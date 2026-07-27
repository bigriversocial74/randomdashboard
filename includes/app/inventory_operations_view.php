<?php
declare(strict_types=1);
$items=data_visible_collection('items');$suppliers=data_visible_collection('suppliers');
?>
<?php if(data_is_production()&&!inventory_ops_tables_ready()): ?>
<section class="notice-card"><div><span class="notice-icon">!</span><div><strong>Section 19 migration required</strong><p>Canonical balances, transactions, and cycle counts remain readable. Production policy, recommendation, reservation, transfer, and governance writes are blocked until the deferred migration is imported.</p></div></div><a href="<?= h(app_url('admin/environment.php')) ?>">Review environment →</a></section>
<?php endif; ?>

<section class="metric-grid inventory-ops-metrics">
<article class="metric-card"><span>Inventory value</span><strong><?= compact_money($metrics['inventory_value']) ?></strong><small>Visible custody positions</small></article>
<article class="metric-card"><span>Available quantity</span><strong><?= number_format($metrics['available_quantity'],2) ?></strong><small>After current allocation</small></article>
<article class="metric-card"><span>Active reservations</span><strong><?= number_format($metrics['active_reservations']) ?></strong><small><?= number_format($metrics['reserved_quantity'],2) ?> units reserved</small></article>
<article class="metric-card"><span>Open transfers</span><strong><?= number_format($metrics['open_transfers']) ?></strong><small>Draft through in transit</small></article>
<article class="metric-card"><span>Replenishment value</span><strong><?= compact_money($metrics['replenishment_value']) ?></strong><small>Open governed recommendations</small></article>
<article class="metric-card"><span>Stockout risks</span><strong><?= number_format($metrics['stockout_risks']) ?></strong><small>Below safety stock</small></article>
<article class="metric-card"><span>Excess exposure</span><strong><?= compact_money($metrics['excess_value']) ?></strong><small>Above policy maximum</small></article>
<article class="metric-card"><span>Open counts</span><strong><?= number_format($metrics['open_counts']) ?></strong><small>Awaiting count or posting</small></article>
</section>

<div class="inventory-ops-grid">
<?php require __DIR__ . '/inventory_operations_view_main.php'; ?>
<?php require __DIR__ . '/inventory_operations_view_aside.php'; ?>
