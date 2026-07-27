<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/fulfillment.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 18 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/fulfillment_management.php';

$po=fulfillment_default_order();
if(!$po){fwrite(STDERR,"Fulfillment PO seed is unavailable.\n");exit(1);}
$poLines=fulfillment_po_lines((int)$po['id']);
if(!$poLines){fwrite(STDERR,"Fulfillment PO lines are unavailable.\n");exit(1);}
$metrics=fulfillment_metrics($po,$poLines,fulfillment_receipts((int)$po['id']),fulfillment_receipt_lines_for_po((int)$po['id']),fulfillment_invoices((int)$po['id']));
foreach(['po_value','accepted_quantity','acceptance_pct','unreceived_commitment','invoice_hold_value','released_value','open_exception_count','supplier_credit_recovery'] as $field){if(!array_key_exists($field,$metrics)){fwrite(STDERR,"Fulfillment metrics missing {$field}.\n");exit(1);}}

$targetPo=fulfillment_find_order(2);
if(!$targetPo){fwrite(STDERR,"Quality target PO is unavailable.\n");exit(1);}
$targetLine=fulfillment_po_lines(2)[0]??null;
if(!$targetLine){fwrite(STDERR,"Quality target PO line is unavailable.\n");exit(1);}
$profile=fulfillment_save_profile(['id'=>null,'purchase_order_id'=>2,'owner_id'=>3,'reviewer_id'=>6,'shipment_status'=>'in_transit','asn_number'=>'ASN-QUALITY-018','carrier'=>'Quality Carrier','tracking_number'=>'TRACK-018','shipment_reference'=>'Quality shipment reference.','fulfillment_evidence'=>'Quality fulfillment evidence.']);
if((int)$profile['id']<=0||!fulfillment_profile_for_po(2)){fwrite(STDERR,"Fulfillment profile persistence failed.\n");exit(1);}

$beforeSnapshot=null;
foreach(data_collection('inventory_snapshots') as $snapshot){if((int)$snapshot['company_id']===(int)$targetPo['company_id']&&(int)$snapshot['item_id']===(int)$targetLine['item_id']){$beforeSnapshot=$snapshot;break;}}
$beforeQty=(float)($beforeSnapshot['quantity_on_hand']??0);
$receipt=fulfillment_save_receipt(['receipt_number'=>'RCV-QUALITY-018','purchase_order_id'=>2,'location_id'=>1,'received_by'=>1,'received_at'=>date('Y-m-d H:i:s'),'packing_slip_number'=>'PS-QUALITY-018','carrier'=>'Quality Carrier','tracking_number'=>'TRACK-018','notes'=>'Quality receipt evidence.']);
$receiptLine=fulfillment_save_receipt_line(['receipt_id'=>(int)$receipt['id'],'purchase_order_line_id'=>(int)$targetLine['id'],'quantity_received'=>3.0,'quantity_accepted'=>2.0,'quantity_rejected'=>1.0,'condition_status'=>'quality_hold','serial_or_lot_reference'=>'LOT-QUALITY-018','notes'=>'One unit held for quality inspection.']);
fulfillment_post_inventory($targetPo,$targetLine,$receipt,$receiptLine);
fulfillment_record_quality_event($targetPo,$targetLine,$receiptLine);
$afterSnapshot=null;
foreach(data_collection('inventory_snapshots') as $snapshot){if((int)$snapshot['company_id']===(int)$targetPo['company_id']&&(int)$snapshot['item_id']===(int)$targetLine['item_id']){$afterSnapshot=$snapshot;break;}}
if(!$afterSnapshot||abs((float)$afterSnapshot['quantity_on_hand']-($beforeQty+2.0))>.0001){fwrite(STDERR,"Accepted-only inventory posting failed.\n");exit(1);}
if(count($_SESSION['gruber_demo_state']['quality_events']??[])<1){fwrite(STDERR,"Receipt quality-event persistence failed.\n");exit(1);}

$invoice=fulfillment_save_invoice(['id'=>null,'invoice_number'=>'INV-QUALITY-018','company_id'=>$targetPo['company_id'],'supplier_id'=>$targetPo['supplier_id'],'purchase_order_id'=>$targetPo['id'],'contract_id'=>3,'invoice_date'=>date('Y-m-d'),'due_date'=>date('Y-m-d',strtotime('+30 days')),'payment_terms'=>'Net 30','currency_code'=>'USD','subtotal'=>853.50,'freight_amount'=>0,'tax_amount'=>0,'total_amount'=>853.50,'document_path'=>'evidence/invoices/inv-quality-018.pdf','status'=>'received','owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'hold_reason'=>'','duplicate_fingerprint'=>hash('sha256',$targetPo['supplier_id'].'|INV-QUALITY-018|853.50'),'released_at'=>null,'paid_at'=>null]);
if((int)$invoice['id']<=0||!fulfillment_find_invoice((int)$invoice['id'])){fwrite(STDERR,"Supplier invoice persistence failed.\n");exit(1);}
$invoiceLine=fulfillment_save_invoice_line(['id'=>null,'invoice_id'=>$invoice['id'],'purchase_order_line_id'=>$targetLine['id'],'item_id'=>$targetLine['item_id'],'description'=>'Quality matched cable receipt','quantity_invoiced'=>2.0,'unit_price'=>$targetLine['unit_cost'],'line_total'=>853.50,'tax_amount'=>0,'freight_amount'=>0]);
if((int)$invoiceLine['id']<=0||!fulfillment_find_invoice_line((int)$invoiceLine['id'])){fwrite(STDERR,"Supplier invoice-line persistence failed.\n");exit(1);}
$blueprint=fulfillment_match_blueprint($invoice,fulfillment_invoice_lines((int)$invoice['id']),$targetPo,fulfillment_po_lines((int)$targetPo['id']),fulfillment_receipt_lines_for_po((int)$targetPo['id']),100,2);
foreach(['po_value','accepted_receipt_value','invoice_value','quantity_variance','price_variance','freight_variance','tax_variance','contract_variance','unreceived_value','held_rejected_value','remaining_po_balance','duplicate_flag','match_status','match_score','exception_exposure'] as $field){if(!array_key_exists($field,$blueprint)){fwrite(STDERR,"Three-way match missing {$field}.\n");exit(1);}}
if(!in_array($blueprint['match_status'],['matched','within_tolerance'],true)){fwrite(STDERR,"Compliant three-way match did not pass tolerance.\n");exit(1);}
$match=fulfillment_save_match(array_replace($blueprint,['invoice_id'=>$invoice['id'],'purchase_order_id'=>$targetPo['id'],'evidence_note'=>'Quality three-way match evidence.','created_by'=>1]));
if((int)$match['id']<=0||!fulfillment_find_match((int)$match['id'])){fwrite(STDERR,"Match-result persistence failed.\n");exit(1);}
$invoice['status']='approved_for_payment';$invoice['released_at']=date('Y-m-d H:i:s');$invoice=fulfillment_save_invoice($invoice);
if(fulfillment_invoice_effective_status($invoice)!=='approved_for_payment'){fwrite(STDERR,"Payment-release status failed.\n");exit(1);}

$exception=fulfillment_save_exception(['id'=>null,'invoice_id'=>$invoice['id'],'match_result_id'=>$match['id'],'purchase_order_id'=>$targetPo['id'],'exception_type'=>'rejected_quality','severity'=>'high','status'=>'resolved','owner_id'=>3,'reviewer_id'=>6,'due_date'=>date('Y-m-d',strtotime('+2 days')),'disputed_amount'=>426.75,'waiver_reason'=>'','resolution_note'=>'Quality hold credited and excluded from payment.','supplier_credit_amount'=>426.75,'approval_id'=>null]);
if((int)$exception['id']<=0||!fulfillment_find_exception((int)$exception['id'])){fwrite(STDERR,"Invoice-exception persistence failed.\n");exit(1);}
$event=fulfillment_add_event((int)$targetPo['id'],(int)$invoice['id'],'payment_released','supplier_invoice',(int)$invoice['id'],'matching','approved_for_payment','low','Quality event evidence.');
if((int)$event['id']<=0||count(fulfillment_events((int)$targetPo['id']))<1){fwrite(STDERR,"Fulfillment-event persistence failed.\n");exit(1);}
if(!fulfillment_invoice_duplicate(array_replace($invoice,['id'=>0]))){fwrite(STDERR,"Duplicate-invoice detection failed.\n");exit(1);}
if(fulfillment_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)"){fwrite(STDERR,"Fulfillment CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/fulfillment.php');foreach(glob($root.'/includes/app/fulfillment_view*.php') as $viewFile)$page.=file_get_contents($viewFile);
$actionFile=file_get_contents($root.'/app/fulfillment-action.php');foreach(glob($root.'/app/fulfillment_action_*.php') as $handlerFile)$actionFile.=file_get_contents($handlerFile);
$sql=file_get_contents($root.'/database/20260727_section18_fulfillment_receiving_invoice_match.sql');
$poPage=file_get_contents($root.'/app/purchase-orders.php');
$contractPage=file_get_contents($root.'/app/contracts.php');
foreach(['PO Fulfillment, Receiving, Invoice Match & Exception Governance','Supplier acknowledgment & shipment control','Receiving & quality acceptance','Three-way match intelligence','Invoice exception governance','Immutable fulfillment history'] as $needle){if(!str_contains($page,$needle)){fwrite(STDERR,"Fulfillment workspace missing {$needle}.\n");exit(1);}}
foreach(['save_fulfillment','save_receipt','save_invoice','save_invoice_line','run_match','update_exception','request_payment_release','release_payment','mark_paid','workflow_approvals','fulfillment_add_event','inventory.create','purchase_orders.approve'] as $needle){if(!str_contains($actionFile,$needle)){fwrite(STDERR,"Fulfillment handler missing {$needle}.\n");exit(1);}}
foreach(['CREATE TABLE IF NOT EXISTS purchase_order_fulfillment_profiles','CREATE TABLE IF NOT EXISTS supplier_invoices','CREATE TABLE IF NOT EXISTS supplier_invoice_lines','CREATE TABLE IF NOT EXISTS procurement_match_results','CREATE TABLE IF NOT EXISTS procurement_invoice_exceptions','CREATE TABLE IF NOT EXISTS procurement_fulfillment_events','4.7-section18'] as $needle){if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 18 SQL missing {$needle}.\n");exit(1);}}
if(!str_contains($poPage,'fulfillment.php')||!str_contains($contractPage,'fulfillment.php')||!str_contains($page,'inventory.php')){fwrite(STDERR,"Fulfillment workflow handoffs are missing.\n");exit(1);}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 18 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 18 fulfillment, receiving, invoice-match, and exception-governance gates passed.\n");
