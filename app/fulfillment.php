<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/fulfillment_management.php';
require_permission('purchase_orders.view');

$poId=query_int('id');$selected=$poId?fulfillment_find_order($poId):fulfillment_default_order();
if($poId&&!$selected){flash('error','The purchase order is outside the active company scope.');redirect_to(app_url('fulfillment.php'));}
$orders=fulfillment_orders();$poLines=$selected?fulfillment_po_lines((int)$selected['id']):[];$receipts=$selected?fulfillment_receipts((int)$selected['id']):[];$receiptLines=$selected?fulfillment_receipt_lines_for_po((int)$selected['id']):[];$invoices=$selected?fulfillment_invoices((int)$selected['id']):[];
$invoiceId=query_int('invoice_id');$invoice=$invoiceId?fulfillment_find_invoice($invoiceId):($selected?fulfillment_default_invoice((int)$selected['id']):null);if($invoice&&$selected&&(int)$invoice['purchase_order_id']!==(int)$selected['id'])$invoice=null;
$invoiceLines=$invoice?fulfillment_invoice_lines((int)$invoice['id']):[];$matches=$invoice?fulfillment_matches((int)$invoice['id']):[];$match=$matches[0]??null;$exceptions=$invoice?fulfillment_exceptions((int)$invoice['id']):[];$events=$selected?fulfillment_events((int)$selected['id']):[];$metrics=$selected?fulfillment_metrics($selected,$poLines,$receipts,$receiptLines,$invoices):[];
if(query_string('export')==='csv'&&$selected)fulfillment_export_csv($selected,$metrics,$receipts,$receiptLines,$invoices,$events);
$agentPrompt='Review PO fulfillment, receiving, invoice match, and exception governance for '.($selected['po_number']??'the selected purchase order').'. Evaluate supplier acknowledgment, shipment status, expected date, receipts, accepted and rejected quantities, inventory posting, quality holds, contract pricing, invoice quantities, price, freight, tax, duplicate risk, remaining PO balance, match tolerance, exceptions, supplier credits, approval evidence, and payment-release readiness.';
$headerActions='<a class="button ghost" href="'.h(app_url('purchase-orders.php')).'">Purchase Orders</a><a class="button ghost" href="'.h(app_url('demand.php')).'">Demand</a><a class="button ghost" href="'.h(app_url('inventory.php')).'">Inventory</a><a class="button ghost" href="'.h(app_url('inventory-operations.php')).'">Inventory Operations</a>';
if(can('supplier_portal.view'))$headerActions.='<a class="button ghost" href="'.h(app_url('supplier-portal.php')).'">Supplier Portal</a>';
if(can('accounts_payable.view'))$headerActions.='<a class="button ghost" href="'.h(app_url('accounts-payable.php')).'">Accounts Payable</a>';
if($selected&&can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('fulfillment.php?id='.(int)$selected['id'].'&export=csv')).'">Export Fulfillment</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('PO Fulfillment, Receiving, Invoice Match & Exception Governance','fulfillment','Post-commitment financial and operational control','Track supplier acknowledgment, delivery, receiving, quality acceptance, inventory posting, invoice matching, exception resolution, and payment release.',$headerActions);
require dirname(__DIR__) . '/includes/app/fulfillment_view_styles.php';
require dirname(__DIR__) . '/includes/app/fulfillment_view.php';
render_app_end();
