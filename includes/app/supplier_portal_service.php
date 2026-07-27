<?php
declare(strict_types=1);

function supplier_portal_invoice_duplicate(int $supplierId,string $invoiceNumber,float $totalAmount,?int $excludeSubmissionId=null): bool
{
    foreach(supplier_portal_invoice_submissions($supplierId) as $submission){
        if($excludeSubmissionId&&(int)$submission['id']===$excludeSubmissionId)continue;
        if(strtoupper(trim((string)$submission['invoice_number']))===strtoupper(trim($invoiceNumber))&&($submission['status']??'')!=='rejected')return true;
    }
    if(function_exists('fulfillment_invoices'))foreach(fulfillment_invoices() as $invoice){
        if((int)$invoice['supplier_id']===$supplierId&&strtoupper(trim((string)$invoice['invoice_number']))===strtoupper(trim($invoiceNumber))&&($invoice['status']??'')!=='void')return true;
    }
    return false;
}

function supplier_portal_company_for_po(int $poId): ?int
{
    $po=data_find('purchase_orders',$poId);return $po?(int)$po['company_id']:null;
}

function supplier_portal_create_workflow_approval(int $companyId,string $entityType,int $entityId,string $action,string $note): int
{
    $user=current_user();if(!$user)throw new RuntimeException('An internal user is required.');
    $reviewer=(int)($user['id']??1);
    foreach(data_collection('users') as $candidate){if((int)$candidate['id']!==$reviewer&&array_intersect($candidate['role_codes']??[],['reviewer','procurement_manager','company_administrator','system_administrator'])){$reviewer=(int)$candidate['id'];break;}}
    if(data_is_demo()){
        $record=['id'=>data_next_id('workflow_approvals'),'company_id'=>$companyId,'module'=>'supplier_portal','entity_type'=>$entityType,'entity_id'=>$entityId,'title'=>status_label($action),'submitted_by'=>(int)$user['id'],'assigned_to'=>$reviewer,'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+5 days')),'notes'=>$note];
        data_upsert('workflow_approvals',$record);return (int)$record['id'];
    }
    $pdo=production_database_connection();$stmt=$pdo->prepare('INSERT INTO workflow_approvals(company_id,module,entity_type,entity_id,requested_action,status,requested_by,assigned_reviewer_id,decision_note) VALUES(?,?,?,?,?,"pending",?,?,?)');
    $stmt->execute([$companyId,'supplier_portal',$entityType,$entityId,$action,(int)$user['id'],$reviewer,$note]);return(int)$pdo->lastInsertId();
}

function supplier_portal_apply_po_response(array $response,string $note): void
{
    $po=data_find('purchase_orders',(int)$response['purchase_order_id']);
    if(!$po||(int)$po['supplier_id']!==(int)$response['supplier_id'])throw new RuntimeException('The staged response does not match the canonical purchase order.');
    $type=(string)$response['response_type'];
    if($type==='change_request'){
        supplier_portal_create_workflow_approval((int)$po['company_id'],'supplier_purchase_order_response',(int)$response['id'],'review_supplier_change_request',$note);
        return;
    }
    if(data_is_demo()){
        $po['supplier_acknowledged_at']=date('Y-m-d H:i:s');
        if(!empty($response['proposed_delivery_date']))$po['expected_date']=$response['proposed_delivery_date'];
        data_upsert('purchase_orders',$po);
    }else{
        $params=[date('Y-m-d H:i:s')];$sql='UPDATE purchase_orders SET supplier_acknowledged_at=?';
        if(!empty($response['proposed_delivery_date'])){$sql.=',expected_date=?';$params[]=$response['proposed_delivery_date'];}
        $sql.=',updated_at=NOW() WHERE id=? AND supplier_id=?';$params[]=(int)$po['id'];$params[]=(int)$po['supplier_id'];
        production_database_connection()->prepare($sql)->execute($params);
    }
}

function supplier_portal_apply_asn(array $asn,string $note): void
{
    require_once __DIR__.'/fulfillment_management.php';
    if(!fulfillment_tables_ready())throw new RuntimeException('Section 18 fulfillment tables are required before an ASN can be accepted.');
    $po=fulfillment_find_order((int)$asn['purchase_order_id']);if(!$po||(int)$po['supplier_id']!==(int)$asn['supplier_id'])throw new RuntimeException('The ASN does not match the canonical purchase order.');
    $profile=fulfillment_profile_for_po((int)$po['id'])??[];
    $saved=fulfillment_save_profile(array_replace($profile,[
        'purchase_order_id'=>(int)$po['id'],'owner_id'=>(int)($profile['owner_id']??$po['buyer_id']??current_user()['id']),
        'reviewer_id'=>(int)($profile['reviewer_id']??current_user()['id']),'shipment_status'=>'shipped','asn_number'=>$asn['asn_number'],
        'carrier'=>$asn['carrier'],'tracking_number'=>$asn['tracking_number'],'shipment_reference'=>$asn['packing_slip_reference'],
        'fulfillment_evidence'=>$note.' Estimated arrival '.$asn['estimated_arrival'].'.',
    ]));
    fulfillment_add_event((int)$po['id'],null,'supplier_asn_accepted','purchase_order',(int)$po['id'],$profile['shipment_status']??null,'shipped','medium',$note.' ASN '.$saved['asn_number'].'.');
}
