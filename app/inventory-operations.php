<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/inventory_operations.php';
require_permission('inventory.view');

$positions=inventory_ops_positions();
$locations=inventory_ops_locations();
$policies=inventory_ops_policies();
$recommendations=inventory_ops_recommendations();
$reservations=inventory_ops_reservations();
$transfers=inventory_ops_transfers();
$counts=inventory_ops_cycle_counts();
$transactions=inventory_ops_transactions();
$events=inventory_ops_events();
$metrics=inventory_ops_metrics();

$transferId=query_int('transfer_id');
$selectedTransfer=$transferId?inventory_ops_find_transfer($transferId):($transfers[0]??null);
$transferLines=$selectedTransfer?inventory_ops_transfer_lines((int)$selectedTransfer['id']):[];
$transferEvents=$selectedTransfer?inventory_ops_transfer_events((int)$selectedTransfer['id']):[];
$countId=query_int('count_id');
$selectedCount=$countId?inventory_ops_find_cycle_count($countId):($counts[0]??null);
$countLines=$selectedCount?inventory_ops_cycle_count_lines((int)$selectedCount['id']):[];

if(query_string('export')==='csv')inventory_ops_export_csv($metrics,$positions,$policies,$recommendations,$reservations,$transfers,$counts,$transactions,$events);

$agentPrompt='Review inventory operations, replenishment, reservation, transfer, custody, cycle-count, and controlled-movement governance. Evaluate on-hand and available quantity, project reservations, open purchase orders, demand forecasts, safety stock, reorder points, internal transfer opportunities, supplier lead times, stockout and excess risk, transfer approvals, in-transit custody, cycle-count variances, scrap and adjustment exposure, and recommended actions.';
$headerActions='<a class="button ghost" href="'.h(app_url('inventory.php')).'">Inventory Snapshots</a><a class="button ghost" href="'.h(app_url('demand.php')).'">Demand Governance</a><a class="button ghost" href="'.h(app_url('fulfillment.php')).'">Receiving & Invoice Match</a>';
if(can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('inventory-operations.php?export=csv')).'">Export Operations</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';

render_app_start('Inventory Operations, Replenishment & Transfer Governance','inventory','Post-receipt inventory control','Reserve, transfer, issue, replenish, count, adjust, and verify inventory through governed operational workflows.',$headerActions);
require dirname(__DIR__) . '/includes/app/inventory_operations_view_styles.php';
require dirname(__DIR__) . '/includes/app/inventory_operations_view.php';
render_app_end();
