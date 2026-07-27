<?php
declare(strict_types=1);

function supplier_portal_apply_invoice_submission(array $submission,string $note): int
{
    require_once __DIR__.'/fulfillment_management.php';
    if(!fulfillment_tables_ready())throw new RuntimeException('Section 18 fulfillment tables are required before an invoice submission can be converted.');
    $po=fulfillment_find_order((int)$submission['purchase_order_id']);if(!$po||(int)$po['supplier_id']!==(int)$submission['supplier_id'])throw new RuntimeException('The invoice submission does not match the canonical purchase order.');
    if(supplier_portal_invoice_duplicate((int)$submission['supplier_id'],(string)$submission['invoice_number'],(float)$submission['total_amount'],(int)$submission['id']))throw new RuntimeException('The invoice number already exists for this supplier.');
    $invoice=fulfillment_save_invoice([
        'invoice_number'=>$submission['invoice_number'],'company_id'=>(int)$po['company_id'],'supplier_id'=>(int)$po['supplier_id'],'purchase_order_id'=>(int)$po['id'],
        'contract_id'=>null,'invoice_date'=>$submission['invoice_date'],'due_date'=>$submission['due_date'],'payment_terms'=>$po['payment_terms']??'',
        'currency_code'=>$submission['currency_code'],'subtotal'=>(float)$submission['subtotal'],'freight_amount'=>(float)$submission['freight_amount'],
        'tax_amount'=>(float)$submission['tax_amount'],'total_amount'=>(float)$submission['total_amount'],'document_path'=>$submission['document_reference'],
        'status'=>'received','owner_id'=>(int)($po['buyer_id']??current_user()['id']),'reviewer_id'=>(int)current_user()['id'],'approval_id'=>null,
        'hold_reason'=>'Supplier portal submission requires three-way match. '.$note,'released_at'=>null,'paid_at'=>null,
    ]);
    foreach(supplier_portal_invoice_lines((int)$submission['id']) as $line){
        $poLine=fulfillment_find_po_line((int)$line['purchase_order_line_id']);if(!$poLine||(int)$poLine['purchase_order_id']!==(int)$po['id'])throw new RuntimeException('An invoice line is not part of the selected purchase order.');
        fulfillment_save_invoice_line(['invoice_id'=>(int)$invoice['id'],'purchase_order_line_id'=>(int)$poLine['id'],'item_id'=>$poLine['item_id']?:null,'description'=>$line['description'],'quantity_invoiced'=>(float)$line['quantity_invoiced'],'unit_price'=>(float)$line['unit_price'],'tax_amount'=>(float)$line['tax_amount'],'freight_amount'=>(float)$line['freight_amount']]);
    }
    fulfillment_add_event((int)$po['id'],(int)$invoice['id'],'supplier_invoice_converted','supplier_invoice',(int)$invoice['id'],null,'received','medium',$note);
    return(int)$invoice['id'];
}

function supplier_portal_review(string $kind,int $id,string $decision,string $note): array
{
    if(!in_array($decision,['accepted','rejected','changes_requested'],true))throw new RuntimeException('Select a valid review decision.');
    if(trim($note)==='')throw new RuntimeException('Review evidence is required.');
    $finder=match($kind){'po_response'=>'supplier_portal_find_po_response','asn'=>'supplier_portal_find_asn','invoice'=>'supplier_portal_find_invoice_submission','document'=>'supplier_portal_find_document','sourcing'=>'supplier_portal_find_sourcing_submission','quality'=>'supplier_portal_find_quality_response',default=>null};
    if(!$finder)throw new RuntimeException('Unknown supplier review type.');
    $record=$finder($id);if(!$record)throw new RuntimeException('The supplier record is outside the active scope.');
    $before=(string)$record['status'];
    if(!in_array($before,['submitted','changes_requested'],true))throw new RuntimeException('Only submitted supplier records can be reviewed.');
    if($decision==='accepted'){
        if($kind==='po_response')supplier_portal_apply_po_response($record,$note);
        if($kind==='asn')supplier_portal_apply_asn($record,$note);
        if($kind==='invoice')$record['canonical_invoice_id']=supplier_portal_apply_invoice_submission($record,$note);
    }
    $record['status']=$decision;$record['reviewed_by']=(int)current_user()['id'];$record['reviewed_at']=date('Y-m-d H:i:s');$record['review_note']=$note;
    $saver=match($kind){'po_response'=>'supplier_portal_save_po_response','asn'=>'supplier_portal_save_asn','invoice'=>'supplier_portal_save_invoice_submission','document'=>'supplier_portal_save_document','sourcing'=>'supplier_portal_save_sourcing_submission','quality'=>'supplier_portal_save_quality_response'};
    $saved=$saver($record);$companyId=isset($record['purchase_order_id'])?supplier_portal_company_for_po((int)$record['purchase_order_id']):null;
    supplier_portal_add_event((int)$record['supplier_id'],$companyId,$kind,(int)$record['id'],'internal_review_completed',$before,$decision,$decision==='rejected'?'high':'medium',$note,'internal',(int)current_user()['id'],(int)($record['account_id']??0)?:null);
    data_add_audit('Supplier Portal','supplier_record_'.$decision,$kind,$id,$before,$saved,$companyId);
    return$saved;
}

function supplier_portal_internal_message(int $supplierId,?int $companyId,string $entityType,int $entityId,string $subject,string $body,?string $requiredDate): array
{
    if(!supplier_portal_supplier_visible($supplierId))throw new RuntimeException('The supplier is outside the active company scope.');
    if(trim($subject)===''||trim($body)==='')throw new RuntimeException('A message subject and body are required.');
    $message=supplier_portal_save_message(['supplier_id'=>$supplierId,'account_id'=>null,'company_id'=>$companyId,'entity_type'=>$entityType,'entity_id'=>$entityId,'direction'=>'internal_to_supplier','subject'=>mb_substr(trim($subject),0,240),'message_body'=>mb_substr(trim($body),0,5000),'supplier_visible'=>1,'required_response_date'=>$requiredDate?:null,'status'=>'open','read_at'=>null,'acknowledged_at'=>null,'created_by_internal'=>(int)current_user()['id']]);
    supplier_portal_add_event($supplierId,$companyId,'message',(int)$message['id'],'message_sent',null,'open','low',$subject,'internal',(int)current_user()['id'],null);
    return$message;
}
