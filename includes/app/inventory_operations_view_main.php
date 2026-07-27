<?php
declare(strict_types=1);
$itemMap=[];foreach($items as $row)$itemMap[(int)$row['id']]=$row;
$locationMap=[];foreach($locations as $row)$locationMap[(int)$row['id']]=$row;
$supplierMap=[];foreach($suppliers as $row)$supplierMap[(int)$row['id']]=$row;
$itemLabel=static fn(int $id):string=>(string)($itemMap[$id]['item_number']??$itemMap[$id]['sku']??('Item #'.$id));
$locationLabel=static fn(int $id):string=>(string)($locationMap[$id]['name']??('Location #'.$id));
?>
<main class="inventory-ops-main">
<section class="panel" id="replenishment">
<header class="panel-head"><div><span class="eyebrow">Replenishment governance</span><h2>Policies and recommendations</h2></div><span class="panel-meta"><?= count($policies) ?> policies · <?= count($recommendations) ?> recommendations</span></header>
<div class="inventory-ops-split">
<div>
<h3>Active policies</h3>
<div class="compact-list">
<?php foreach($policies as $policy): $blueprint=inventory_ops_policy_blueprint($policy); ?>
<article>
<div><strong><?= h($policy['policy_number']) ?> · <?= h($itemLabel((int)$policy['item_id'])) ?></strong><small><?= h(data_company_name($policy['company_id'])) ?> · <?= h($locationLabel((int)$policy['inventory_location_id'])) ?> · reorder <?= number_format((float)$policy['reorder_point'],2) ?> · safety <?= number_format((float)$policy['safety_stock'],2) ?></small><p><?= h($policy['evidence_note']) ?></p></div>
<div class="right-summary"><?= badge($policy['status']) ?><b><?= h(status_label($blueprint['recommendation_type'])) ?></b><small><?= number_format((float)$blueprint['recommended_quantity'],2) ?> units · <?= money((float)$blueprint['estimated_value']) ?></small>
<?php if(can('purchase_orders.create')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="generate_recommendation"><input type="hidden" name="policy_id" value="<?= (int)$policy['id'] ?>"><button class="mini-button primary" type="submit">Generate</button></form><?php endif; ?></div>
</article>
<?php endforeach; ?>
<?php if(!$policies): render_empty_state('No replenishment policies','Create a governed minimum, maximum, reorder-point, and safety-stock policy.'); endif; ?>
</div>
</div>
<div>
<h3>Recommendations</h3>
<div class="compact-list">
<?php foreach(array_slice($recommendations,0,8) as $recommendation): $effective=inventory_ops_recommendation_effective_status($recommendation); ?>
<article><div><strong><?= h($recommendation['recommendation_number']) ?> · <?= h($itemLabel((int)$recommendation['item_id'])) ?></strong><small><?= h(status_label($recommendation['recommendation_type'])) ?> · required <?= h(date_us($recommendation['required_date'])) ?> · <?= h(data_company_name($recommendation['company_id'])) ?></small><p><?= h($recommendation['evidence_note']) ?></p></div><div class="right-summary"><?= badge($effective) ?><b><?= number_format((float)$recommendation['recommended_quantity'],2) ?></b><small><?= money((float)$recommendation['estimated_value']) ?></small>
<?php if(in_array($effective,['draft','changes_requested'],true)&&can('approvals.submit')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="submit_recommendation"><input type="hidden" name="id" value="<?= (int)$recommendation['id'] ?>"><button class="mini-button secondary" type="submit">Submit</button></form><?php elseif($effective==='approved'&&can('purchase_orders.create')&&(float)$recommendation['recommended_quantity']>0): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="convert_recommendation"><input type="hidden" name="id" value="<?= (int)$recommendation['id'] ?>"><button class="mini-button primary" type="submit">Convert</button></form><?php endif; ?></div></article>
<?php endforeach; ?>
<?php if(!$recommendations): render_empty_state('No recommendations','Generate recommendations from active replenishment policies.'); endif; ?>
</div>
</div>
</div>
<?php if(can('purchase_orders.create')): ?>
<details class="governance-form"><summary>Create replenishment policy</summary>
<form class="form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_policy">
<label><span>Company</span><select name="company_id" required><?php foreach(data_permitted_companies() as $company): ?><option value="<?= (int)$company['id'] ?>" <?= (int)$company['id']===inventory_ops_default_company_id()?'selected':'' ?>><?= h($company['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Inventory location</span><select name="inventory_location_id" required><?php foreach($locations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= h(data_company_name($location['company_id'])) ?> · <?= h($location['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Item</span><select name="item_id" required><?php foreach($items as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['item_number']??$item['sku']??('Item #'.$item['id'])) ?> · <?= h($item['description']??$item['normalized_description']??'') ?></option><?php endforeach; ?></select></label>
<label><span>Minimum quantity</span><input type="number" min="0" step="0.0001" name="minimum_quantity" value="0" required></label>
<label><span>Maximum quantity</span><input type="number" min="0" step="0.0001" name="maximum_quantity" value="0" required></label>
<label><span>Reorder point</span><input type="number" min="0" step="0.0001" name="reorder_point" value="0" required></label>
<label><span>Safety stock</span><input type="number" min="0" step="0.0001" name="safety_stock" value="0" required></label>
<label><span>Lead time days</span><input type="number" min="1" name="lead_time_days" value="14" required></label>
<label><span>Review frequency days</span><input type="number" min="1" name="review_frequency_days" value="7" required></label>
<label><span>Preferred supplier</span><select name="preferred_supplier_id"><option value="0">No preferred supplier</option><?php foreach($suppliers as $supplier): ?><option value="<?= (int)$supplier['id'] ?>"><?= h($supplier['name']??$supplier['normalized_name']??('Supplier #'.$supplier['id'])) ?></option><?php endforeach; ?></select></label>
<label><span>Owner user ID</span><input type="number" min="1" name="owner_id" value="<?= (int)current_user()['id'] ?>" required></label>
<label><span>Reviewer user ID</span><input type="number" min="1" name="reviewer_id" value="6" required></label>
<label class="full"><span>Policy evidence</span><textarea name="evidence_note" required>Minimum, maximum, reorder point, safety stock, demand, lead time, and supplier evidence reviewed.</textarea></label>
<div class="form-actions full"><button class="button primary" type="submit">Save Policy</button></div>
</form></details>
<?php endif; ?>
</section>

<section class="panel" id="reservations">
<header class="panel-head"><div><span class="eyebrow">Custody and availability</span><h2>Reservations and controlled movements</h2></div><span class="panel-meta"><?= count($reservations) ?> reservations</span></header>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Reservation</th><th>Company / location</th><th>Item</th><th class="numeric">Quantity</th><th>Needed</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach($reservations as $reservation): ?><tr><td><strong><?= h($reservation['reservation_number']) ?></strong><small><?= h($reservation['reference_type']) ?> #<?= h((string)$reservation['reference_id']) ?></small></td><td><?= h(data_company_name($reservation['company_id'])) ?><small><?= h($locationLabel((int)$reservation['inventory_location_id'])) ?></small></td><td><?= h($itemLabel((int)$reservation['item_id'])) ?></td><td class="numeric"><?= number_format((float)$reservation['quantity'],2) ?></td><td><?= h(date_us($reservation['needed_date'])) ?></td><td><?= badge($reservation['status']) ?></td><td><?php if($reservation['status']==='active'&&can('purchase_orders.create')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="release_reservation"><input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>"><input type="hidden" name="evidence_note" value="Authorized reservation release after demand review."><button class="mini-button secondary" type="submit">Release</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$reservations): ?><tr><td colspan="7">No reservations are visible.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if(can('purchase_orders.create')): ?><div class="inventory-ops-form-pair">
<form class="panel inset form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create_reservation"><h3 class="full">Reserve inventory</h3>
<label><span>Location</span><select name="inventory_location_id" required><?php foreach($locations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= h(data_company_name($location['company_id'])) ?> · <?= h($location['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Item</span><select name="item_id" required><?php foreach($items as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['item_number']??$item['sku']??('Item #'.$item['id'])) ?></option><?php endforeach; ?></select></label>
<label><span>Quantity</span><input type="number" min="0.0001" step="0.0001" name="quantity" required></label>
<label><span>Needed date</span><input type="date" name="needed_date" value="<?= h(date('Y-m-d',strtotime('+7 days'))) ?>" required></label>
<label><span>Reference type</span><select name="reference_type"><option value="project">Project</option><option value="work_order">Work order</option><option value="service_commitment">Service commitment</option><option value="customer_order">Customer order</option><option value="other">Other</option></select></label>
<label><span>Reference ID</span><input type="number" min="0" name="reference_id" value="0"></label>
<label class="full"><span>Evidence</span><textarea name="evidence_note" required>Reservation required for approved operational demand and custody control.</textarea></label><div class="form-actions full"><button class="button primary" type="submit">Reserve Inventory</button></div></form>
<form class="panel inset form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="controlled_movement"><h3 class="full">Post controlled movement</h3>
<label><span>Location</span><select name="inventory_location_id" required><?php foreach($locations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= h(data_company_name($location['company_id'])) ?> · <?= h($location['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Item</span><select name="item_id" required><?php foreach($items as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['item_number']??$item['sku']??('Item #'.$item['id'])) ?></option><?php endforeach; ?></select></label>
<label><span>Movement</span><select name="movement_type"><?php foreach(['issue','return_to_stock','return_to_supplier','adjustment','scrap','core_return','donor_harvest','refurbish','sale'] as $type): ?><option value="<?= h($type) ?>"><?= h(status_label($type)) ?></option><?php endforeach; ?></select></label>
<label><span>Quantity</span><input type="number" min="0.0001" step="0.0001" name="quantity" required></label>
<label><span>Project / work order ID</span><input type="number" min="0" name="project_workorder_id" value="0"></label>
<label class="full"><span>Movement evidence</span><textarea name="evidence_note" required>Authorized custody movement with quantity, condition, purpose, and ownership evidence.</textarea></label><div class="form-actions full"><button class="button secondary" type="submit">Post Movement</button></div></form>
</div><?php endif; ?>
</section>

<section class="panel" id="transfers">
<header class="panel-head"><div><span class="eyebrow">Internal supply network</span><h2>Transfer governance</h2></div><span class="panel-meta"><?= count($transfers) ?> transfer requests</span></header>
<div class="inventory-transfer-board">
<nav class="inventory-transfer-list"><?php foreach($transfers as $transfer): ?><a class="<?= $selectedTransfer&&(int)$selectedTransfer['id']===(int)$transfer['id']?'active':'' ?>" href="<?= h(app_url('inventory-operations.php?transfer_id='.(int)$transfer['id'].'#transfers')) ?>"><strong><?= h($transfer['transfer_number']) ?></strong><small><?= h(data_company_name($transfer['source_company_id'])) ?> → <?= h(data_company_name($transfer['destination_company_id'])) ?></small><?= badge(inventory_ops_transfer_effective_status($transfer)) ?></a><?php endforeach; ?><?php if(!$transfers): ?><span>No transfer requests.</span><?php endif; ?></nav>
<div class="inventory-transfer-detail">
<?php if($selectedTransfer): $transferStatus=inventory_ops_transfer_effective_status($selectedTransfer); ?>
<header><div><span class="eyebrow">Selected transfer</span><h3><?= h($selectedTransfer['transfer_number']) ?></h3><p><?= h($locationLabel((int)$selectedTransfer['source_inventory_location_id'])) ?> → <?= h($locationLabel((int)$selectedTransfer['destination_inventory_location_id'])) ?> · required <?= h(date_us($selectedTransfer['required_date'])) ?></p></div><?= badge($transferStatus) ?></header>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Item</th><th class="numeric">Requested</th><th class="numeric">Shipped</th><th class="numeric">Received</th><th>Condition / lot</th></tr></thead><tbody><?php foreach($transferLines as $line): ?><tr><td><?= h($itemLabel((int)$line['item_id'])) ?></td><td class="numeric"><?= number_format((float)$line['quantity_requested'],2) ?></td><td class="numeric"><?= number_format((float)$line['quantity_shipped'],2) ?></td><td class="numeric"><?= number_format((float)$line['quantity_received'],2) ?></td><td><?= badge($line['condition_status']) ?><small><?= h($line['serial_or_lot_reference']??'') ?></small></td></tr><?php endforeach; ?></tbody></table></div>
<div class="form-actions">
<?php if(in_array($transferStatus,['draft','changes_requested'],true)&&can('approvals.submit')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="submit_transfer"><input type="hidden" name="id" value="<?= (int)$selectedTransfer['id'] ?>"><button class="button secondary" type="submit">Submit Transfer</button></form><?php endif; ?>
<?php if($transferStatus==='approved'&&can('purchase_orders.approve')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="dispatch_transfer"><input type="hidden" name="id" value="<?= (int)$selectedTransfer['id'] ?>"><input type="hidden" name="carrier" value="<?= h($selectedTransfer['carrier']?:'Internal fleet') ?>"><input type="hidden" name="tracking_number" value="<?= h($selectedTransfer['tracking_number']?:('TRK-'.$selectedTransfer['transfer_number'])) ?>"><input type="hidden" name="evidence_note" value="Approved transfer dispatched after quantity, condition, custody, and destination validation."><button class="button primary" type="submit">Dispatch</button></form><?php endif; ?>
<?php if(in_array($transferStatus,['in_transit','partially_received'],true)&&can('purchase_orders.approve')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="receive_transfer"><input type="hidden" name="id" value="<?= (int)$selectedTransfer['id'] ?>"><input type="hidden" name="evidence_note" value="Destination confirmed quantity, condition, custody, and receipt evidence."><button class="button primary" type="submit">Receive Transfer</button></form><?php endif; ?>
</div>
<div class="timeline compact-timeline"><?php foreach(array_slice($transferEvents,0,6) as $event): ?><article><span></span><div><strong><?= h(status_label($event['event_type'])) ?></strong><small><?= h(date_us($event['created_at'])) ?> · <?= h($event['evidence_note']) ?></small></div></article><?php endforeach; ?></div>
<?php else: ?><?php render_empty_state('No transfer selected','Create or select an internal transfer request.'); ?><?php endif; ?>
</div></div>
<?php if(can('purchase_orders.create')): ?><details class="governance-form"><summary>Create transfer request</summary><form class="form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_transfer">
<label><span>Source location</span><select name="source_inventory_location_id" required><?php foreach($locations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= h(data_company_name($location['company_id'])) ?> · <?= h($location['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Destination location</span><select name="destination_inventory_location_id" required><?php foreach($locations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= h(data_company_name($location['company_id'])) ?> · <?= h($location['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Required date</span><input type="date" name="required_date" value="<?= h(date('Y-m-d',strtotime('+7 days'))) ?>" required></label><label><span>Freight cost</span><input type="number" min="0" step="0.01" name="freight_cost" value="0"></label>
<label><span>Owner user ID</span><input type="number" min="1" name="owner_id" value="<?= (int)current_user()['id'] ?>"></label><label><span>Reviewer user ID</span><input type="number" min="1" name="reviewer_id" value="6"></label>
<label class="full"><span>Evidence</span><textarea name="evidence_note" required>Internal transfer supported by source availability, destination need, freight, timing, condition, and custody evidence.</textarea></label><div class="form-actions full"><button class="button primary" type="submit">Create Transfer</button></div></form></details>
<?php if($selectedTransfer&&in_array(inventory_ops_transfer_effective_status($selectedTransfer),['draft','changes_requested'],true)): ?><details class="governance-form"><summary>Add line to <?= h($selectedTransfer['transfer_number']) ?></summary><form class="form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_transfer_line"><input type="hidden" name="transfer_request_id" value="<?= (int)$selectedTransfer['id'] ?>"><label><span>Item</span><select name="item_id" required><?php foreach($items as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['item_number']??$item['sku']??('Item #'.$item['id'])) ?></option><?php endforeach; ?></select></label><label><span>Quantity</span><input type="number" min="0.0001" step="0.0001" name="quantity_requested" required></label><label><span>Unit cost</span><input type="number" min="0" step="0.0001" name="unit_cost" required></label><label><span>Serial / lot reference</span><input name="serial_or_lot_reference"></label><label class="full"><span>Line evidence</span><textarea name="notes" required>Specification, condition, quantity, and custody validated before transfer.</textarea></label><div class="form-actions full"><button class="button secondary" type="submit">Add Transfer Line</button></div></form></details><?php endif; ?>
<?php endif; ?>
</section>
</main>
