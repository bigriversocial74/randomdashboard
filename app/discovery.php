<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_permission('discovery.view');

$assignments = data_visible_collection('discovery_assignments');
$responses = data_visible_collection('discovery_responses');
$actions = can('discovery.create') ? '<button class="button primary" type="button" data-modal-open="assignmentModal">Create Assignment</button>' : '';
render_app_start('Company Discovery Tracker','discovery','Operational discovery','Assign source owners, track questionnaire completion, submit evidence, and manage reviews across the six businesses.',$actions);
?>
<section class="metric-grid metric-grid-4">
<article class="metric-card"><span>Assignments</span><strong><?= count($assignments) ?></strong><small>Visible company scope</small></article>
<article class="metric-card"><span>In progress</span><strong><?= count(array_filter($assignments,fn($a)=>$a['status']==='in_progress')) ?></strong><small>Actively collecting</small></article>
<article class="metric-card"><span>Submitted</span><strong><?= count(array_filter($assignments,fn($a)=>$a['status']==='submitted')) ?></strong><small>Awaiting review</small></article>
<article class="metric-card"><span>Average completion</span><strong><?= $assignments ? round(array_sum(array_column($assignments,'completion'))/count($assignments)) : 0 ?>%</strong><small>Sample progress</small></article>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Assignment control</span><h2>Discovery assignments</h2></div><span class="panel-meta"><?= h(current_scope_label()) ?></span></header>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Assignment</th><th>Company</th><th>Owner / reviewer</th><th>Due</th><th>Progress</th><th>Status</th><th>Workflow</th></tr></thead>
<tbody>
<?php foreach($assignments as $assignment): ?>
<tr>
<td><strong><?= h($assignment['title']) ?></strong><small><?= badge($assignment['priority']) ?> <?= sample_badge() ?></small></td>
<td><?= h(data_company_name($assignment['company_id'])) ?></td>
<td><strong><?= h(data_user_name($assignment['owner_id'])) ?></strong><small>Reviewer: <?= h(data_user_name($assignment['reviewer_id'])) ?></small></td>
<td><?= h(date_us($assignment['due_date'])) ?></td>
<td><div class="table-progress"><div class="progress-track"><i style="width:<?= (int)$assignment['completion'] ?>%"></i></div><span><?= (int)$assignment['completion'] ?>%</span></div></td>
<td><?= badge($assignment['status']) ?></td>
<td><?= workflow_actions('discovery_assignments',(int)$assignment['id'],$assignment['status'],'discovery','discovery.php') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Questionnaire evidence</span><h2>Discovery responses</h2></div><span class="panel-meta"><?= count($responses) ?> sample responses</span></header>
<div class="response-grid">
<?php foreach($responses as $response): ?>
<article class="response-card"><div><span><?= h(data_company_name($response['company_id'])) ?></span><?= badge($response['status']) ?></div><h3><?= h($response['question']) ?></h3><p><?= h($response['response']) ?></p><small>Owner: <?= h(data_user_name($response['owner_id'])) ?></small></article>
<?php endforeach; ?>
</div>
</section>

<?php if(can('discovery.create')): ?>
<div class="modal" id="assignmentModal" hidden><div class="modal-backdrop" data-modal-close></div><section class="modal-card" role="dialog" aria-modal="true"><header><div><span class="eyebrow">New discovery record</span><h2>Create discovery assignment</h2></div><button type="button" data-modal-close>×</button></header><form action="<?= h(app_url('action.php')) ?>" method="post" class="modal-body form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="save_discovery"><input type="hidden" name="return_to" value="discovery.php"><label class="span-2"><span>Assignment title</span><input name="title" required placeholder="Example: Supplier source-system inventory"></label><label><span>Company</span><select name="company_id"><?= company_options(current_company_id()==='enterprise'?1:current_company_id()) ?></select></label><label><span>Priority</span><select name="priority"><option>high</option><option selected>medium</option><option>low</option></select></label><label><span>Owner</span><select name="owner_id"><?= user_options(current_user()['id']) ?></select></label><label><span>Reviewer</span><select name="reviewer_id"><?= user_options(6) ?></select></label><label><span>Due date</span><input type="date" name="due_date" value="<?= h(date('Y-m-d',strtotime('+14 days'))) ?>"></label><label><span>Status</span><select name="status"><option value="not_started">Not started</option><option value="in_progress">In progress</option></select></label><label class="span-2"><span>Completion</span><input type="range" min="0" max="100" value="0" name="completion"></label><div class="span-2 modal-actions"><button class="button secondary" type="button" data-modal-close>Cancel</button><button class="button primary" type="submit">Create assignment</button></div></form></section></div>
<?php endif; ?>
<?php render_app_end(); ?>
