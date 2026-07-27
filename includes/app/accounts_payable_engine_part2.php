<?php
declare(strict_types=1);
function accounts_payable_invoice_in_active_batch(int$invoiceId,?int$excludeBatch=null):bool
{
    $active=[];foreach(accounts_payable_batches()as$b)if(in_array((string)$b['status'],accounts_payable_active_batch_statuses(),true))$active[(int)$b['id']]=true;
    foreach(accounts_payable_batch_items()as$item)if((int)$item['invoice_id']===$invoiceId&&isset($active[(int)$item['batch_id']])&&($excludeBatch===null||(int)$item['batch_id']!==$excludeBatch))return true;
    return false;
}
function accounts_payable_recalculate_batch(array$batch):array
{
    $items=accounts_payable_batch_items((int)$batch['id']);$batch['gross_amount']=round(array_sum(array_map(static fn(array$i):float=>(float)$i['gross_amount'],$items)),2);
    $batch['credit_amount']=round(array_sum(array_map(static fn(array$i):float=>(float)$i['credit_amount'],$items)),2);
    $batch['net_amount']=round(array_sum(array_map(static fn(array$i):float=>(float)$i['net_amount'],$items)),2);
    $batch['invoice_count']=count(array_filter($items,static fn(array$i):bool=>(int)$i['invoice_id']>0));
    $batch['batch_hash']=hash('sha256',json_encode(array_map(static fn(array$i):array=>[(int)$i['invoice_id'],(int)($i['credit_id']??0),(float)$i['net_amount']],$items)));
    return accounts_payable_save_batch($batch);
}
function accounts_payable_invoice_settled_value(int$invoiceId):float
{
    $settled=[];foreach(accounts_payable_batches()as$batch)if(in_array((string)$batch['status'],['settled','reconciled'],true))$settled[(int)$batch['id']]=true;
    return round(array_sum(array_map(static fn(array$item):float=>(int)$item['invoice_id']===$invoiceId&&isset($settled[(int)$item['batch_id']])?(float)$item['gross_amount']:0,accounts_payable_batch_items())),2);
}
function accounts_payable_invoice_outstanding(array$invoice):float{return max(0,round((float)$invoice['total_amount']-accounts_payable_invoice_settled_value((int)$invoice['id']),2));}
function accounts_payable_add_invoice_to_batch(array$batch,array$invoice,?array$credit,string$note,?float$paymentAmount=null):array
{
    if((string)$batch['status']!=='draft')throw new RuntimeException('Invoices can only be added to draft payment batches.');
    if((int)$batch['company_id']!==(int)$invoice['company_id'])throw new RuntimeException('The invoice company does not match the payment batch.');
    if((string)($invoice['currency_code']??'USD')!==(string)$batch['currency_code'])throw new RuntimeException('The batch currency does not match the invoice.');
    if(!in_array(fulfillment_invoice_effective_status($invoice),['approved_for_payment','partially_paid'],true))throw new RuntimeException('The invoice is not approved for payment.');
    if(accounts_payable_invoice_in_active_batch((int)$invoice['id'],(int)$batch['id']))throw new RuntimeException('The invoice is already included in another active payment batch.');
    $outstanding=accounts_payable_invoice_outstanding($invoice);if($outstanding<=0)throw new RuntimeException('The invoice has no remaining payable balance.');$grossAmount=min($outstanding,$paymentAmount===null||$paymentAmount<=0?$outstanding:$paymentAmount);
    $creditAmount=0;if($credit){if((int)$credit['supplier_id']!==(int)$invoice['supplier_id']||(int)$credit['company_id']!==(int)$invoice['company_id'])throw new RuntimeException('The selected credit does not match the invoice supplier and company.');if((string)$credit['status']!=='validated')throw new RuntimeException('Only finance-validated credits can be applied.');$creditAmount=min((float)$credit['remaining_amount'],$grossAmount);}
    $saved=accounts_payable_save_batch_item(['id'=>null,'batch_id'=>(int)$batch['id'],'company_id'=>(int)$batch['company_id'],'supplier_id'=>(int)$invoice['supplier_id'],'invoice_id'=>(int)$invoice['id'],'credit_id'=>$credit['id']??null,'gross_amount'=>$grossAmount,'credit_amount'=>$creditAmount,'net_amount'=>round($grossAmount-$creditAmount,2),'status'=>'included','evidence_note'=>$note]);
    if($credit){$credit['applied_amount']=round((float)$credit['applied_amount']+$creditAmount,2);$credit['remaining_amount']=round((float)$credit['original_amount']-(float)$credit['applied_amount'],2);$credit['status']=$credit['remaining_amount']<=0?'applied':'validated';accounts_payable_save_credit($credit);}
    foreach(accounts_payable_schedules()as$schedule)if((int)$schedule['invoice_id']===(int)$invoice['id']&&!in_array((string)$schedule['status'],['canceled','paid'],true)){$schedule['status']='batched';$schedule['credit_amount']=$creditAmount;$schedule['gross_amount']=$grossAmount;$schedule['net_amount']=round($grossAmount-$creditAmount,2);accounts_payable_save_schedule($schedule);}
    accounts_payable_recalculate_batch($batch);accounts_payable_add_event((int)$batch['company_id'],'payment_batch',(int)$batch['id'],'invoice_added','draft','draft','medium',(float)$saved['net_amount'],$note);return$saved;
}
function accounts_payable_transition_batch(array$batch,string$target,string$note):array
{
    $actor=(int)current_user()['id'];$from=(string)$batch['status'];
    if($target==='reviewed'){if($from!=='draft')throw new RuntimeException('Only draft batches can be reviewed.');if($actor===(int)$batch['prepared_by'])throw new RuntimeException('The batch preparer cannot review the batch.');if($actor!==(int)$batch['reviewed_by'])throw new RuntimeException('The assigned batch reviewer must complete review.');if((int)$batch['invoice_count']<1)throw new RuntimeException('The payment batch must contain at least one invoice.');$batch['reviewed_at']=date('Y-m-d H:i:s');}
    elseif($target==='approved'){if($from!=='reviewed')throw new RuntimeException('Only reviewed batches can be approved.');if(in_array($actor,[(int)$batch['prepared_by'],(int)$batch['reviewed_by']],true))throw new RuntimeException('The payment approver must be independent from preparation and review.');if($actor!==(int)$batch['approved_by'])throw new RuntimeException('The assigned payment approver must approve the batch.');$batch['approved_at']=date('Y-m-d H:i:s');$batch['locked_at']=$batch['approved_at'];}
    elseif($target==='released'){if($from!=='approved')throw new RuntimeException('Only approved batches can be released.');if($actor===(int)$batch['approved_by'])throw new RuntimeException('The payment approver cannot release the same batch.');foreach(accounts_payable_batch_items((int)$batch['id'])as$item){$instruction=accounts_payable_instruction_for((int)$batch['company_id'],(int)$item['supplier_id'],(string)$batch['payment_method']);if(!accounts_payable_instruction_ready($instruction))throw new RuntimeException('A supplier payment instruction is not independently verified or the cooling-off period is not complete.');}$batch['released_by']=$actor;$batch['released_at']=date('Y-m-d H:i:s');}
    else throw new RuntimeException('Unknown payment-batch transition.');
    $batch['status']=$target;$batch['evidence_note']=$note;$saved=accounts_payable_save_batch($batch);accounts_payable_add_event((int)$batch['company_id'],'payment_batch',(int)$batch['id'],'batch_'.$target,$from,$target,'high',(float)$batch['net_amount'],$note);return$saved;
}
function accounts_payable_record_execution(array$batch,string$status,string$providerReference,string$settlementReference,float$fee,string$note):array
{
    if(!in_array($status,['transmitted','accepted','settled','failed','returned'],true))throw new RuntimeException('Select a valid execution status.');
    if(trim($providerReference)==='')throw new RuntimeException('A provider or bank acknowledgment reference is required.');
    if($status==='settled'&&trim($settlementReference)==='')throw new RuntimeException('A settlement reference is required before invoices can be marked paid.');
    $idempotency=hash('sha256',(int)$batch['id'].'|'.$status.'|'.$providerReference.'|'.$settlementReference);
    foreach(accounts_payable_executions((int)$batch['id'])as$existing)if(hash_equals((string)$existing['idempotency_key'],$idempotency))return$existing;
    $allowed=['released'=>['transmitted','accepted','failed'],'transmitted'=>['accepted','failed'],'accepted'=>['settled','failed','returned'],'settled'=>[],'failed'=>['transmitted'],'returned'=>['transmitted']];
    if(!in_array($status,$allowed[(string)$batch['status']]??[],true))throw new RuntimeException('The requested execution transition is not valid for this batch.');
    $now=date('Y-m-d H:i:s');$saved=accounts_payable_save_execution([
        'id'=>null,'execution_number'=>accounts_payable_number('APX',accounts_payable_executions(),'execution_number'),'batch_id'=>(int)$batch['id'],'company_id'=>(int)$batch['company_id'],
        'idempotency_key'=>$idempotency,'provider_reference'=>mb_substr($providerReference,0,190),'settlement_reference'=>mb_substr($settlementReference,0,190),
        'execution_status'=>$status,'executed_amount'=>(float)$batch['net_amount'],'fee_amount'=>max(0,$fee),'failure_code'=>$status==='failed'?'PROVIDER_REJECTED':'',
        'failure_reason'=>$status==='failed'?$note:'','transmitted_at'=>in_array($status,['transmitted','accepted','settled'],true)?$now:null,
        'accepted_at'=>in_array($status,['accepted','settled'],true)?$now:null,'settled_at'=>$status==='settled'?$now:null,'returned_at'=>$status==='returned'?$now:null,
        'created_by'=>(int)current_user()['id'],'evidence_note'=>$note,
    ]);
    $from=$batch['status'];$batch['status']=$status;$batch['settled_at']=$status==='settled'?$now:$batch['settled_at'];$batch['locked_at']=$status==='settled'?$now:$batch['locked_at'];accounts_payable_save_batch($batch);
    if($status==='settled')accounts_payable_finalize_settlement($batch,$saved,$note);
    accounts_payable_add_event((int)$batch['company_id'],'payment_execution',(int)$saved['id'],'execution_'.$status,$from,$status,$status==='failed'||$status==='returned'?'critical':'high',(float)$batch['net_amount'],$note);return$saved;
}
