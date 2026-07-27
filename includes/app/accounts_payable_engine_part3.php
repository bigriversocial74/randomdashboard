<?php
declare(strict_types=1);
function accounts_payable_finalize_settlement(array$batch,array$execution,string$note):void
{
    $supplierTotals=[];
    foreach(accounts_payable_batch_items((int)$batch['id'])as$item){$invoice=fulfillment_find_invoice((int)$item['invoice_id']);if(!$invoice)throw new RuntimeException('A batch invoice is no longer available.');$remaining=max(0,round((float)$invoice['total_amount']-accounts_payable_invoice_settled_value((int)$invoice['id']),2));$invoice['status']=$remaining<=0.01?'paid':'partially_paid';$invoice['paid_at']=$remaining<=0.01?$execution['settled_at']:null;$invoice['hold_reason']=$remaining<=0.01?'':'Partial settlement recorded; '.money($remaining).' remains payable.';fulfillment_save_invoice($invoice);foreach(accounts_payable_schedules()as$schedule)if((int)$schedule['invoice_id']===(int)$invoice['id']&&!in_array((string)$schedule['status'],['canceled','paid'],true)){if($remaining<=0.01)$schedule['status']='paid';else{$schedule['status']='scheduled';$schedule['gross_amount']=$remaining;$schedule['credit_amount']=0;$schedule['net_amount']=$remaining;$schedule['evidence_note']='Partial settlement recorded; remaining invoice balance rescheduled under AP governance.';}accounts_payable_save_schedule($schedule);}$sid=(int)$item['supplier_id'];if(!isset($supplierTotals[$sid]))$supplierTotals[$sid]=['gross'=>0.0,'credit'=>0.0,'net'=>0.0];$supplierTotals[$sid]['gross']+=(float)$item['gross_amount'];$supplierTotals[$sid]['credit']+=(float)$item['credit_amount'];$supplierTotals[$sid]['net']+=(float)$item['net_amount'];}
    foreach($supplierTotals as$supplierId=>$totals)accounts_payable_save_remittance([
        'id'=>null,'remittance_number'=>accounts_payable_number('REM',accounts_payable_remittances(),'remittance_number'),'batch_id'=>(int)$batch['id'],'company_id'=>(int)$batch['company_id'],'supplier_id'=>$supplierId,
        'payment_reference'=>$execution['provider_reference'],'settlement_reference'=>$execution['settlement_reference'],'payment_date'=>date('Y-m-d',strtotime((string)$execution['settled_at'])),
        'gross_amount'=>round($totals['gross'],2),'credit_amount'=>round($totals['credit'],2),'discount_amount'=>0,'withheld_amount'=>0,'net_amount'=>round($totals['net'],2),
        'currency_code'=>$batch['currency_code'],'delivery_status'=>'available','supplier_acknowledged_at'=>null,'supplier_visible'=>1,'evidence_note'=>$note,
    ]);
}
function accounts_payable_reconcile(array$batch,array$execution,float$settled,float$fee,string$bankReference,int$reviewerId,string$note):array
{
    $actor=(int)current_user()['id'];
    if((string)$batch['status']!=='settled')throw new RuntimeException('Only settled payment batches can be reconciled.');
    if($actor===(int)$batch['released_by'])throw new RuntimeException('The payment releaser cannot reconcile the same batch.');
    if($actor===(int)$batch['prepared_by'])throw new RuntimeException('The payment-batch owner cannot reconcile the same batch.');
    if($actor!==$reviewerId)throw new RuntimeException('The assigned reconciliation reviewer must complete reconciliation.');
    $expected=(float)$batch['net_amount'];$variance=round($settled+$fee-$expected,2);$status=abs($variance)<=0.01?'reconciled':'exception';
    $saved=accounts_payable_save_reconciliation([
        'id'=>null,'reconciliation_number'=>accounts_payable_number('REC',accounts_payable_reconciliations(),'reconciliation_number'),
        'batch_id'=>(int)$batch['id'],'execution_id'=>(int)$execution['id'],'company_id'=>(int)$batch['company_id'],'expected_amount'=>$expected,'settled_amount'=>$settled,
        'fee_amount'=>$fee,'currency_variance'=>$variance,'settlement_date'=>date('Y-m-d'),'status'=>$status,'owner_id'=>(int)$batch['prepared_by'],'reviewer_id'=>$reviewerId,
        'provider_reference'=>$execution['provider_reference'],'bank_reference'=>mb_substr($bankReference,0,190),'evidence_note'=>$note,'reconciled_at'=>$status==='reconciled'?date('Y-m-d H:i:s'):null,
    ]);
    if($status==='reconciled'){$batch['status']='reconciled';$batch['locked_at']=date('Y-m-d H:i:s');accounts_payable_save_batch($batch);}
    accounts_payable_add_event((int)$batch['company_id'],'reconciliation',(int)$saved['id'],'payment_'.$status,'settled',$status,$status==='exception'?'high':'low',$expected,$note);return$saved;
}
function accounts_payable_grni_rows():array
{
    $rows=[];foreach(fulfillment_orders()as$po){$received=0.0;foreach(fulfillment_receipt_lines_for_po((int)$po['id'])as$line){$poLine=fulfillment_find_po_line((int)$line['purchase_order_line_id']);$received+=(float)$line['quantity_accepted']*(float)($poLine['unit_cost']??0);}$invoiced=array_sum(array_map(static fn(array$i):float=>in_array((string)$i['status'],['void'],true)?0:(float)$i['total_amount'],fulfillment_invoices((int)$po['id'])));$grni=max(0,$received-$invoiced);if($grni>0)$rows[]=['company_id'=>(int)$po['company_id'],'purchase_order_id'=>(int)$po['id'],'supplier_id'=>(int)$po['supplier_id'],'received_value'=>round($received,2),'invoiced_value'=>round($invoiced,2),'grni_amount'=>round($grni,2)];}return$rows;
}
function accounts_payable_create_accrual(array$period,array$grni,int$ownerId,int$reviewerId,string$note):array
{
    if((string)$period['status']==='hard_closed')throw new RuntimeException('Accruals cannot be created in a hard-closed accounting period.');
    if($ownerId===$reviewerId)throw new RuntimeException('Accrual owner and reviewer must be different users.');
    $saved=accounts_payable_save_accrual([
        'id'=>null,'accrual_number'=>accounts_payable_number('ACR',accounts_payable_accruals(),'accrual_number'),'accounting_period_id'=>(int)$period['id'],'company_id'=>(int)$grni['company_id'],
        'purchase_order_id'=>(int)$grni['purchase_order_id'],'supplier_id'=>(int)$grni['supplier_id'],'accrual_type'=>'grni','received_value'=>(float)$grni['received_value'],
        'invoiced_value'=>(float)$grni['invoiced_value'],'accrual_amount'=>(float)$grni['grni_amount'],'methodology'=>'Accepted receipt value less non-void supplier invoices.',
        'reversal_date'=>date('Y-m-d',strtotime((string)$period['period_end'].' +1 day')),'status'=>'draft','owner_id'=>$ownerId,'reviewer_id'=>$reviewerId,'evidence_note'=>$note,'approved_at'=>null,'reversed_at'=>null,
    ]);
    accounts_payable_add_event((int)$grni['company_id'],'accrual',(int)$saved['id'],'grni_accrual_created',null,'draft','medium',(float)$saved['accrual_amount'],$note);return$saved;
}
function accounts_payable_validate_credit(array$credit,string$note):array
{
    if((string)$credit['status']!=='draft')throw new RuntimeException('Only draft supplier credits can be finance validated.');
    $actor=(int)current_user()['id'];
    if($actor===(int)$credit['owner_id'])throw new RuntimeException('The supplier-credit owner cannot validate the same credit.');
    if($actor!==(int)$credit['reviewer_id'])throw new RuntimeException('The assigned finance reviewer must validate the supplier credit.');
    $credit['status']='validated';$credit['evidence_note']=$note;$saved=accounts_payable_save_credit($credit);
    accounts_payable_add_event((int)$credit['company_id'],'supplier_credit',(int)$credit['id'],'supplier_credit_validated','draft','validated','medium',(float)$credit['original_amount'],$note);return$saved;
}
function accounts_payable_approve_accrual(array$accrual,string$note):array
{
    if((string)$accrual['status']!=='draft')throw new RuntimeException('Only draft accruals can be approved.');
    if((int)current_user()['id']===(int)$accrual['owner_id'])throw new RuntimeException('The accrual owner cannot approve the accrual.');
    if((int)current_user()['id']!==(int)$accrual['reviewer_id'])throw new RuntimeException('The assigned accrual reviewer must approve the accrual.');
    $accrual['status']='approved';$accrual['approved_at']=date('Y-m-d H:i:s');$saved=accounts_payable_save_accrual($accrual);
    accounts_payable_add_event((int)$accrual['company_id'],'accrual',(int)$accrual['id'],'accrual_approved','draft','approved','medium',(float)$accrual['accrual_amount'],$note);return$saved;
}
