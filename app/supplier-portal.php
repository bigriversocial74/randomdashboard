<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/supplier_portal.php';
require_permission('supplier_portal.view');

if(query_string('export')==='csv')supplier_portal_export_csv();
$suppliers=sort_records(data_visible_collection('suppliers'),'name');
$selectedSupplierId=query_int('supplier_id');if($selectedSupplierId&&!supplier_portal_supplier_visible($selectedSupplierId))$selectedSupplierId=0;
$selectedSupplier=$selectedSupplierId?data_find('suppliers',$selectedSupplierId):($suppliers[0]??null);$selectedSupplierId=(int)($selectedSupplier['id']??0);
$tab=query_string('tab','overview');if(!in_array($tab,['overview','accounts','review','messages','events'],true))$tab='overview';
$metrics=supplier_portal_internal_metrics();$accounts=supplier_portal_accounts();$invitations=supplier_portal_invitations();$contacts=array_values(array_filter(data_collection('supplier_contacts'),static fn(array $c):bool=>(int)$c['supplier_id']===$selectedSupplierId));
$poResponses=supplier_portal_po_responses($selectedSupplierId);$asns=supplier_portal_asns($selectedSupplierId);$invoices=supplier_portal_invoice_submissions($selectedSupplierId);$documents=supplier_portal_documents($selectedSupplierId);$sourcing=supplier_portal_sourcing_submissions($selectedSupplierId);$quality=supplier_portal_quality_responses($selectedSupplierId);$messages=supplier_portal_messages($selectedSupplierId);$events=supplier_portal_events($selectedSupplierId);
$actions='<a class="button ghost" href="'.h(app_url('suppliers.php')).'">Supplier Master</a><a class="button ghost" href="'.h(app_url('fulfillment.php')).'">Fulfillment</a><a class="button ghost" href="'.h(app_url('sourcing.php')).'">Sourcing</a>';
if(demo_mode_active())$actions.='<a class="button secondary" href="'.h(root_url('supplier-portal/login.php?demo=1')).'">Open Supplier Demo</a>';
if(can('supplier_portal.export'))$actions.='<a class="button secondary" href="'.h(app_url('supplier-portal.php?export=csv')).'">Export CSV</a>';
$agentPrompt='Review supplier portal collaboration for '.($selectedSupplier['name']??'the selected supplier').'. Evaluate portal access, PO acknowledgments, proposed changes, advance shipment notices, invoice submissions, document expiration, sourcing proposals, quality responses, overdue messages, internal review evidence, and canonical conversion risks.';
$actions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Supplier Portal & Collaboration','supplier-portal','External supplier collaboration','Govern restricted supplier identities, purchase-order responses, shipment notices, staged invoices, documents, sourcing proposals, quality responses, and supplier-visible communication.',$actions);
?>
<?php require __DIR__.'/supplier-portal-views/summary.php'; require __DIR__.'/supplier-portal-views/'.$tab.'.php'; render_app_end(); ?>
