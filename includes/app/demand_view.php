<?php
declare(strict_types=1);
$effectiveStatus=$selected?demand_effective_status($selected):'draft';
?>
<section class="metric-grid metric-grid-4">
<article class="metric-card"><span>Open requested value</span><strong><?= money(array_sum(array_map(static fn(array $request):float=>in_array(demand_effective_status($request),['draft','submitted','pending','in_review','approved','changes_requested'],true)?(float)$request['estimated_total']:0,demand_requests()))) ?></strong><small>Visible pre-commitment demand</small></article>
<article class="metric-card"><span>Budget envelopes</span><strong><?= count($budgets) ?></strong><small><?= money(array_sum(array_column($budgets,'budget_amount'))) ?> governed capacity</small></article>
<article class="metric-card"><span>Unplanned requests</span><strong><?= count(array_filter($requests,static fn(array $request):bool=>!empty($request['unplanned_demand']))) ?></strong><small>Requires demand classification</small></article>
<article class="metric-card"><span>Forecast demand</span><strong><?= money(array_sum(array_column($forecasts,'forecast_value'))) ?></strong><small><?= count($forecasts) ?> active forecast record(s)</small></article>
</section>

<div class="demand-grid">
<div class="demand-stack">
<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Demand intake</span><h2>Purchase requests</h2></div><span class="panel-meta"><?= count($requests) ?> visible</span></header>
<div class="demand-list">
<?php foreach($requests as $request): ?>
<a href="<?= h(app_url('demand.php?id='.(int)$request['id'])) ?>" <?= $selected&&(int)$selected['id']===(int)$request['id']?'aria-current="page"':'' ?>><span><strong><?= h($request['request_number']) ?></strong><small><?= h(data_company_name($request['company_id'])) ?> · <?= h(status_label($request['business_purpose'])) ?> · Required <?= h(date_us($request['required_date'])) ?></small></span><span class="right-summary"><?= badge(demand_effective_status($request)) ?><b><?= money($request['estimated_total']) ?></b></span></a>
<?php endforeach; ?>
<?php if(!$requests): render_empty_state('No purchase requests','Create the first governed demand record.'); endif; ?>
</div>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Request governance</span><h2><?= $selected?h($selected['request_number']):'Create purchase request' ?></h2></div><?= $selected?badge($effectiveStatus):'' ?></header>
<form class="form-grid" action="<?= h(app_url('demand-action.php')) ?>" method="post">
<?= csrf_field() ?><input type="hidden" name="action" value="save_request"><input type="hidden" name="id" value="<?= (int)($selected['id']??0) ?>">
<label><span>Request number</span><input name="request_number" required value="<?= h($selected['request_number']??('REQ-'.date('Y').'-'.str_pad((string)(count($requests)+1),4,'0',STR_PAD_LEFT))) ?>"></label>
<label><span>Company</span><select name="company_id"><?= company_options($selected['company_id']??(current_company_id()==='enterprise'?data_default_company_id(current_user()):current_company_id())) ?></select></label>
<label><span>Business purpose</span><select name="business_purpose"><?php foreach(demand_business_purposes() as $value): ?><option value="<?= h($value) ?>" <?= ($selected['business_purpose']??'inventory_replenishment')===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
<label><span>Required date</span><input type="date" name="required_date" required value="<?= h($selected['required_date']??date('Y-m-d',strtotime('+30 days'))) ?>"></label>
<label><span>Urgency</span><select name="urgency"><?php foreach(demand_urgencies() as $value): ?><option value="<?= h($value) ?>" <?= ($selected['urgency']??'normal')===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
<label><span>Expense classification</span><select name="capex_opex"><?php foreach(demand_capex_opex() as $value): ?><option value="<?= h($value) ?>" <?= ($selected['capex_opex']??'operating_expense')===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label>
<label><span>Owner</span><select name="owner_id"><?= user_options($selected['owner_id']??current_user()['id']) ?></select></label>
<label><span>Reviewer</span><select name="reviewer_id"><?= user_options($selected['reviewer_id']??6) ?></select></label>
<label><span>Budget envelope</span><select name="budget_envelope_id"><option value="">Auto-match</option><?php foreach($budgets as $budget): ?><option value="<?= (int)$budget['id'] ?>" <?= (int)($selected['budget_envelope_id']??0)===(int)$budget['id']?'selected':'' ?>><?= h($budget['budget_number'].' · '.data_company_name($budget['company_id']).' · '.money($budget['budget_amount'])) ?></option><?php endforeach; ?></select></label>
<label><span>Location ID</span><input type="number" min="0" name="location_id" value="<?= h((string)($selected['location_id']??'')) ?>" placeholder="Optional"></label>
<label><span>Department ID</span><input type="number" min="0" name="department_id" value="<?= h((string)($selected['department_id']??'')) ?>" placeholder="Optional"></label>
<label><span>Project / work-order ID</span><input type="number" min="0" name="project_workorder_id" value="<?= h((string)($selected['project_workorder_id']??'')) ?>" placeholder="Optional"></label>
<label class="span-2"><span>Business justification</span><textarea name="justification" rows="3" required><?= h($selected['justification']??'') ?></textarea></label>
<label class="span-2"><span>Governance evidence</span><textarea name="evidence_note" rows="3" required><?= h($selected['evidence_note']??'Validate inventory, budget, contract coverage, supplier performance, required-date risk, duplicate demand, and sourcing alternatives.') ?></textarea></label>
<label class="checkbox-row span-2"><input type="checkbox" name="unplanned_demand" value="1" <?= !empty($selected['unplanned_demand'])?'checked':'' ?>><span>Classify as unplanned demand</span></label>
<div class="span-2 modal-actions"><button class="button primary" type="submit">Save Purchase Request</button></div>
</form>
</section>

<?php if($selected): ?>
<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Line-level demand</span><h2>Purchase request lines</h2></div><span class="panel-meta"><?= count($lines) ?> line(s) · <?= money(demand_request_total($lines)) ?></span></header>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Item / description</th><th>Preferred supplier</th><th class="numeric">Quantity</th><th class="numeric">Unit cost</th><th class="numeric">Line total</th></tr></thead><tbody>
<?php foreach($lines as $line): ?><tr><td><strong><?= h($line['requested_description']) ?></strong><small><?= h($line['specification_notes']??'') ?></small></td><td><?= h(data_supplier_name($line['preferred_supplier_id']??null)) ?></td><td class="numeric"><?= number_format((float)$line['quantity'],2) ?> <?= h($line['unit_of_measure']) ?></td><td class="numeric"><?= money($line['estimated_unit_cost']) ?></td><td class="numeric"><strong><?= money(demand_line_total($line)) ?></strong></td></tr><?php endforeach; ?>
<?php if(!$lines): ?><tr><td colspan="5"><?php render_empty_state('No request lines','Add item-level demand before assessment or approval.'); ?></td></tr><?php endif; ?>
</tbody></table></div>
<?php if(can('purchase_orders.create')&&in_array($effectiveStatus,['draft','changes_requested'],true)): ?>
<form class="form-grid" action="<?= h(app_url('demand-action.php')) ?>" method="post">
<?= csrf_field() ?><input type="hidden" name="action" value="save_line"><input type="hidden" name="purchase_request_id" value="<?= (int)$selected['id'] ?>">
<label><span>Catalog item</span><select name="item_id"><option value="">Non-catalog item</option><?php foreach(data_visible_collection('items') as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['item_number'].' · '.$item['description']) ?></option><?php endforeach; ?></select></label>
<label><span>Preferred supplier</span><select name="preferred_supplier_id"><option value="">Unspecified</option><?= supplier_options(0) ?></select></label>
<label class="span-2"><span>Requested description</span><input name="requested_description" maxlength="500" required></label>
<label><span>Quantity</span><input type="number" min="0.0001" step="0.0001" name="quantity" value="1" required></label>
<label><span>Unit of measure</span><input name="unit_of_measure" value="EA" maxlength="32" required></label>
<label><span>Estimated unit cost</span><input type="number" min="0" step="0.0001" name="estimated_unit_cost" value="0" required></label>
<label class="span-2"><span>Specifications and constraints</span><textarea name="specification_notes" rows="2"></textarea></label>
<div class="span-2 modal-actions"><button class="button primary" type="submit">Add Request Line</button></div>
</form>
<?php endif; ?>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Automated pre-commitment review</span><h2>Demand intelligence</h2></div><span class="panel-meta"><?= $assessment?h($assessment['assessment_number']):'Not assessed' ?></span></header>
<?php if($assessment): ?>
<div class="demand-signal">
<article><span>Assessment score</span><b><?= number_format((float)$assessment['assessment_score'],1) ?>/100</b><small><?= h(status_label($assessment['recommended_action'])) ?></small></article>
<article><span>Required-date risk</span><b><?= h(status_label($assessment['required_date_risk'])) ?></b><small>Supplier risk <?= h(status_label($assessment['supplier_risk'])) ?></small></article>
<article><span>Inventory avoidance</span><b><?= money($assessment['inventory_avoidance_value']) ?></b><small>Open-PO coverage <?= money($assessment['open_po_coverage_value']) ?></small></article>
<article><span>Contract coverage</span><b><?= money($assessment['contract_covered_value']) ?></b><small>Off-contract <?= money($assessment['off_contract_exposure']) ?></small></article>
<article><span>Budget status</span><b><?= h(status_label($assessment['budget_status'])) ?></b><small>Performance <?= number_format((float)$assessment['performance_score'],1) ?></small></article>
<article><span>Consolidation</span><b><?= money($assessment['consolidation_value']) ?></b><small><?= (int)$assessment['duplicate_request_count'] ?> duplicate request signal(s)</small></article>
</div>
<p><?= h($assessment['evidence_note']) ?></p>
<?php else: ?><?php render_empty_state('Assessment required','Run sourcing assessment after request lines are complete.'); ?><?php endif; ?>
<div class="demand-actions">
<?php if(can('purchase_orders.create')&&in_array($effectiveStatus,['draft','changes_requested'],true)): ?><form action="<?= h(app_url('demand-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="assess_request"><input type="hidden" name="id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="evidence_note" value="<?= h($selected['evidence_note']) ?>"><button class="button secondary" type="submit">Run Sourcing Assessment</button></form><?php endif; ?>
<?php if(can('approvals.submit')&&$assessment&&in_array($effectiveStatus,['draft','changes_requested'],true)): ?><form action="<?= h(app_url('demand-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="submit_request"><input type="hidden" name="id" value="<?= (int)$selected['id'] ?>"><button class="button primary" type="submit">Submit for Approval</button></form><?php endif; ?>
<?php if(can('purchase_orders.create')&&$effectiveStatus==='approved'): ?><form action="<?= h(app_url('demand-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="convert_request"><input type="hidden" name="id" value="<?= (int)$selected['id'] ?>"><button class="button primary" type="submit">Convert to Purchase Order</button></form><?php endif; ?>
<?php if(can('purchase_orders.edit')&&!in_array($effectiveStatus,['converted','canceled','rejected'],true)): ?><form action="<?= h(app_url('demand-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel_request"><input type="hidden" name="id" value="<?= (int)$selected['id'] ?>"><input type="hidden" name="evidence_note" value="Request cancelled after governance review."><button class="button ghost" type="submit">Cancel Request</button></form><?php endif; ?>
</div>
</section>
<?php endif; ?>
</div>

<div class="demand-stack">
<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Financial controls</span><h2>Budget governance</h2></div><span class="panel-meta"><?= count($budgets) ?> envelope(s)</span></header>
<?php if($selected): ?><div class="demand-kpis"><div><span>Requested</span><strong><?= money($metrics['requested_value']) ?></strong></div><div><span>Budget remaining</span><strong><?= money($metrics['budget_remaining']) ?></strong></div><div><span>Utilization</span><strong><?= number_format((float)$metrics['budget_utilization_pct'],1) ?>%</strong></div><div><span>Off contract</span><strong><?= money($metrics['off_contract_exposure']) ?></strong></div><div><span>Inventory avoidance</span><strong><?= money($metrics['inventory_avoidance_value']) ?></strong></div><div><span>Cash requirement</span><strong><?= money($metrics['forecast_cash_requirement']) ?></strong></div></div><?php endif; ?>
<div class="compact-list"><?php foreach($budgets as $budget): ?><article><div><strong><?= h($budget['budget_number']) ?></strong><small><?= h(data_company_name($budget['company_id'])) ?> · <?= h(date_us($budget['period_start'])) ?>–<?= h(date_us($budget['period_end'])) ?></small></div><div class="right-summary"><?= badge($budget['status']) ?><b><?= money($budget['budget_amount']) ?></b></div></article><?php endforeach; ?></div>
<?php if(can('purchase_orders.approve')): ?><form class="form-grid" action="<?= h(app_url('demand-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_budget"><label><span>Company</span><select name="company_id"><?= company_options(current_company_id()==='enterprise'?data_default_company_id(current_user()):current_company_id()) ?></select></label><label><span>Category</span><select name="category_id"><option value="">All categories</option><?= category_options(0) ?></select></label><label><span>Period start</span><input type="date" name="period_start" value="<?= h(date('Y-01-01')) ?>" required></label><label><span>Period end</span><input type="date" name="period_end" value="<?= h(date('Y-12-31')) ?>" required></label><label><span>Budget amount</span><input type="number" min="0.01" step="0.01" name="budget_amount" required></label><label><span>Owner</span><select name="owner_id"><?= user_options(current_user()['id']) ?></select></label><label class="span-2"><span>Budget evidence</span><textarea name="evidence_note" rows="2" required></textarea></label><div class="span-2 modal-actions"><button class="button secondary" type="submit">Create Budget Envelope</button></div></form><?php endif; ?>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Forward demand</span><h2>Demand forecasts</h2></div><span class="panel-meta"><?= count($forecasts) ?> active</span></header>
<div class="compact-list"><?php foreach($forecasts as $forecast): ?><article><div><strong><?= h($forecast['forecast_number']) ?></strong><small><?= h(data_company_name($forecast['company_id'])) ?> · <?= h(date_us($forecast['period_start'])) ?>–<?= h(date_us($forecast['period_end'])) ?></small></div><div class="right-summary"><span><?= number_format((float)$forecast['confidence_pct'],0) ?>%</span><b><?= money($forecast['forecast_value']) ?></b></div></article><?php endforeach; ?></div>
<?php if(can('purchase_orders.create')): ?><form class="form-grid" action="<?= h(app_url('demand-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_forecast"><label><span>Company</span><select name="company_id"><?= company_options(current_company_id()==='enterprise'?data_default_company_id(current_user()):current_company_id()) ?></select></label><label><span>Category</span><select name="category_id"><option value="">All categories</option><?= category_options(0) ?></select></label><label><span>Period start</span><input type="date" name="period_start" value="<?= h(date('Y-m-01',strtotime('+1 month'))) ?>" required></label><label><span>Period end</span><input type="date" name="period_end" value="<?= h(date('Y-m-t',strtotime('+3 months'))) ?>" required></label><label><span>Forecast quantity</span><input type="number" min="0" step="0.01" name="forecast_quantity" value="0"></label><label><span>Forecast value</span><input type="number" min="0" step="0.01" name="forecast_value" value="0"></label><label><span>Confidence</span><input type="number" min="0" max="100" step="1" name="confidence_pct" value="70"></label><label><span>Owner</span><select name="owner_id"><?= user_options(current_user()['id']) ?></select></label><label class="span-2"><span>Forecast source evidence</span><textarea name="source_note" rows="2" required></textarea></label><div class="span-2 modal-actions"><button class="button secondary" type="submit">Create Forecast</button></div></form><?php endif; ?>
</section>

<?php if($selected): ?>
<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Audit evidence</span><h2>Immutable request history</h2></div><span class="panel-meta"><?= count($events) ?> event(s)</span></header>
<div class="demand-history"><?php foreach($events as $event): ?><article><strong><?= h(status_label($event['event_type'])) ?></strong><small><?= h(date_us($event['created_at'])) ?> · <?= h(data_user_name($event['created_by'])) ?> · <?= h(status_label($event['severity'])) ?></small><p><?= h($event['evidence_note']) ?></p></article><?php endforeach; ?><?php if(!$events): render_empty_state('No request events','Governance actions will create immutable evidence here.'); endif; ?></div>
</section>
<?php endif; ?>
</div>
</div>
