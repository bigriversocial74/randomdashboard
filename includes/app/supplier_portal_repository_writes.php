<?php
declare(strict_types=1);
function supplier_portal_save_row(string $table,string $demoKey,callable $seed,array $record,array $fields): array
{
    supplier_portal_require_tables();
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    $record['updated_at']=date('Y-m-d H:i:s');
    if (data_is_demo()) return supplier_portal_demo_save($demoKey,$record,$seed);
    $pdo=production_database_connection();
    $id=(int)($record['id']??0);
    $values=[];foreach($fields as $field)$values[]=$record[$field]??null;
    if($id>0){$values[]=$id;$pdo->prepare('UPDATE '.$table.' SET '.implode(',',array_map(static fn(string $f):string=>$f.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);}
    else{$pdo->prepare('INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);$id=(int)$pdo->lastInsertId();}
    $record['id']=$id;
    return $record;
}

function supplier_portal_save_account(array $r): array{return supplier_portal_save_row('supplier_portal_accounts','supplier_portal_accounts','supplier_portal_demo_accounts',$r,['supplier_id','supplier_contact_id','name','email','password_hash','portal_role','status','email_verified_at','mfa_required','last_login_at','locked_until','created_by']);}
function supplier_portal_save_invitation(array $r): array{return supplier_portal_save_row('supplier_portal_invitations','supplier_portal_invitations','supplier_portal_demo_invitations',$r,['supplier_id','supplier_contact_id','invited_name','email','portal_role','token_hash','expires_at','status','invited_by','accepted_account_id','accepted_at']);}
function supplier_portal_save_grant(array $r): array{return supplier_portal_save_row('supplier_portal_access_grants','supplier_portal_access_grants','supplier_portal_demo_grants',$r,['account_id','company_id','access_scope','can_acknowledge_po','can_submit_asn','can_submit_invoice','can_submit_documents','can_message','can_quality','can_sourcing','starts_at','expires_at','status','granted_by']);}
function supplier_portal_save_po_response(array $r): array{return supplier_portal_save_row('supplier_purchase_order_responses','supplier_purchase_order_responses','supplier_portal_demo_po_responses',$r,['response_number','supplier_id','purchase_order_id','account_id','response_type','proposed_delivery_date','proposed_total_amount','currency_code','notes','evidence_reference','status','reviewed_by','reviewed_at','review_note']);}
function supplier_portal_save_asn(array $r): array{return supplier_portal_save_row('supplier_shipment_notices','supplier_shipment_notices','supplier_portal_demo_asns',$r,['asn_number','supplier_id','purchase_order_id','account_id','ship_date','estimated_arrival','carrier','tracking_number','package_count','pallet_count','packing_slip_reference','status','reviewed_by','reviewed_at','review_note']);}
function supplier_portal_save_asn_line(array $r): array{return supplier_portal_save_row('supplier_shipment_notice_lines','supplier_shipment_notice_lines','supplier_portal_demo_asn_lines',$r,['shipment_notice_id','purchase_order_line_id','quantity_shipped','lot_or_serial_reference','package_reference','notes']);}
function supplier_portal_save_invoice_submission(array $r): array{return supplier_portal_save_row('supplier_invoice_submissions','supplier_invoice_submissions','supplier_portal_demo_invoice_submissions',$r,['submission_number','supplier_id','purchase_order_id','account_id','invoice_number','invoice_date','due_date','currency_code','subtotal','freight_amount','tax_amount','total_amount','document_reference','status','duplicate_flag','reviewed_by','reviewed_at','review_note','canonical_invoice_id']);}
function supplier_portal_save_invoice_line(array $r): array{return supplier_portal_save_row('supplier_invoice_submission_lines','supplier_invoice_submission_lines','supplier_portal_demo_invoice_lines',$r,['invoice_submission_id','purchase_order_line_id','description','quantity_invoiced','unit_price','tax_amount','freight_amount','line_total']);}
function supplier_portal_save_document(array $r): array{return supplier_portal_save_row('supplier_document_submissions','supplier_document_submissions','supplier_portal_demo_documents',$r,['supplier_id','account_id','entity_type','entity_id','document_type','title','document_reference','effective_date','expiration_date','status','reviewed_by','reviewed_at','review_note']);}
function supplier_portal_save_sourcing_submission(array $r): array{return supplier_portal_save_row('supplier_sourcing_submissions','supplier_sourcing_submissions','supplier_portal_demo_sourcing_submissions',$r,['submission_number','supplier_id','account_id','sourcing_reference','title','bid_deadline','currency_code','proposal_value','lead_time_days','payment_terms','freight_terms','exceptions_and_assumptions','document_reference','revision_number','status','locked_at','reviewed_by','reviewed_at','review_note']);}
function supplier_portal_save_quality_response(array $r): array{return supplier_portal_save_row('supplier_quality_responses','supplier_quality_responses','supplier_portal_demo_quality_responses',$r,['response_number','supplier_id','account_id','quality_reference','response_type','containment_response','root_cause','corrective_action','preventive_action','target_date','evidence_reference','status','reviewed_by','reviewed_at','review_note']);}
function supplier_portal_save_message(array $r): array{return supplier_portal_save_row('supplier_collaboration_messages','supplier_collaboration_messages','supplier_portal_demo_messages',$r,['supplier_id','account_id','company_id','entity_type','entity_id','direction','subject','message_body','supplier_visible','required_response_date','status','read_at','acknowledged_at','created_by_internal']);}

function supplier_portal_add_event(int $supplierId,?int $companyId,string $entityType,int $entityId,string $eventType,?string $fromStatus,?string $toStatus,string $severity,string $note,string $actorType,?int $internalUserId,?int $portalAccountId): array
{
    supplier_portal_require_tables();
    $record=['id'=>null,'supplier_id'=>$supplierId,'account_id'=>$portalAccountId,'company_id'=>$companyId,'entity_type'=>$entityType,'entity_id'=>$entityId,'event_type'=>$eventType,'from_status'=>$fromStatus,'to_status'=>$toStatus,'severity'=>$severity,'evidence_note'=>$note,'actor_type'=>$actorType,'actor_internal_user_id'=>$internalUserId,'actor_portal_account_id'=>$portalAccountId,'ip_address'=>current_ip(),'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo())return supplier_portal_demo_save('supplier_portal_events',$record,'supplier_portal_demo_events');
    $pdo=production_database_connection();
    $pdo->prepare('INSERT INTO supplier_portal_events(supplier_id,account_id,company_id,entity_type,entity_id,event_type,from_status,to_status,severity,evidence_note,actor_type,actor_internal_user_id,actor_portal_account_id,ip_address) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$supplierId,$portalAccountId,$companyId,$entityType,$entityId,$eventType,$fromStatus,$toStatus,$severity,$note,$actorType,$internalUserId,$portalAccountId,current_ip()]);
    $record['id']=(int)$pdo->lastInsertId();
    return $record;
}

function supplier_portal_account_company_ids(array $account): array
{
    $ids=[];$today=date('Y-m-d');
    foreach(supplier_portal_access_grants((int)$account['id']) as $grant){
        if(($grant['status']??'')!=='active')continue;
        if(!empty($grant['starts_at'])&&$grant['starts_at']>$today)continue;
        if(!empty($grant['expires_at'])&&$grant['expires_at']<$today)continue;
        if(!empty($grant['company_id']))$ids[]=(int)$grant['company_id'];
    }
    return array_values(array_unique($ids));
}

function supplier_portal_account_grant(array $account,string $capability): bool
{
    $field='can_'.$capability;
    foreach(supplier_portal_access_grants((int)$account['id']) as $grant){
        if(($grant['status']??'')==='active'&&!empty($grant[$field]))return true;
    }
    return false;
}

function supplier_portal_account_purchase_orders(array $account): array
{
    $supplierId=(int)$account['supplier_id'];$companies=supplier_portal_account_company_ids($account);
    if(!$companies)return[];
    if(data_is_demo()){
        return array_values(array_filter(data_collection('purchase_orders'),static fn(array $po):bool=>(int)$po['supplier_id']===$supplierId&&in_array((int)$po['company_id'],$companies,true)&&!in_array((string)$po['status'],['draft','canceled'],true)));
    }
    $pdo=production_database_connection();if(!$pdo)return[];
    $stmt=$pdo->prepare('SELECT * FROM purchase_orders WHERE supplier_id=? AND company_id IN ('.implode(',',array_fill(0,count($companies),'?')).') AND status NOT IN ("draft","canceled") ORDER BY expected_date,id');
    $stmt->execute([$supplierId,...$companies]);return $stmt->fetchAll();
}

function supplier_portal_account_po(array $account,int $poId): ?array
{
    foreach(supplier_portal_account_purchase_orders($account) as $po)if((int)$po['id']===$poId)return$po;
    return null;
}

function supplier_portal_account_po_lines(array $account,int $poId): array
{
    if(!supplier_portal_account_po($account,$poId))return[];
    $rows=data_collection('purchase_order_lines');
    return array_values(array_filter($rows,static fn(array $line):bool=>(int)$line['purchase_order_id']===$poId));
}

function supplier_portal_account_records(array $account,string $kind): array
{
    $supplierId=(int)$account['supplier_id'];
    $rows=match($kind){
        'po_responses'=>supplier_portal_po_responses($supplierId),'asns'=>supplier_portal_asns($supplierId),
        'invoices'=>supplier_portal_invoice_submissions($supplierId),'documents'=>supplier_portal_documents($supplierId),
        'sourcing'=>supplier_portal_sourcing_submissions($supplierId),'quality'=>supplier_portal_quality_responses($supplierId),
        'messages'=>supplier_portal_messages($supplierId),default=>[]};
    return array_values(array_filter($rows,static fn(array $row):bool=>(int)($row['account_id']??0)===(int)$account['id']||$kind==='messages'));
}
