<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_permission('platform.view');
if (!demo_mode_active()) {
    flash('info', 'The guided tour is available in Demo Mode.');
    redirect_to(root_url('demo.php'));
}
$steps = app_guided_tour_steps();
$actions = '<a class="button primary" href="'.h($steps[0]['href']).'">Start guided tour</a>';
render_app_start('Guided Demo Tour','tour','Presentation mode','Walk through the connected procurement story—from executive risk and source data to savings realization, approvals, and supervised Agent analysis.',$actions);
?>
<section class="guided-tour-hero panel">
    <div><span class="eyebrow">Nine connected stops</span><h2>Present the platform as one operating system</h2><p>The tour follows a practical executive narrative: identify risk, inspect evidence, find cross-company value, govern the decision, and use the Agent Workspace to prepare the next move.</p></div>
    <a class="button primary" href="<?= h($steps[0]['href']) ?>">Begin with Executive Dashboard →</a>
</section>
<section class="guided-tour-grid">
<?php foreach($steps as $index=>$step): ?>
    <article class="guided-tour-step-card"><span><?= str_pad((string)($index+1),2,'0',STR_PAD_LEFT) ?></span><i><?= h($step['icon']) ?></i><div><small><?= h($step['eyebrow']) ?></small><h3><?= h($step['title']) ?></h3><p><?= h($step['description']) ?></p></div><a href="<?= h($step['href']) ?>">Open step →</a></article>
<?php endforeach; ?>
</section>
<section class="panel guided-tour-close"><div><span class="eyebrow">Presenter note</span><h2>Use the System Administrator account for the complete tour</h2><p>Other demo roles intentionally see fewer pages and actions. The tour remains safe: every record is fictional and session-isolated.</p></div><a class="button secondary" href="<?= h(root_url('demo.php')) ?>">Switch demo role</a></section>
<?php render_app_end(); ?>
