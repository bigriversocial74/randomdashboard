<?php if(data_is_production()&&!mitigation_tables_ready()): ?><div class="flash warning" role="status"><strong>Production mitigation saving is not active yet.</strong> Import <code>database/20260726_section13_mitigation_action_plans.sql</code> after the Section 11 and Section 12 migrations during the final deployment window. Planning analysis remains available.</div><?php endif; ?>

<section class="mitigation-hero">
    <div>
        <span class="eyebrow">Mitigation Action Plans &amp; Supplier Contingency Management</span>
        <h2><?= h($plan['title']) ?></h2>
        <p><?= h($plan['summary']) ?></p>
        <div class="company-chip-row"><span><?= h(current_scope_label()) ?></span><span><?= h($plan['plan_number']) ?></span><span><?= h(status_label(mitigation_operational_status($plan))) ?></span><span><?= (int)$metrics['action_count'] ?> owned actions</span><span><?= (int)$metrics['overdue_count'] ?> overdue</span></div>
    </div>
    <div class="mitigation-risk <?= h(status_class($metrics['residual_risk_level'])) ?>"><span>Residual risk</span><strong><?= number_format((float)$metrics['residual_risk_score'],1) ?></strong><small>Reduced from <?= number_format((float)$metrics['source_risk_score'],1) ?> · <?= h(status_label($metrics['residual_risk_level'])) ?></small></div>
</section>

<section class="mitigation-metrics" aria-label="Mitigation plan metrics">
    <article class="mitigation-metric"><span>Contingency coverage</span><strong><?= number_format((float)$metrics['coverage_pct'],1) ?>%</strong><small>Recovery value vs. modeled exposure</small></article>
    <article class="mitigation-metric"><span>Action readiness</span><strong><?= number_format((float)$metrics['readiness_pct'],1) ?>%</strong><small>Average operational readiness</small></article>
    <article class="mitigation-metric"><span>Execution</span><strong><?= number_format((float)$metrics['execution_pct'],1) ?>%</strong><small><?= (int)$metrics['completed_count'] ?> completed actions</small></article>
    <article class="mitigation-metric"><span>Estimated cost</span><strong><?= compact_money($metrics['estimated_cost']) ?></strong><small>Planned mitigation spend</small></article>
    <article class="mitigation-metric"><span>Recovery value</span><strong><?= compact_money($metrics['recovery_value']) ?></strong><small>Modeled cost/service recovery</small></article>
    <article class="mitigation-metric"><span>Blocked actions</span><strong><?= (int)$metrics['blocked_count'] ?></strong><small>Escalated to plan ownership</small></article>
</section>

<section class="panel">
    <header class="panel-head"><div><span class="eyebrow">Contingency trigger</span><h2>Activation conditions and source exposure</h2><p>The plan remains governed until its trigger or an executive decision activates execution.</p></div><span class="panel-meta">Source scenario <?= h($scenario['scenario_number']??'Live simulation') ?></span></header>
    <div class="trigger-grid">
        <article class="trigger-card"><span>Trigger rule</span><strong><?= h(status_label($plan['trigger_type'])) ?> <?= h($plan['trigger_operator']) ?> <?= number_format((float)$plan['trigger_value'],1) ?></strong><small>Explicit activation threshold</small></article>
        <article class="trigger-card"><span>Source risk</span><strong><?= number_format((float)$plan['source_risk_score'],1) ?> · <?= h(status_label($plan['risk_level'])) ?></strong><small>Scenario risk entering mitigation</small></article>
        <article class="trigger-card"><span>Net exposure</span><strong><?= compact_money($plan['source_net_impact']) ?></strong><small>Expected scenario impact</small></article>
        <article class="trigger-card"><span>Supplier / category</span><strong><?= h($supplier['name']??($category['name']??'Enterprise scope')) ?></strong><small><?= count($simulation['critical_items']) ?> critical items · <?= count($simulation['alternatives']) ?> alternate pathways</small></article>
    </div>
</section>

<div class="mitigation-layout">
    <main>
        <section class="panel">
            <header class="panel-head"><div><span class="eyebrow">Action ownership</span><h2>Mitigation execution board</h2><p>Every action has an owner, priority, due date, readiness level, cost, recovery value, service-risk reduction, and evidence note.</p></div><span class="panel-meta"><?= (int)$metrics['action_count'] ?> actions</span></header>
            <?php foreach($actions as $row): ?>
                <article class="mitigation-action-card">
                    <div class="mitigation-action-head"><div><div class="action-meta"><span><?= h(status_label($row['action_type'])) ?></span><span><?= h(status_label($row['priority'])) ?> priority</span><?= badge((string)$row['status']) ?></div><h3><?= h($row['title']) ?></h3><p><?= h($row['notes']) ?></p></div><strong>#<?= (int)($row['sequence_no']??0) ?></strong></div>
                    <div class="action-kpis"><div><span>Due date</span><strong><?= h(date_us($row['due_date'])) ?></strong></div><div><span>Estimated cost</span><strong><?= compact_money($row['estimated_cost']) ?></strong></div><div><span>Recovery</span><strong><?= compact_money($row['recovery_value']) ?></strong></div><div><span>Risk reduction</span><strong><?= number_format((float)$row['service_risk_reduction'],1) ?> pts</strong></div></div>
                    <div class="progress-track" aria-label="<?= h($row['title']) ?> readiness"><span style="width:<?= max(0,min(100,(float)$row['readiness_pct'])) ?>%"></span></div><small><?= number_format((float)$row['readiness_pct'],1) ?>% ready · Owner user #<?= (int)$row['owner_id'] ?></small>
                    <?php if($savedPlan&&can('savings.edit')): ?>
                    <form class="action-update-grid" action="<?= h(app_url('mitigation-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update_action"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <label><span>Status</span><select name="status"><?php foreach(mitigation_action_statuses() as $value): ?><option value="<?= h($value) ?>" <?= $row['status']===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
                        <label><span>Priority</span><select name="priority"><?php foreach(mitigation_priorities() as $value): ?><option value="<?= h($value) ?>" <?= $row['priority']===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
                        <label><span>Owner</span><select name="owner_id"><?php foreach(data_admin_visible_users() as $user): ?><option value="<?= (int)$user['id'] ?>" <?= (int)$row['owner_id']===(int)$user['id']?'selected':'' ?>><?= h($user['name']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Due date</span><input type="date" name="due_date" required value="<?= h($row['due_date']) ?>"></label>
                        <label><span>Readiness %</span><input type="number" name="readiness_pct" min="0" max="100" step="1" value="<?= h((string)$row['readiness_pct']) ?>"></label>
                        <label><span>Execution note</span><textarea name="notes" rows="3"><?= h($row['notes']) ?></textarea></label>
                        <div><button class="mini-button primary" type="submit">Update action</button></div>
                    </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <aside>
        <article class="plan-side-card"><span class="eyebrow">Activation control</span><h2>Governed response</h2><div class="residual-callout"><span>Current plan state</span><strong><?= h(status_label(mitigation_operational_status($plan))) ?></strong></div><p><?= h($plan['activation_notes']) ?></p>
            <?php if($savedPlan): $effectiveStatus=mitigation_operational_status($savedPlan); ?>
                <div class="plan-history-actions">
                <?php if(can('approvals.submit')&&in_array($effectiveStatus,['draft','changes_requested'],true)): ?><form action="<?= h(app_url('mitigation-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="submit"><input type="hidden" name="id" value="<?= (int)$savedPlan['id'] ?>"><button class="button primary" type="submit">Route approval</button></form><?php endif; ?>
                <?php if(can('savings.edit')&&$effectiveStatus==='approved'): ?><form action="<?= h(app_url('mitigation-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="activate"><input type="hidden" name="id" value="<?= (int)$savedPlan['id'] ?>"><button class="button primary" type="submit">Activate plan</button></form><?php endif; ?>
                <?php if(can('savings.edit')&&$effectiveStatus==='active'): ?><form action="<?= h(app_url('mitigation-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="contain"><input type="hidden" name="id" value="<?= (int)$savedPlan['id'] ?>"><button class="button secondary" type="submit">Mark risk contained</button></form><?php endif; ?>
                </div>
            <?php else: ?><p>Save the blueprint before routing approval or activating the contingency response.</p><?php endif; ?>
        </article>

        <article class="plan-side-card"><span class="eyebrow">Saved plans</span><h2>Contingency history</h2><?php foreach(mitigation_records() as $record): $recordActions=mitigation_actions((int)$record['id']);$recordMetrics=mitigation_calculate_metrics($record,$recordActions); ?><div class="plan-history-row"><strong><?= h($record['title']) ?></strong><small><?= h($record['plan_number']) ?> · <?= count($recordActions) ?> actions · <?= number_format((float)$recordMetrics['coverage_pct'],1) ?>% coverage</small><div class="plan-history-actions"><?= badge(mitigation_operational_status($record)) ?><a href="<?= h(app_url('mitigations.php?id='.(int)$record['id'])) ?>">Open plan</a></div></div><?php endforeach; ?><?php if(!mitigation_records())render_empty_state('No saved mitigation plans','Build the first governed contingency plan from a procurement scenario.'); ?></article>
    </aside>
</div>

<?php if(can('savings.edit')): ?>
<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Plan builder</span><h2><?= $savedPlan?'Update mitigation plan':'Create mitigation plan' ?></h2><p>Use the scenario-generated blueprint as a starting point, then refine ownership, cost, recovery, supplier pathways, and activation evidence.</p></div><span class="panel-meta">Transparent calculations · Protected CSV export after save</span></header>
<form action="<?= h(app_url('mitigation-action.php')) ?>" method="post" class="stacked-form"><?= csrf_field() ?><input type="hidden" name="action" value="save_plan"><input type="hidden" name="id" value="<?= (int)($savedPlan['id']??0) ?>">
<div class="form-grid">
<label class="span-2"><span>Plan title</span><input name="title" maxlength="190" required value="<?= h($plan['title']) ?>"></label>
<label><span>Source scenario</span><select name="scenario_id"><option value="0">Live default simulation</option><?php foreach(scenario_records() as $record): ?><option value="<?= (int)$record['id'] ?>" <?= (int)($plan['scenario_id']??0)===(int)$record['id']?'selected':'' ?>><?= h($record['scenario_number'].' · '.$record['title']) ?></option><?php endforeach; ?></select></label>
<label><span>Trigger type</span><select name="trigger_type"><?php foreach(mitigation_trigger_types() as $value=>$label): ?><option value="<?= h($value) ?>" <?= $plan['trigger_type']===$value?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
<label><span>Operator</span><select name="trigger_operator"><?php foreach(['>='=>'>=','>'=>'>','='=>'=','<='=>'<=','<'=>'<'] as $value=>$label): ?><option value="<?= h($value) ?>" <?= $plan['trigger_operator']===$value?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
<label><span>Trigger value</span><input type="number" name="trigger_value" min="0" step="0.1" value="<?= h((string)$plan['trigger_value']) ?>"></label>
<label class="span-2"><span>Plan summary</span><textarea name="summary" rows="4" required><?= h($plan['summary']) ?></textarea></label>
<label class="span-2"><span>Activation notes</span><textarea name="activation_notes" rows="4" required><?= h($plan['activation_notes']) ?></textarea></label>
</div>
<div class="plan-form-actions">
<?php foreach($actions as $index=>$row): ?>
<article class="plan-form-action"><span class="eyebrow">Action <?= $index+1 ?></span><div class="plan-form-action-grid">
<label class="span-2"><span>Action title</span><input name="action_title[]" maxlength="190" required value="<?= h($row['title']) ?>"></label>
<label><span>Action type</span><select name="action_type[]"><?php foreach(mitigation_action_types() as $value=>$label): ?><option value="<?= h($value) ?>" <?= $row['action_type']===$value?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
<label><span>Priority</span><select name="action_priority[]"><?php foreach(mitigation_priorities() as $value): ?><option value="<?= h($value) ?>" <?= $row['priority']===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
<label><span>Status</span><select name="action_status[]"><?php foreach(mitigation_action_statuses() as $value): ?><option value="<?= h($value) ?>" <?= $row['status']===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
<label><span>Owner</span><select name="action_owner_id[]"><?php foreach(data_admin_visible_users() as $user): ?><option value="<?= (int)$user['id'] ?>" <?= (int)$row['owner_id']===(int)$user['id']?'selected':'' ?>><?= h($user['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Due date</span><input type="date" name="action_due_date[]" required value="<?= h($row['due_date']) ?>"></label>
<label><span>Supplier pathway</span><select name="action_supplier_id[]"><option value="0">No supplier dependency</option><?php foreach(data_visible_collection('suppliers') as $supplierRow): ?><option value="<?= (int)$supplierRow['id'] ?>" <?= (int)($row['supplier_id']??0)===(int)$supplierRow['id']?'selected':'' ?>><?= h($supplierRow['name']) ?></option><?php endforeach; ?></select></label>
<label><span>Estimated cost</span><input type="number" name="action_estimated_cost[]" min="0" step="0.01" value="<?= h((string)$row['estimated_cost']) ?>"></label>
<label><span>Recovery value</span><input type="number" name="action_recovery_value[]" min="0" step="0.01" value="<?= h((string)$row['recovery_value']) ?>"></label>
<label><span>Service-risk reduction</span><input type="number" name="action_service_risk_reduction[]" min="0" max="100" step="0.1" value="<?= h((string)$row['service_risk_reduction']) ?>"></label>
<label><span>Readiness %</span><input type="number" name="action_readiness_pct[]" min="0" max="100" step="1" value="<?= h((string)$row['readiness_pct']) ?>"></label>
<label class="span-4"><span>Evidence / execution notes</span><textarea name="action_notes[]" rows="3"><?= h($row['notes']) ?></textarea></label>
</div></article>
<?php endforeach; ?>
</div><div><button class="button primary" type="submit">Save mitigation plan</button></div>
</form>
</section>
<?php endif; ?>
