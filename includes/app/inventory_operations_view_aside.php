<?php
declare(strict_types=1);
?>
<aside class="inventory-ops-aside">
<section class="panel" id="counts">
<header class="panel-head"><div><span class="eyebrow">Inventory assurance</span><h2>Cycle counts</h2></div><span class="panel-meta"><?= count($counts) ?> counts</span></header>
<nav class="inventory-count-list">
<?php foreach($counts as $count): ?><a class="<?= $selectedCount&&(int)$selectedCount['id']===(int)$count['id']?'active':'' ?>" href="<?= h(app_url('inventory-operations.php?count_id='.(int)$count['id'].'#counts')) ?>"><span><strong><?= h($count['count_reference']) ?></strong><small><?= h($locationMap[(int)$count['inventory_location_id']]['name']??('Location #'.$count['inventory_location_id'])) ?> · <?= h(date_us($count['scheduled_date'])) ?></small></span><?= badge($count['status']) ?></a><?php endforeach; ?>
<?php if(!$counts): ?><p>No cycle counts are visible.</p><?php endif; ?>
</nav>
<?php if($selectedCount): $countApproval=inventory_ops_cycle_count_approval((int)$selectedCount['id']); ?>
<div class="inventory-count-detail"><h3><?= h($selectedCount['count_reference']) ?></h3><p><?= h($locationMap[(int)$selectedCount['inventory_location_id']]['name']??'Inventory location') ?> · assigned to <?= h(data_user_name($selectedCount['assigned_to']??null)) ?></p>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Item</th><th class="numeric">System</th><th class="numeric">Counted</th><th class="numeric">Variance</th><th>Reason</th></tr></thead><tbody>
<?php foreach($countLines as $line): $variance=$line['counted_quantity']===null?null:inventory_ops_cycle_count_variance($line); ?><tr><td><?= h($itemLabel((int)$line['item_id'])) ?></td><td class="numeric"><?= number_format((float)$line['system_quantity'],2) ?></td><td class="numeric"><?= $line['counted_quantity']===null?'—':number_format((float)$line['counted_quantity'],2) ?></td><td class="numeric"><?= $variance===null?'—':number_format($variance,2) ?></td><td><?= h($line['variance_reason']??'') ?></td></tr><?php endforeach; ?>
<?php if(!$countLines): ?><tr><td colspan="5">No count lines.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="form-actions">
<?php if(in_array((string)$selectedCount['status'],['planned','open'],true)&&can('approvals.submit')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="submit_cycle_count"><input type="hidden" name="id" value="<?= (int)$selectedCount['id'] ?>"><button class="button secondary" type="submit">Submit Count</button></form><?php endif; ?>
<?php if($countApproval&&$countApproval['status']==='approved'&&can('purchase_orders.approve')): ?><form action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="post_cycle_count"><input type="hidden" name="id" value="<?= (int)$selectedCount['id'] ?>"><input type="hidden" name="evidence_note" value="Approved cycle-count variance posted after independent review."><button class="button primary" type="submit">Post Variance</button></form><?php endif; ?>
</div>
<?php if(can('purchase_orders.create')&&in_array((string)$selectedCount['status'],['planned','open'],true)): ?><details class="governance-form"><summary>Add or update count line</summary><form class="form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_cycle_line"><input type="hidden" name="cycle_count_id" value="<?= (int)$selectedCount['id'] ?>"><label><span>Item</span><select name="item_id" required><?php foreach($items as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['item_number']??$item['sku']??('Item #'.$item['id'])) ?></option><?php endforeach; ?></select></label><label><span>Counted quantity</span><input type="number" min="0" step="0.0001" name="counted_quantity" required></label><label class="full"><span>Variance reason / count evidence</span><textarea name="variance_reason" required>Blind count completed and custody evidence reviewed.</textarea></label><div class="form-actions full"><button class="button secondary" type="submit">Save Count Line</button></div></form></details><?php endif; ?>
</div>
<?php endif; ?>
<?php if(can('purchase_orders.create')): ?><details class="governance-form"><summary>Schedule cycle count</summary><form class="form-grid" action="<?= h(app_url('inventory-operations-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create_cycle_count"><label><span>Inventory location</span><select name="inventory_location_id" required><?php foreach($locations as $location): ?><option value="<?= (int)$location['id'] ?>"><?= h(data_company_name($location['company_id'])) ?> · <?= h($location['name']) ?></option><?php endforeach; ?></select></label><label><span>Scheduled date</span><input type="date" name="scheduled_date" value="<?= h(date('Y-m-d',strtotime('+3 days'))) ?>" required></label><label><span>Assigned user ID</span><input type="number" min="1" name="assigned_to" value="<?= (int)current_user()['id'] ?>" required></label><div class="form-actions full"><button class="button primary" type="submit">Schedule Count</button></div></form></details><?php endif; ?>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Position intelligence</span><h2>Custody positions</h2></div><span class="panel-meta"><?= count($positions) ?> positions</span></header>
<div class="compact-list inventory-position-list">
<?php foreach(array_slice($positions,0,12) as $position): ?><article><div><strong><?= h($itemLabel((int)$position['item_id'])) ?></strong><small><?= h(data_company_name($position['company_id'])) ?> · <?= h($locationLabel((int)$position['inventory_location_id'])) ?></small></div><div class="right-summary"><b><?= number_format((float)$position['available'],2) ?> available</b><small><?= number_format((float)$position['quantity_on_hand'],2) ?> on hand · <?= money((float)$position['value']) ?></small></div></article><?php endforeach; ?>
<?php if(!$positions): render_empty_state('No inventory positions','No balances are visible in the active company scope.'); endif; ?>
</div>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Immutable evidence</span><h2>Operations history</h2></div><span class="panel-meta"><?= count($events) ?> governance events</span></header>
<div class="timeline compact-timeline">
<?php foreach(array_slice($events,0,10) as $event): ?><article><span></span><div><strong><?= h(status_label($event['event_type'])) ?> · <?= h($itemLabel((int)($event['item_id']??0))) ?></strong><small><?= h(date_us($event['created_at'])) ?> · <?= h($event['evidence_note']) ?></small><em><?= h(status_label($event['severity'])) ?> · <?= number_format((float)($event['quantity']??0),2) ?> units · <?= money((float)($event['value_amount']??0)) ?></em></div></article><?php endforeach; ?>
<?php if(!$events): ?><p>No governance events are visible.</p><?php endif; ?>
</div>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Canonical ledger</span><h2>Recent inventory transactions</h2></div><span class="panel-meta"><?= count($transactions) ?> visible</span></header>
<div class="compact-list">
<?php foreach(array_slice($transactions,0,10) as $transaction): ?><article><div><strong><?= h(status_label($transaction['transaction_type'])) ?> · <?= h($itemLabel((int)$transaction['item_id'])) ?></strong><small><?= h(date_us($transaction['performed_at']??null)) ?> · <?= h($transaction['reason_code']??'') ?></small></div><div class="right-summary"><b><?= number_format((float)$transaction['quantity'],2) ?></b><small><?= money((float)$transaction['quantity']*(float)($transaction['unit_cost']??0)) ?></small></div></article><?php endforeach; ?>
<?php if(!$transactions): render_empty_state('No inventory transactions','No canonical inventory movements are visible.'); endif; ?>
</div>
</section>
</aside>
</div>
