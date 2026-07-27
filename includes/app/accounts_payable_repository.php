<?php
declare(strict_types=1);

function accounts_payable_tables(): array
{
    return [
        'ap_payment_schedules','ap_payment_batches','ap_payment_batch_items','ap_payment_executions',
        'ap_remittance_records','ap_supplier_credits','ap_reconciliations','ap_accounting_periods',
        'ap_accrual_entries','ap_close_certifications','ap_supplier_payment_instructions','ap_governance_events',
    ];
}
function accounts_payable_tables_ready(): bool
{
    if(data_is_demo())return true;
    $pdo=production_database_connection();if(!$pdo)return false;
    try{$names=accounts_payable_tables();$stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('.implode(',',array_fill(0,count($names),'?')).')');$stmt->execute($names);return(int)$stmt->fetchColumn()===count($names);}catch(Throwable){return false;}
}
function accounts_payable_require_tables(): void
{
    if(!accounts_payable_tables_ready())throw new RuntimeException('Import the Section 23 migration before using Production Data accounts-payable writes.');
}
function accounts_payable_demo_collection(string $key,callable $seed): array
{
    if(!isset($_SESSION['gruber_demo_state'][$key]))$_SESSION['gruber_demo_state'][$key]=$seed();
    return array_values($_SESSION['gruber_demo_state'][$key]);
}
function accounts_payable_demo_save(string $key,array $record,callable $seed): array
{
    $rows=accounts_payable_demo_collection($key,$seed);$id=(int)($record['id']??0);
    if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;$rows[]=$record;}
    else{$found=false;foreach($rows as$i=>$row)if((int)$row['id']===$id){$rows[$i]=array_replace($row,$record);$record=$rows[$i];$found=true;break;}if(!$found)$rows[]=$record;}
    $_SESSION['gruber_demo_state'][$key]=array_values($rows);return$record;
}
function accounts_payable_row_visible(array $row): bool
{
    if(!array_key_exists('company_id',$row)||$row['company_id']===null||$row['company_id']==='')return current_company_id()==='enterprise'&&can_use_enterprise_view();
    return data_company_within_scope((int)$row['company_id'])&&(current_company_id()==='enterprise'||(int)current_company_id()===(int)$row['company_id']);
}
function accounts_payable_rows(string $table,string $demoKey,callable $seed,string $order='id DESC'): array
{
    if(data_is_demo())$rows=accounts_payable_demo_collection($demoKey,$seed);
    else{if(!accounts_payable_tables_ready())return[];$rows=production_database_connection()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();}
    return array_values(array_filter($rows,'accounts_payable_row_visible'));
}
function accounts_payable_schedules():array{return accounts_payable_rows('ap_payment_schedules','ap_payment_schedules','accounts_payable_demo_schedules','scheduled_date,id');}
function accounts_payable_batches():array{return accounts_payable_rows('ap_payment_batches','ap_payment_batches','accounts_payable_demo_batches','execution_date DESC,id DESC');}
function accounts_payable_batch_items(?int $batchId=null):array{$r=accounts_payable_rows('ap_payment_batch_items','ap_payment_batch_items','accounts_payable_demo_batch_items','id');return$batchId===null?$r:array_values(array_filter($r,static fn(array$x):bool=>(int)$x['batch_id']===$batchId));}
function accounts_payable_executions(?int $batchId=null):array{$r=accounts_payable_rows('ap_payment_executions','ap_payment_executions','accounts_payable_demo_executions','created_at DESC,id DESC');return$batchId===null?$r:array_values(array_filter($r,static fn(array$x):bool=>(int)$x['batch_id']===$batchId));}
function accounts_payable_remittances(?int $supplierId=null):array{$r=accounts_payable_rows('ap_remittance_records','ap_remittance_records','accounts_payable_demo_remittances','payment_date DESC,id DESC');return$supplierId===null?$r:array_values(array_filter($r,static fn(array$x):bool=>(int)$x['supplier_id']===$supplierId));}
function accounts_payable_credits():array{return accounts_payable_rows('ap_supplier_credits','ap_supplier_credits','accounts_payable_demo_credits','credit_date DESC,id DESC');}
function accounts_payable_reconciliations():array{return accounts_payable_rows('ap_reconciliations','ap_reconciliations','accounts_payable_demo_reconciliations','settlement_date DESC,id DESC');}
function accounts_payable_periods():array{return accounts_payable_rows('ap_accounting_periods','ap_accounting_periods','accounts_payable_demo_periods','period_end DESC,id DESC');}
function accounts_payable_accruals(?int $periodId=null):array{$r=accounts_payable_rows('ap_accrual_entries','ap_accrual_entries','accounts_payable_demo_accruals','created_at DESC,id DESC');return$periodId===null?$r:array_values(array_filter($r,static fn(array$x):bool=>(int)$x['accounting_period_id']===$periodId));}
function accounts_payable_certifications(?int $periodId=null):array{$r=accounts_payable_rows('ap_close_certifications','ap_close_certifications','accounts_payable_demo_certifications','created_at DESC,id DESC');return$periodId===null?$r:array_values(array_filter($r,static fn(array$x):bool=>(int)$x['accounting_period_id']===$periodId));}
function accounts_payable_instructions():array{return accounts_payable_rows('ap_supplier_payment_instructions','ap_supplier_payment_instructions','accounts_payable_demo_instructions','effective_date DESC,id DESC');}
function accounts_payable_events():array{return accounts_payable_rows('ap_governance_events','ap_governance_events','accounts_payable_demo_events','created_at DESC,id DESC');}
function accounts_payable_supplier_remittances(int$supplierId,array$companyIds):array
{
    $companyIds=array_values(array_unique(array_filter(array_map('intval',$companyIds))));if(!$companyIds)return[];
    if(data_is_demo())$rows=accounts_payable_demo_collection('ap_remittance_records','accounts_payable_demo_remittances');
    else{if(!accounts_payable_tables_ready())return[];$stmt=production_database_connection()->prepare('SELECT * FROM ap_remittance_records WHERE supplier_id=? AND supplier_visible=1 AND company_id IN ('.implode(',',array_fill(0,count($companyIds),'?')).') ORDER BY payment_date DESC,id DESC');$stmt->execute([$supplierId,...$companyIds]);$rows=$stmt->fetchAll();}
    return array_values(array_filter($rows,static fn(array$r):bool=>(int)$r['supplier_id']===$supplierId&&!empty($r['supplier_visible'])&&in_array((int)$r['company_id'],$companyIds,true)));
}
function accounts_payable_find(array $rows,int $id):?array{foreach($rows as$row)if((int)$row['id']===$id)return$row;return null;}
function accounts_payable_find_schedule(int$id):?array{return accounts_payable_find(accounts_payable_schedules(),$id);}
function accounts_payable_find_batch(int$id):?array{return accounts_payable_find(accounts_payable_batches(),$id);}
function accounts_payable_find_credit(int$id):?array{return accounts_payable_find(accounts_payable_credits(),$id);}
function accounts_payable_find_period(int$id):?array{return accounts_payable_find(accounts_payable_periods(),$id);}
function accounts_payable_find_instruction(int$id):?array{return accounts_payable_find(accounts_payable_instructions(),$id);}

function accounts_payable_save_row(string$table,string$demoKey,callable$seed,array$record,array$fields):array
{
    accounts_payable_require_tables();$record['created_at']=$record['created_at']??date('Y-m-d H:i:s');$record['updated_at']=date('Y-m-d H:i:s');
    if(data_is_demo())return accounts_payable_demo_save($demoKey,$record,$seed);
    $pdo=production_database_connection();$id=(int)($record['id']??0);$values=[];foreach($fields as$f)$values[]=$record[$f]??null;
    if($id){$values[]=$id;$pdo->prepare('UPDATE '.$table.' SET '.implode(',',array_map(static fn(string$f):string=>$f.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);}
    else{$pdo->prepare('INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);$id=(int)$pdo->lastInsertId();}
    $record['id']=$id;return$record;
}
function accounts_payable_save_schedule(array$r):array{return accounts_payable_save_row('ap_payment_schedules','ap_payment_schedules','accounts_payable_demo_schedules',$r,['schedule_number','company_id','supplier_id','invoice_id','scheduled_date','discount_date','gross_amount','credit_amount','net_amount','currency_code','payment_method','priority','status','hold_reason','owner_id','reviewer_id','evidence_note']);}
function accounts_payable_save_batch(array$r):array{return accounts_payable_save_row('ap_payment_batches','ap_payment_batches','accounts_payable_demo_batches',$r,['batch_number','company_id','payment_method','currency_code','execution_date','bank_account_reference','gross_amount','credit_amount','net_amount','invoice_count','status','prepared_by','reviewed_by','approved_by','released_by','batch_hash','evidence_note','reviewed_at','approved_at','released_at','settled_at','locked_at']);}
function accounts_payable_save_batch_item(array$r):array{return accounts_payable_save_row('ap_payment_batch_items','ap_payment_batch_items','accounts_payable_demo_batch_items',$r,['batch_id','company_id','supplier_id','invoice_id','credit_id','gross_amount','credit_amount','net_amount','status','evidence_note']);}
function accounts_payable_save_execution(array$r):array{return accounts_payable_save_row('ap_payment_executions','ap_payment_executions','accounts_payable_demo_executions',$r,['execution_number','batch_id','company_id','idempotency_key','provider_reference','settlement_reference','execution_status','executed_amount','fee_amount','failure_code','failure_reason','transmitted_at','accepted_at','settled_at','returned_at','created_by','evidence_note']);}
function accounts_payable_save_remittance(array$r):array{return accounts_payable_save_row('ap_remittance_records','ap_remittance_records','accounts_payable_demo_remittances',$r,['remittance_number','batch_id','company_id','supplier_id','payment_reference','settlement_reference','payment_date','gross_amount','credit_amount','discount_amount','withheld_amount','net_amount','currency_code','delivery_status','supplier_acknowledged_at','supplier_visible','evidence_note']);}
function accounts_payable_save_credit(array$r):array{return accounts_payable_save_row('ap_supplier_credits','ap_supplier_credits','accounts_payable_demo_credits',$r,['credit_number','company_id','supplier_id','invoice_id','credit_type','credit_memo_number','credit_date','expiration_date','currency_code','original_amount','applied_amount','remaining_amount','status','owner_id','reviewer_id','evidence_note']);}
function accounts_payable_save_reconciliation(array$r):array{return accounts_payable_save_row('ap_reconciliations','ap_reconciliations','accounts_payable_demo_reconciliations',$r,['reconciliation_number','batch_id','execution_id','company_id','expected_amount','settled_amount','fee_amount','currency_variance','settlement_date','status','owner_id','reviewer_id','provider_reference','bank_reference','evidence_note','reconciled_at']);}
function accounts_payable_save_period(array$r):array{return accounts_payable_save_row('ap_accounting_periods','ap_accounting_periods','accounts_payable_demo_periods',$r,['period_number','company_id','fiscal_year','period_label','period_start','period_end','status','soft_closed_at','hard_closed_at','locked_at','owner_id','reviewer_id','evidence_note']);}
function accounts_payable_save_accrual(array$r):array{return accounts_payable_save_row('ap_accrual_entries','ap_accrual_entries','accounts_payable_demo_accruals',$r,['accrual_number','accounting_period_id','company_id','purchase_order_id','supplier_id','accrual_type','received_value','invoiced_value','accrual_amount','methodology','reversal_date','status','owner_id','reviewer_id','evidence_note','approved_at','reversed_at']);}
function accounts_payable_save_certification(array$r):array{return accounts_payable_save_row('ap_close_certifications','ap_close_certifications','accounts_payable_demo_certifications',$r,['certification_number','accounting_period_id','company_id','certification_type','status','open_invoice_count','unsettled_batch_count','unreconciled_count','unapplied_credit_amount','grni_amount','prepared_by','reviewed_by','approved_by','evidence_note','certified_at']);}
function accounts_payable_save_instruction(array$r):array{return accounts_payable_save_row('ap_supplier_payment_instructions','ap_supplier_payment_instructions','accounts_payable_demo_instructions',$r,['instruction_number','company_id','supplier_id','vault_reference','instruction_fingerprint','payment_method','status','requested_by','verified_by','requested_at','verified_at','effective_date','cooling_until','callback_evidence','change_reason']);}
function accounts_payable_add_event(int$companyId,string$entityType,int$entityId,string$eventType,?string$from,?string$to,string$severity,float$amount,string$note):array
{
    accounts_payable_require_tables();$r=['id'=>null,'company_id'=>$companyId,'entity_type'=>$entityType,'entity_id'=>$entityId,'event_type'=>$eventType,'from_status'=>$from,'to_status'=>$to,'severity'=>$severity,'amount'=>$amount,'evidence_note'=>$note,'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo())return accounts_payable_demo_save('ap_governance_events',$r,'accounts_payable_demo_events');
    $pdo=production_database_connection();$pdo->prepare('INSERT INTO ap_governance_events(company_id,entity_type,entity_id,event_type,from_status,to_status,severity,amount,evidence_note,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$companyId,$entityType,$entityId,$eventType,$from,$to,$severity,$amount,$note,$r['created_by']]);$r['id']=(int)$pdo->lastInsertId();return$r;
}
