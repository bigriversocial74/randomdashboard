<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/agent_context.php';
$user = require_app_user();
require_permission('platform.view');

$metrics = data_dashboard_metrics();
$companies = data_permitted_companies();
$visibleSuppliers = data_visible_collection('suppliers');
$visibleItems = data_visible_collection('items');
$visiblePurchaseOrders = data_visible_collection('purchase_orders');
$commitments = data_visible_collection('open_commitments');
usort($commitments, static fn(array $a, array $b): int => strcmp($a['expected_date'], $b['expected_date']));
$savings = sort_records(data_visible_collection('savings_opportunities'), 'annualized_value', 'desc');
$exceptions = data_visible_collection('data_quality_exceptions');
$audit = array_slice(data_visible_collection('audit_events'), 0, 6);
$agentWorkspace = gruber_agent_build_workspace();
$dailyBriefing = $agentWorkspace['briefing'];

$actions = '<a class="button secondary" href="'.h(app_url('briefing.php')).'">Daily Briefing</a>';
$actions .= '<a class="button secondary" href="'.h(app_url('imports.php')).'">Import Data</a>';
if (can('agent.view')) {
    $actions .= '<a class="button primary" href="'.h(app_url('agent.php')).'">Open Agent Workspace</a>';
}
render_app_start('Executive Dashboard','dashboard','Enterprise command center','Purchasing, inventory, supplier, savings, and data-quality visibility for '.current_scope_label().'.',$actions);
?>
<section class="notice-card critical-notice executive-briefing-notice">
    <div><span class="notice-icon">☀</span><div><strong><?= h($dailyBriefing['headline']) ?></strong><p><?= h($dailyBriefing['summary']) ?></p><small><?= h($dailyBriefing['date_label']) ?> · <?= count($dailyBriefing['priorities']) ?> prioritized items</small></div></div>
    <a href="<?= h(app_url('briefing.php')) ?>">Open daily briefing →</a>
</section>

<section class="metric-grid metric-grid-6">
    <article class="metric-card"><span>Visible purchasing spend</span><strong><?= compact_money($metrics['spend']) ?></strong><small>Sample orders in selected scope</small><em>USD</em></article>
    <article class="metric-card"><span>Open commitments</span><strong><?= compact_money($metrics['open_commitments']) ?></strong><small>Open, partial, and past-due POs</small><em><?= count($commitments) ?> orders</em></article>
    <article class="metric-card"><span>Inventory value</span><strong><?= compact_money($metrics['inventory_value']) ?></strong><small>Latest inventory snapshots</small><em><?= count(data_visible_collection('inventory_snapshots')) ?> positions</em></article>
    <article class="metric-card"><span>Savings pipeline</span><strong><?= compact_money($metrics['savings_pipeline']) ?></strong><small>Annualized opportunity value</small><em><?= count($savings) ?> initiatives</em></article>
    <article class="metric-card"><span>Pending approvals</span><strong><?= number_format($metrics['pending_approvals']) ?></strong><small>Awaiting review or validation</small><em>Workflow</em></article>
    <article class="metric-card"><span>Data exceptions</span><strong><?= number_format($metrics['data_exceptions']) ?></strong><small>Open quality issues</small><em>Needs attention</em></article>
</section>

<div class="dashboard-grid two-thirds">
    <section class="panel">
        <header class="panel-head"><div><span class="eyebrow">Company readiness</span><h2>Data completeness by business</h2></div><a href="<?= h(app_url('data-collection.php')) ?>">Open collection hub</a></header>
        <div class="progress-list">
            <?php foreach ($companies as $company): ?>
                <?php if (current_company_id() !== 'enterprise' && (int) current_company_id() !== (int) $company['id']) continue; ?>
                <article>
                    <div><span><strong><?= h($company['code']) ?></strong><?= h($company['name']) ?></span><b><?= (int) $company['completion'] ?>%</b></div>
                    <div class="progress-track"><i style="width:<?= (int) $company['completion'] ?>%"></i></div>
                    <small><?= count_for_company($visibleSuppliers, (int)$company['id']) ?> suppliers · <?= count_for_company($visibleItems, (int)$company['id']) ?> items · <?= count_for_company($visiblePurchaseOrders, (int)$company['id']) ?> purchase orders</small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <header class="panel-head"><div><span class="eyebrow">Opportunity portfolio</span><h2>Top savings initiatives</h2></div><a href="<?= h(app_url('savings.php')) ?>">View pipeline</a></header>
        <div class="ranked-list">
            <?php foreach (array_slice($savings,0,5) as $index=>$opportunity): ?>
                <article><span><?= str_pad((string)($index+1),2,'0',STR_PAD_LEFT) ?></span><div><strong><?= h($opportunity['title']) ?></strong><small><?= h(data_company_name($opportunity['company_id'])) ?> · <?= h($opportunity['category']) ?></small></div><b><?= compact_money($opportunity['annualized_value']) ?></b></article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="panel">
    <header class="panel-head"><div><span class="eyebrow">Commitment control</span><h2>Open purchase-order commitments</h2></div><a href="<?= h(app_url('purchase-orders.php')) ?>">View purchase orders</a></header>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>PO</th><th>Company</th><th>Supplier</th><th>Expected</th><th>Status</th><th class="numeric">Commitment</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($commitments,0,8) as $commitment): ?>
                <tr>
                    <td><strong><?= h($commitment['po_number']) ?></strong><?= sample_badge() ?></td>
                    <td><?= h(data_company_name($commitment['company_id'])) ?></td>
                    <td><?= h(data_supplier_name($commitment['supplier_id'])) ?></td>
                    <td><?= h(date_us($commitment['expected_date'])) ?></td>
                    <td><?= badge($commitment['status']) ?></td>
                    <td class="numeric"><strong><?= money($commitment['amount']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="dashboard-grid">
    <section class="panel">
        <header class="panel-head"><div><span class="eyebrow">Data governance</span><h2>Open data-quality exceptions</h2></div><a href="<?= h(app_url('data-collection.php')) ?>">Review exceptions</a></header>
        <div class="issue-list">
            <?php foreach (array_slice($exceptions,0,5) as $exception): ?>
                <article><i class="<?= h(status_class($exception['severity'])) ?>">!</i><div><strong><?= h($exception['issue']) ?></strong><small><?= h($exception['module']) ?> · <?= h(data_company_name($exception['company_id'])) ?> · Owner: <?= h(data_user_name($exception['owner_id'])) ?></small></div><?= badge($exception['status']) ?></article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel">
        <header class="panel-head"><div><span class="eyebrow">Traceability</span><h2>Recent audit activity</h2></div><?php if(can('audit.view')): ?><a href="<?= h(app_url('admin/audit.php')) ?>">Open audit log</a><?php endif; ?></header>
        <div class="activity-list">
            <?php foreach ($audit as $event): ?>
                <article><span><?= h(initials(data_user_name($event['user_id']))) ?></span><div><strong><?= h(data_user_name($event['user_id'])) ?> · <?= h(status_label($event['action'])) ?></strong><small><?= h($event['module']) ?> / <?= h($event['entity_type']) ?> #<?= h($event['entity_id'] ?? '—') ?></small></div><time><?= h(date_us($event['created_at'],true)) ?></time></article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php render_app_end(); ?>
