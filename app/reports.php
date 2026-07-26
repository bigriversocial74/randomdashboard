<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/reporting.php';
require_permission('reports.view');

$definitions = gruber_report_definitions();
$selectedKey = query_string('report', 'executive-summary');
if (!isset($definitions[$selectedKey])) $selectedKey = 'executive-summary';
$report = gruber_report_dataset($selectedKey);
$metrics = data_dashboard_metrics();
$actions = '<button class="button secondary" type="button" data-print-page>Print report</button>';
if (can('reports.export')) {
    $actions .= '<a class="button primary" href="'.h(app_url('report-export.php?report='.rawurlencode($selectedKey))).'">Export CSV</a>';
}
render_app_start('Reports','reports','Approved operational reporting','Open scoped operational reports, inspect the underlying records and export authorized datasets.',$actions);
?>
<section class="metric-grid metric-grid-4">
    <article class="metric-card"><span>Purchasing spend</span><strong><?= compact_money($metrics['spend']) ?></strong><small>Selected scope</small></article>
    <article class="metric-card"><span>Inventory value</span><strong><?= compact_money($metrics['inventory_value']) ?></strong><small>Latest snapshots</small></article>
    <article class="metric-card"><span>Savings pipeline</span><strong><?= compact_money($metrics['savings_pipeline']) ?></strong><small>Annualized</small></article>
    <article class="metric-card"><span>Pending approvals</span><strong><?= number_format((int)$metrics['pending_approvals']) ?></strong><small>Workflow queue</small></article>
</section>

<section class="report-grid" aria-label="Available reports">
<?php foreach ($definitions as $key => $definition): ?>
    <article class="report-card <?= $selectedKey === $key ? 'is-selected' : '' ?>">
        <div><span><?= h($definition['module']) ?></span><h2><?= h($definition['title']) ?></h2><p><?= h($definition['description']) ?></p></div>
        <footer><small><?= h(current_scope_label()) ?> · Live scoped view</small><a class="mini-button primary" href="<?= h(app_url('reports.php?report='.rawurlencode($key).'#reportDetail')) ?>"><?= $selectedKey === $key ? 'Viewing report' : 'Open report' ?> →</a></footer>
    </article>
<?php endforeach; ?>
</section>

<section class="panel report-detail-panel" id="reportDetail">
    <header class="panel-head">
        <div><span class="eyebrow"><?= h($report['module']) ?> · <?= h($report['scope']) ?></span><h2><?= h($report['title']) ?></h2><p><?= h($report['description']) ?></p></div>
        <span class="panel-meta"><?= count($report['rows']) ?> rows · Generated <?= h(date_us($report['generated_at'], true)) ?></span>
    </header>
    <?php if (!$report['rows']): ?>
        <?php render_empty_state('No report rows', 'No records are visible for this report in the current company and permission scope.'); ?>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" data-table>
                <caption class="sr-only"><?= h($report['title']) ?> for <?= h($report['scope']) ?></caption>
                <thead><tr><?php foreach ($report['columns'] as $column): ?><th scope="col" data-sort><?= h($column) ?></th><?php endforeach; ?></tr></thead>
                <tbody><?php foreach ($report['rows'] as $row): ?><tr><?php foreach ($row as $value): ?><td><?= h((string)$value) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php render_app_end(); ?>
