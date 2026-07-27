<?php
declare(strict_types=1);

function accounts_payable_payment_methods():array{return['ach','wire','check','virtual_card','external_settlement'];}
function accounts_payable_active_batch_statuses():array{return['draft','reviewed','approved','released','transmitted','accepted'];}
function accounts_payable_number(string$prefix,array$rows,string$field):string
{
    $max=0;foreach($rows as$row)if(preg_match('/(\d+)$/',(string)($row[$field]??''),$m))$max=max($max,(int)$m[1]);
    return$prefix.'-'.date('Y').'-'.str_pad((string)($max+1),4,'0',STR_PAD_LEFT);
}
function accounts_payable_eligible_invoices():array
{
    return array_values(array_filter(fulfillment_invoices(),static fn(array$i):bool=>in_array(fulfillment_invoice_effective_status($i),['approved_for_payment','partially_paid'],true)));
}
function accounts_payable_supplier_company_valid(int$supplierId,int$companyId):bool
{
    $supplier=data_find('suppliers',$supplierId);if(!$supplier)return false;
    if(data_is_demo())return in_array($companyId,array_map('intval',$supplier['company_ids']??[]),true);
    $stmt=production_database_connection()->prepare('SELECT COUNT(*) FROM supplier_companies WHERE supplier_id=? AND company_id=? AND account_status="active"');$stmt->execute([$supplierId,$companyId]);return(int)$stmt->fetchColumn()>0;
}
function accounts_payable_instruction_for(int$companyId,int$supplierId,string$method):?array
{
    foreach(accounts_payable_instructions()as$row)if((int)$row['company_id']===$companyId&&(int)$row['supplier_id']===$supplierId&&(string)$row['payment_method']===$method&&($row['status']??'')==='verified')return$row;
    return null;
}
function accounts_payable_instruction_ready(?array$instruction):bool
{
    if(!$instruction||($instruction['status']??'')!=='verified'||empty($instruction['verified_by']))return false;
    if((int)$instruction['requested_by']===(int)$instruction['verified_by'])return false;
    $today=date('Y-m-d');return(string)$instruction['effective_date']<=$today&&(string)$instruction['cooling_until']<=$today;
}
function accounts_payable_save_verified_instruction(array$record):array
{
    $actor=(int)current_user()['id'];
    if((int)$record['requested_by']===(int)$record['verified_by'])throw new RuntimeException('Payment-instruction requester and verifier must be different users.');
    if($actor!==(int)$record['verified_by'])throw new RuntimeException('The assigned independent verifier must record payment-instruction verification.');
    if(!in_array((string)$record['payment_method'],accounts_payable_payment_methods(),true))throw new RuntimeException('Select a valid payment method.');
    if(trim((string)$record['vault_reference'])===''||trim((string)$record['callback_evidence'])==='')throw new RuntimeException('External vault reference and independent callback evidence are required.');
    if((string)$record['cooling_until']<(string)$record['effective_date'])throw new RuntimeException('The cooling-off date cannot precede the effective date.');
    $saved=accounts_payable_save_instruction($record);
    accounts_payable_add_event((int)$saved['company_id'],'payment_instruction',(int)$saved['id'],'payment_instruction_verified',null,'verified','high',0,(string)$saved['callback_evidence']);
    return$saved;
}
function accounts_payable_schedule_invoice(array$invoice,string$date,string$method,int$ownerId,int$reviewerId,string$note):array
{
    if(fulfillment_invoice_effective_status($invoice)!=='approved_for_payment')throw new RuntimeException('Only invoices approved for payment can be scheduled.');
    if(!in_array($method,accounts_payable_payment_methods(),true))throw new RuntimeException('Select a valid payment method.');
    if($ownerId===$reviewerId)throw new RuntimeException('Payment schedule owner and reviewer must be different users.');
    foreach(accounts_payable_schedules()as$row)if((int)$row['invoice_id']===(int)$invoice['id']&&!in_array((string)$row['status'],['canceled','paid'],true))throw new RuntimeException('The invoice already has an active payment schedule.');
    $discountDate=null;if(str_contains(strtolower((string)$invoice['payment_terms']),'2/10'))$discountDate=date('Y-m-d',strtotime((string)$invoice['invoice_date'].' +10 days'));
    $saved=accounts_payable_save_schedule([
        'id'=>null,'schedule_number'=>accounts_payable_number('APS',accounts_payable_schedules(),'schedule_number'),
        'company_id'=>(int)$invoice['company_id'],'supplier_id'=>(int)$invoice['supplier_id'],'invoice_id'=>(int)$invoice['id'],
        'scheduled_date'=>$date,'discount_date'=>$discountDate,'gross_amount'=>(float)$invoice['total_amount'],'credit_amount'=>0,
        'net_amount'=>(float)$invoice['total_amount'],'currency_code'=>$invoice['currency_code']??'USD','payment_method'=>$method,
        'priority'=>strtotime((string)$invoice['due_date'])<=strtotime('+7 days')?'high':'normal','status'=>'scheduled','hold_reason'=>'',
        'owner_id'=>$ownerId,'reviewer_id'=>$reviewerId,'evidence_note'=>$note,
    ]);
    accounts_payable_add_event((int)$invoice['company_id'],'payment_schedule',(int)$saved['id'],'invoice_scheduled',null,'scheduled','medium',(float)$saved['net_amount'],$note);return$saved;
}
function accounts_payable_create_batch(int$companyId,string$method,string$date,string$bankReference,int$preparedBy,int$reviewedBy,int$approvedBy,string$note):array
{
    if(!in_array($method,accounts_payable_payment_methods(),true))throw new RuntimeException('Select a valid payment method.');
    if(count(array_unique([$preparedBy,$reviewedBy,$approvedBy]))<3)throw new RuntimeException('Batch preparer, reviewer, and approver must be three different users.');
    if(trim($bankReference)==='')throw new RuntimeException('A controlled bank-account or payment-adapter reference is required.');
    $saved=accounts_payable_save_batch([
        'id'=>null,'batch_number'=>accounts_payable_number('APB',accounts_payable_batches(),'batch_number'),'company_id'=>$companyId,
        'payment_method'=>$method,'currency_code'=>'USD','execution_date'=>$date,'bank_account_reference'=>mb_substr($bankReference,0,160),
        'gross_amount'=>0,'credit_amount'=>0,'net_amount'=>0,'invoice_count'=>0,'status'=>'draft','prepared_by'=>$preparedBy,
        'reviewed_by'=>$reviewedBy,'approved_by'=>$approvedBy,'released_by'=>null,'batch_hash'=>'','evidence_note'=>$note,
        'reviewed_at'=>null,'approved_at'=>null,'released_at'=>null,'settled_at'=>null,'locked_at'=>null,
    ]);
    accounts_payable_add_event($companyId,'payment_batch',(int)$saved['id'],'batch_created',null,'draft','high',0,$note);return$saved;
}
