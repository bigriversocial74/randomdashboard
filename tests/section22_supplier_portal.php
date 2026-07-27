<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/supplier-portal.php';$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_USER_AGENT']='Section 22 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}require_once $root.'/includes/app/supplier_portal.php';
function s22(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"Section 22 failure: {$message}\n");exit(1);}}

s22(supplier_portal_tables_ready(),'Demo mode must report portal tables ready.');
s22(count(supplier_portal_tables())===13,'Section 22 must govern all thirteen portal tables.');
$metrics=supplier_portal_internal_metrics();s22($metrics['active_accounts']>=1,'Seeded active account is required.');

$invite=supplier_portal_create_invitation(1,1,'Dana Supplier','dana.supplier@example.test','billing',5);
s22(!empty($invite['activation_token']),'Invitation must return the one-time activation token to the authorized inviter.');
s22(!hash_equals($invite['activation_token'],(string)$invite['token_hash']),'Plain invitation token must never be stored.');
s22((int)supplier_portal_invitation_by_token($invite['activation_token'])['id']===(int)$invite['id'],'Hashed invitation lookup must work.');
$activated=supplier_portal_activate_invitation($invite['activation_token'],'SupplierSecure!2026','SupplierSecure!2026');
s22((int)$activated['supplier_id']===1,'Activated account must remain bound to the invited supplier.');
s22(count(supplier_portal_access_grants((int)$activated['id']))>=1,'Activation must create explicit company grants.');
s22(supplier_portal_invitation_by_token($invite['activation_token'])===null,'Accepted invitations must not be reusable.');

$_SESSION['gruber_supplier_account_id']=1;$_SESSION['gruber_supplier_session_started_at']=time();$_SESSION['gruber_supplier_last_activity']=time();
$portalAccount=supplier_portal_current_account();s22($portalAccount!==null,'Seeded portal account must authenticate in isolated demo state.');
$authorizedPos=supplier_portal_account_purchase_orders($portalAccount);s22($authorizedPos!==[],'Portal account must see authorized purchase orders.');
foreach($authorizedPos as $po)s22((int)$po['supplier_id']===1,'Portal account must never see another supplier purchase order.');
s22(supplier_portal_account_po($portalAccount,3)===null,'Cross-supplier purchase order lookup must fail closed.');
s22(!can_use_enterprise_view($portalAccount),'Supplier portal accounts must never receive Enterprise View.');

$po=$authorizedPos[0];$poLines=supplier_portal_account_po_lines($portalAccount,(int)$po['id']);s22($poLines!==[],'Authorized PO lines are required.');$poLine=$poLines[0];
$response=supplier_portal_save_po_response(['response_number'=>supplier_portal_number('POR',supplier_portal_po_responses(1)),'supplier_id'=>1,'purchase_order_id'=>$po['id'],'account_id'=>1,'response_type'=>'accept','proposed_delivery_date'=>date('Y-m-d',strtotime('+3 days')),'proposed_total_amount'=>$po['total_amount'],'currency_code'=>'USD','notes'=>'Accepted with controlled delivery evidence.','evidence_reference'=>'TEST-CONFIRM-1','status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'']);
$reviewed=supplier_portal_review('po_response',(int)$response['id'],'accepted','Internal reviewer verified supplier identity, PO scope, and delivery evidence.');
s22($reviewed['status']==='accepted','PO response must complete internal review.');
s22(!empty(data_find('purchase_orders',(int)$po['id'])['supplier_acknowledged_at']),'Accepted PO response must update canonical acknowledgment only after review.');

$asn=supplier_portal_save_asn(['asn_number'=>'ASN-TEST-22','supplier_id'=>1,'purchase_order_id'=>$po['id'],'account_id'=>1,'ship_date'=>date('Y-m-d'),'estimated_arrival'=>date('Y-m-d',strtotime('+2 days')),'carrier'=>'Test Freight','tracking_number'=>'TRACK-22','package_count'=>2,'pallet_count'=>1,'packing_slip_reference'=>'PS-22','status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'']);
supplier_portal_save_asn_line(['shipment_notice_id'=>$asn['id'],'purchase_order_line_id'=>$poLine['id'],'quantity_shipped'=>1,'lot_or_serial_reference'=>'LOT-22','package_reference'=>'PKG-22','notes'=>'Controlled test shipment.']);
$reviewedAsn=supplier_portal_review('asn',(int)$asn['id'],'accepted','ASN carrier, tracking, line quantity, and arrival date verified.');s22($reviewedAsn['status']==='accepted','ASN must complete review.');
$profile=fulfillment_profile_for_po((int)$po['id']);s22(($profile['shipment_status']??'')==='shipped','Accepted ASN must update canonical fulfillment evidence.');

$invoice=supplier_portal_save_invoice_submission(['submission_number'=>supplier_portal_number('INV-SUB',supplier_portal_invoice_submissions(1)),'supplier_id'=>1,'purchase_order_id'=>$po['id'],'account_id'=>1,'invoice_number'=>'INV-S22-UNIQUE','invoice_date'=>date('Y-m-d'),'due_date'=>date('Y-m-d',strtotime('+30 days')),'currency_code'=>'USD','subtotal'=>189.60,'freight_amount'=>0,'tax_amount'=>0,'total_amount'=>189.60,'document_reference'=>'DOC-S22-INVOICE','status'=>'submitted','duplicate_flag'=>0,'reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'','canonical_invoice_id'=>null]);
supplier_portal_save_invoice_line(['invoice_submission_id'=>$invoice['id'],'purchase_order_line_id'=>$poLine['id'],'description'=>$poLine['description'],'quantity_invoiced'=>1,'unit_price'=>189.60,'tax_amount'=>0,'freight_amount'=>0,'line_total'=>189.60]);
$reviewedInvoice=supplier_portal_review('invoice',(int)$invoice['id'],'accepted','Invoice number, PO line, quantity, price, and document reference verified.');
s22((int)$reviewedInvoice['canonical_invoice_id']>0,'Accepted staged invoice must convert into canonical Section 18 invoice.');
s22(fulfillment_find_invoice((int)$reviewedInvoice['canonical_invoice_id'])!==null,'Canonical invoice must be traceable after conversion.');
s22(supplier_portal_invoice_duplicate(1,'INV-S22-UNIQUE',189.60),'Duplicate detection must include canonical invoices.');

$document=supplier_portal_save_document(['supplier_id'=>1,'account_id'=>1,'entity_type'=>'supplier','entity_id'=>1,'document_type'=>'banking_change_request','title'=>'Banking verification request','document_reference'=>'VERIFY-BANK-22','effective_date'=>null,'expiration_date'=>null,'status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'Verification request only; no credentials stored.']);
s22(supplier_portal_review('document',(int)$document['id'],'changes_requested','Independent call-back verification is required before any master-data change.')['status']==='changes_requested','Sensitive verification request must remain governed.');

$message=supplier_portal_internal_message(1,2,'purchase_order',(int)$po['id'],'Supplier response required','Provide confirmation evidence by the due date.',date('Y-m-d',strtotime('+2 days')));s22(!empty($message['supplier_visible']),'Internal message must be explicitly supplier visible.');
$events=supplier_portal_events(1);s22(count($events)>=6,'Immutable supplier activity history must accumulate events.');

$migration=file_get_contents($root.'/database/20260727_section22_supplier_portal_collaboration_exchange.sql');
foreach(supplier_portal_tables() as $table)s22(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),'Migration must create '.$table.'.');
s22(str_contains($migration,"'5.1-section22'"),'Migration version must be recorded.');
s22(str_contains($migration,'supplier_portal.view'),'Production permission seed is required.');
$export=file_get_contents($root.'/includes/app/supplier_portal_export.php');s22(str_contains($export,"preg_match('/^[=+\\-@]/'"),'CSV formula-injection protection is required.');
$portalAction=file_get_contents($root.'/supplier-portal/action.php');s22(str_contains($portalAction,'supplier_portal_account_po'),'Supplier writes must verify PO tenancy.');s22(str_contains($portalAction,'supplier_portal_invoice_duplicate'),'Invoice duplicate screening is required before acceptance.');
$internalAction=file_get_contents($root.'/app/supplier-portal-action.php');s22(str_contains($internalAction,"require_permission('supplier_portal.review')"),'Internal conversion must require review permission.');

echo "Section 22 supplier portal, tenant isolation, collaboration staging, canonical conversion, and governance tests passed.\n";
