<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_permission('discovery.view');

$assignments = data_visible_collection('discovery_assignments');
$exceptions = data_visible_collection('data_quality_exceptions');
$imports = data_visible_collection('import_jobs');
$moduleCards = [
    ['label'=>'Discovery assignments','value'=>count($assignments),'detail'=>count(array_filter($assignments,fn($a)=>$a['status']==='submitted')).' submitted','href'=>'discovery.php','icon'=>'◫'],
    ['label'=>'Data-quality exceptions','value'=>count($exceptions),'detail'=>count(array_filter($exceptions,fn($e)=>in_array($e['severity'],['high','critical'],true))).' high priority','href'=>'#exceptions','icon'=>'!'],
    ['label'=>'Import jobs','value'=>count($imports),'detail'=>count(array_filter($imports,fn($i)=>$i['status']==='validation_errors')).' with errors','href'=>'imports.php','icon'=>'⇪'],
    ['label'=>'Workflow approvals','value'=>count(data_visible_collection('workflow_approvals')),'detail'=>data_dashboard_metrics()['pending_approvals'].' pending','href'=>'approvals.php','icon'=>'✓'],
];
$actions = can('discovery.create') ? '<a class="button primary" href="'.h(app_url('discovery.php')).'">Create Assignment</a>' : '';
render_app_start('Data Collection Hub','data','Source onboarding and governance','Coordinate company discovery, imports, validation errors, data ownership, and completion tracking.',$actions);
?>
<section class="module-card-grid">
<?php foreach($moduleCards as $card): ?>
    <a class="module-card" href="<?= h(str_starts_with($card['href'],'#')?$card['href']:app_url($card['href'])) ?>"><i><?= h($card['icon']) ?></i><span><?= h($card['label']) ?></span><strong><?= number_format($card['value']) ?></strong><small><?= h($card['detail']) ?></small><b>Open →</b></a>
<?php endforeach; ?>
</section>

<div class="dashboard-grid two-thirds">
<section class="panel">
    <header class="panel-head"><div><span class="eyebrow">30-day discovery</span><h2>Company collection progress</h2></div><a href="<?= h(app_url('discovery.php')) ?>">Manage assignments</a></header>
    <div class="assignment-list">
    <?php foreach($assignments as $assignment): ?>
        <article>
            <div class="assignment-main"><span class="priority-dot <?= h(status_class($assignment['priority'])) ?>"></span><div><strong><?= h($assignment['title']) ?></strong><small><?= h(data_company_name($assignment['company_id'])) ?> · Owner: <?= h(data_user_name($assignment['owner_id'])) ?> · Due <?= h(date_us($assignment['due_date'])) ?></small></div></div>
            <div class="assignment-progress"><div class="progress-track"><i style="width:<?= (int)$assignment['completion'] ?>%"></i></div><b><?= (int)$assignment['completion'] ?>%</b></div>
            <?= badge($assignment['status']) ?>
        </article>
    <?php endforeach; ?>
    </div>
</section>
<section class="panel">
    <header class="panel-head"><div><span class="eyebrow">Source activity</span><h2>Recent import jobs</h2></div><a href="<?= h(app_url('imports.php')) ?>">Open imports</a></header>
    <div class="compact-list">
    <?php foreach(array_slice($imports,0,5) as $job): ?>
        <article><div><strong><?= h($job['name']) ?></strong><small><?= h($job['file_name']) ?> · <?= number_format($job['rows_total']) ?> rows</small></div><?= badge($job['status']) ?></article>
    <?php endforeach; ?>
    </div>
</section>
</div>

<section class="panel" id="exceptions">
<header class="panel-head"><div><span class="eyebrow">Validation queue</span><h2>Data-quality exceptions</h2></div><span class="panel-meta"><?= count($exceptions) ?> visible issues</span></header>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Severity</th><th>Issue</th><th>Company</th><th>Module</th><th>Owner</th><th>Status</th><th>Created</th></tr></thead>
<tbody><?php foreach($exceptions as $exception): ?><tr><td><?= badge($exception['severity']) ?></td><td><strong><?= h($exception['issue']) ?></strong><?= sample_badge() ?></td><td><?= h(data_company_name($exception['company_id'])) ?></td><td><?= h($exception['module']) ?></td><td><?= h(data_user_name($exception['owner_id'])) ?></td><td><?= badge($exception['status']) ?></td><td><?= h(date_us($exception['created_at'],true)) ?></td></tr><?php endforeach; ?></tbody>
</table></div>
</section>
<?php render_app_end(); ?>
