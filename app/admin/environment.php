<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/includes/app/bootstrap.php';
require_permission('platform.administer');
$env=environment_snapshot();
$productionReady=config_present() && production_database_available();
$demoAvailable=(bool)(data_settings()['demo_mode_available']??true);
$target=data_is_demo()?'production':'demo';
render_app_start(
    'Environment Status',
    'admin-environment',
    'Environment controls',
    'Inspect runtime health and intentionally select whether the application reads and writes Demo Data or Production Data.',
    '<a class="button secondary" href="'.h(root_url('MANUAL_SETUP.txt')).'" target="_blank" rel="noopener">Manual setup guide</a>'
);
?>
<section class="environment-hero <?= data_is_demo()?'demo':'production' ?>">
    <div>
        <span><?= h(strtoupper($env['mode'])) ?> DATA ENVIRONMENT</span>
        <h2><?= data_is_demo()?'Isolated database-free review':'Persistent production operations' ?></h2>
        <p><?= data_is_demo()?'All displayed records are fictional samples stored in this PHP session.':'Application workflows are connected to the configured MySQL database and persist after logout or server restart.' ?></p>
    </div>
    <strong><?= h($env['database']) ?></strong>
</section>

<section class="panel environment-switch-panel">
    <header class="panel-head">
        <div><span class="eyebrow">Administrative environment control</span><h2>Demo Data / Production Data</h2></div>
        <?= badge(data_environment(), data_is_demo()?'Demo Data':'Production Data') ?>
    </header>
    <div class="environment-toggle-grid">
        <article class="environment-choice <?= data_is_demo()?'active':'' ?>">
            <header><span>DEMO DATA</span><?= data_is_demo()?badge('active','Selected'):badge('available','Available') ?></header>
            <h3>Session-isolated sample records</h3>
            <p>Use the seven sample roles and six fictional company datasets without touching MySQL.</p>
            <ul><li>No production writes</li><li>Resettable review dataset</li><li>Visible SAMPLE indicators</li></ul>
        </article>
        <article class="environment-choice <?= data_is_production()?'active':'' ?> <?= !$productionReady?'disabled':'' ?>">
            <header><span>PRODUCTION DATA</span><?= data_is_production()?badge('active','Selected'):badge($productionReady?'available':'unavailable',$productionReady?'Ready':'Not ready') ?></header>
            <h3>Persistent MySQL records</h3>
            <p>Use authenticated production accounts, company memberships, permission checks, imports, approvals, and immutable audit records.</p>
            <ul><li>Requires config.php</li><li>Requires Version 3 schema</li><li>Requires production authentication</li></ul>
        </article>
    </div>
    <div class="environment-switch-warning">
        <strong>No records are copied between environments.</strong>
        <p>Changing the data source ends the current application identity. Switching to Production Data requires a production administrator login. Type <code>SWITCH</code> to confirm.</p>
    </div>
    <form class="environment-switch-form" action="<?= h(app_url('action.php')) ?>" method="post" data-confirm="Change the application data environment? The current app identity will end.">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="switch_environment">
        <input type="hidden" name="environment" value="<?= h($target) ?>">
        <input type="hidden" name="return_to" value="admin/environment.php">
        <label><span>Confirmation</span><input name="confirmation" autocomplete="off" placeholder="Type SWITCH" required></label>
        <button class="button <?= $target==='production'?'primary':'secondary' ?>" type="submit" <?= ($target==='production'&&!$productionReady)||($target==='demo'&&!$demoAvailable)?'disabled':'' ?>>
            Switch to <?= $target==='production'?'Production Data':'Demo Data' ?>
        </button>
    </form>
    <?php if($target==='production'&&!$productionReady): ?><p class="form-help critical-text">Production Data cannot be selected until config.php exists, MySQL connects, and the Phase 2 SQL is installed.</p><?php elseif($target==='demo'&&!$demoAvailable): ?><p class="form-help critical-text">Demo Mode is disabled in Platform Settings. Enable it before switching environments.</p><?php endif; ?>
</section>

<section class="environment-grid">
<?php foreach(['mode'=>'Environment','database'=>'Database','config'=>'Configuration','schema_version'=>'Schema version','php_version'=>'PHP version','storage_writable'=>'Demo storage','import_storage_writable'=>'Import storage','email_queue'=>'Email queue','maintenance'=>'Scheduled maintenance','setup'=>'Setup status','last_health_check'=>'Last health check'] as $key=>$label):
    $value=$env[$key]??'Unknown'; if(is_bool($value))$value=$value?'Writable':'Not writable'; ?>
    <article><span><?= h($label) ?></span><strong><?= h((string)$value) ?></strong><small><?php switch($key){case'database':echo data_is_demo()?'Bypassed for sample records.':'Live PDO connection check.';break;case'schema_version':echo'Latest applied migration.';break;case'import_storage_writable':echo'Protected CSV/XLSX staging.';break;default:echo'Runtime diagnostic.';} ?></small></article>
<?php endforeach; ?>
</section>

<div class="dashboard-grid">
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Required runtime support</span><h2>PHP extensions</h2></div></header><div class="extension-list"><?php foreach($env['extensions'] as $extension=>$loaded): ?><article><code><?= h($extension) ?></code><?= badge($loaded?'available':'missing') ?></article><?php endforeach; ?></div></section>
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Manual deployment readiness</span><h2>Version 3 resources</h2></div></header><dl class="detail-list">
<div><dt>Production config</dt><dd><code>/config.php</code></dd></div>
<div><dt>Manual fresh schema</dt><dd><code>/database/gruber_ai_procurement_single_install_v3.sql</code></dd></div>
<div><dt>Existing v2 upgrade</dt><dd><code>/database/gruber_ai_procurement_phase2_v3.sql</code></dd></div>
<div><dt>Protected imports</dt><dd><code>/storage/imports/</code></dd></div>
<div><dt>Repository adapter</dt><dd><code>/includes/data/</code></dd></div>
<div><dt>Last health check</dt><dd><?= h(date_us($env['last_health_check'],true)) ?></dd></div>
</dl></section>
</div>

<?php if($env['database_error']): ?><section class="notice-card critical-notice"><div><span class="notice-icon">!</span><div><strong>Latest database connection error</strong><p><?= h($env['database_error']) ?></p></div></div></section><?php endif; ?>
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Environment separation</span><h2>Production safety guarantees</h2></div></header><div class="safety-check-grid">
<article><i>✓</i><div><strong>Explicit switch</strong><p>The administrator must type SWITCH and confirm the data-source change.</p></div></article>
<article><i>✓</i><div><strong>Fresh authentication</strong><p>Production activation redirects to production login instead of impersonating an account.</p></div></article>
<article><i>✓</i><div><strong>No cross-environment copy</strong><p>Sample records are never promoted or inserted into Production Data by the toggle.</p></div></article>
<article><i>✓</i><div><strong>Audited transitions</strong><p>Environment requests, successful logins, failures, and production mutations are recorded.</p></div></article>
</div></section>
<?php render_app_end(); ?>
