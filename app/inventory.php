<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/agent_context.php';
require_permission('inventory.view');

$allSnapshots = data_visible_collection('inventory_snapshots');
$aging = data_visible_collection('inventory_aging');
$locations = data_visible_collection('inventory_locations');
$items = data_visible_collection('items');
$purchaseOrders = data_visible_collection('purchase_orders');
$purchaseOrderLines = data_collection('purchase_order_lines');
$companies = data_permitted_companies();

$agingMap = [];
foreach ($aging as $row) $agingMap[(int)$row['company_id'].':'.(int)$row['item_id']] = $row;
$locationMap = [];
foreach ($locations as $row) $locationMap[(int)$row['id']] = $row;
$itemMap = [];
foreach ($items as $row) $itemMap[(int)$row['id']] = $row;

$search = strtolower(trim(query_string('q')));
$classification = query_string('classification');
$availability = query_string('availability');
$snapshots = array_values(array_filter($allSnapshots, static function (array $row) use ($search, $classification, $availability, $agingMap, $locationMap, $itemMap): bool {
    $item = $itemMap[(int)($row['item_id'] ?? 0)] ?? [];
    $location = $locationMap[(int)($row['location_id'] ?? 0)] ?? [];
    $agingRow = $agingMap[(int)($row['company_id'] ?? 0).':'.(int)($row['item_id'] ?? 0)] ?? [];
    if ($search !== '' && !str_contains(strtolower(implode(' ', [
        $item['item_number'] ?? '', $item['sku'] ?? '', $item['description'] ?? '',
        $location['name'] ?? '', $location['code'] ?? '', data_company_name($row['company_id'] ?? null),
    ])), $search)) return false;
    if ($classification !== '' && ($agingRow['classification'] ?? '') !== $classification) return false;
    $available = (float)($row['available'] ?? 0);
    if ($availability === 'available' && $available <= 0) return false;
    if ($availability === 'allocated' && (float)($row['allocated'] ?? 0) <= 0) return false;
    if ($availability === 'unavailable' && $available > 0) return false;
    return true;
}));
$pagination = paginate_records($snapshots, query_int('page', 1), 10);
$totalValue = array_sum(array_column($allSnapshots, 'value'));
$allocated = array_sum(array_column($allSnapshots, 'allocated'));
$available = array_sum(array_column($allSnapshots, 'available'));

$visiblePoIds = array_map('intval', array_column($purchaseOrders, 'id'));
$visibleLines = array_values(array_filter($purchaseOrderLines, static fn(array $line): bool => in_array((int)($line['purchase_order_id'] ?? 0), $visiblePoIds, true)));
$transferCandidates = gruber_agent_build_transfer_candidates($purchaseOrders, $visibleLines, $allSnapshots, $items, $companies);
$topTransfer = $transferCandidates[0] ?? null;

render_app_start('Inventory Snapshots','inventory','Inventory position and aging','Review current custody, available and allocated quantities, aging classifications, and evidence-based internal transfer opportunities.','');
?>
<section class="metric-grid metric-grid-4">
    <article class="metric-card"><span>Inventory value</span><strong><?= compact_money($totalValue) ?></strong><small>Latest visible snapshots</small></article>
    <article class="metric-card"><span>Available quantity</span><strong><?= number_format($available) ?></strong><small>Across visible positions</small></article>
    <article class="metric-card"><span>Allocated quantity</span><strong><?= number_format($allocated) ?></strong><small>Reserved to work or demand</small></article>
    <article class="metric-card"><span>Transfer candidates</span><strong><?= count($transferCandidates) ?></strong><small>Requires human validation</small></article>
</section>

<?php if ($topTransfer): ?>
<section class="notice-card opportunity-notice">
    <div><span class="notice-icon">↔</span><div><strong><?= h($topTransfer['source_company']) ?> may cover demand for <?= h($topTransfer['destination_company']) ?></strong><p><?= h($topTransfer['item_number']) ?> · <?= number_format((float)$topTransfer['candidate_quantity'], 2) ?> <?= h($topTransfer['uom']) ?> screened against <?= h($topTransfer['po_number']) ?>. Confirm specification, custody, reservations, condition, freight and required date before changing the purchase order.</p></div></div>
    <a href="<?= h(app_url('agent.php?prompt='.rawurlencode('Review the cross-company transfer candidate for '.$topTransfer['item_number'].' and '.$topTransfer['po_number'].', including evidence and required human checks.'))) ?>">Analyze evidence →</a>
</section>
<?php else: ?>
<section class="notice-card"><div><span class="notice-icon">✓</span><div><strong>No supported transfer candidate is visible</strong><p>The active company scope has no open PO line matched to positive inventory in another permitted company.</p></div></div><a href="<?= h(app_url('purchase-orders.php')) ?>">Review commitments →</a></section>
<?php endif; ?>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Current position</span><h2>Inventory snapshots</h2></div><span class="panel-meta"><?= $pagination['total'] ?> matching positions</span></header>
<form class="filter-bar wide-filter" method="get">
    <label><span>Search</span><input type="search" name="q" value="<?= h(query_string('q')) ?>" placeholder="Item, SKU, location, or company"></label>
    <label><span>Aging class</span><select name="classification"><option value="">All classifications</option><?php foreach(['active','monitor','slow_moving','strategic_legacy'] as $value): ?><option value="<?= h($value) ?>" <?= $classification===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
    <label><span>Position</span><select name="availability"><option value="">All positions</option><option value="available" <?= $availability==='available'?'selected':'' ?>>Available stock</option><option value="allocated" <?= $availability==='allocated'?'selected':'' ?>>Allocated stock</option><option value="unavailable" <?= $availability==='unavailable'?'selected':'' ?>>No available stock</option></select></label>
    <button class="button secondary" type="submit">Apply filters</button><a class="button ghost" href="<?= h(app_url('inventory.php')) ?>">Reset</a>
</form>
<div class="table-wrap"><table class="data-table" data-table>
<caption class="sr-only">Inventory snapshots for <?= h(current_scope_label()) ?></caption>
<thead><tr><th scope="col" data-sort>Company / location</th><th scope="col" data-sort>Item</th><th scope="col" class="numeric" data-sort>On hand</th><th scope="col" class="numeric" data-sort>Allocated</th><th scope="col" class="numeric" data-sort>Available</th><th scope="col" class="numeric" data-sort>Unit cost</th><th scope="col" class="numeric" data-sort>Value</th><th scope="col" data-sort>Aging / snapshot</th></tr></thead>
<tbody>
<?php foreach($pagination['items'] as $row): $item=$itemMap[(int)$row['item_id']]??[]; $location=$locationMap[(int)$row['location_id']]??[]; $agingRow=$agingMap[(int)$row['company_id'].':'.(int)$row['item_id']]??[]; ?>
<tr><td><strong><?= h(data_company_name($row['company_id'])) ?></strong><small><?= h($location['name']??'Unknown location') ?></small></td><td><strong><?= h($item['item_number']??'Unknown') ?></strong><small><?= h($item['description']??'') ?> <?= sample_badge() ?></small></td><td class="numeric"><?= number_format((float)$row['quantity_on_hand'],2) ?></td><td class="numeric"><?= number_format((float)$row['allocated'],2) ?></td><td class="numeric"><strong><?= number_format((float)$row['available'],2) ?></strong></td><td class="numeric"><?= money($row['unit_cost']) ?></td><td class="numeric"><strong><?= money($row['value']) ?></strong></td><td><?= badge($agingRow['classification']??'not_classified') ?><small><?= h(date_us($row['snapshot_date'])) ?></small></td></tr>
<?php endforeach; ?>
<?php if (!$pagination['items']): ?><tr><td colspan="8"><?php render_empty_state('No inventory positions found','Adjust the filters or select another permitted company scope.'); ?></td></tr><?php endif; ?>
</tbody></table></div>
<?= render_pagination($pagination,['q'=>query_string('q'),'classification'=>$classification,'availability'=>$availability]) ?>
</section>

<div class="dashboard-grid">
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Aging analysis</span><h2>Inventory aging</h2></div><span class="panel-meta"><?= count($aging) ?> classifications</span></header><div class="compact-list"><?php foreach($aging as $row): ?><article><div><strong><?= h($itemMap[(int)$row['item_id']]['item_number']??'Unknown item') ?></strong><small><?= h(data_company_name($row['company_id'])) ?> · <?= number_format($row['days_since_use']) ?> days since use</small></div><div class="right-summary"><?= badge($row['classification']) ?><b><?= money($row['value']) ?></b></div></article><?php endforeach; ?><?php if(!$aging): render_empty_state('No aging records','No aging classifications are visible in this scope.'); endif; ?></div></section>
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Custody points</span><h2>Inventory locations</h2></div><span class="panel-meta"><?= count($locations) ?> visible</span></header><div class="compact-list"><?php foreach($locations as $location): ?><article><div><strong><?= h($location['name']) ?></strong><small><?= h($location['code']) ?> · <?= h(status_label($location['type'])) ?></small></div><?= badge($location['status']) ?></article><?php endforeach; ?><?php if(!$locations): render_empty_state('No locations','No inventory locations are visible in this scope.'); endif; ?></div></section>
</div>
<?php render_app_end(); ?>
