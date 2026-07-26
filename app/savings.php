<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_permission('savings.view');

function gruber_savings_stage_definitions(): array
{
    return [
        'draft' => ['label' => 'Identified', 'short' => 'Identify', 'description' => 'Capture the opportunity, baseline, owner, and initial evidence.'],
        'submitted' => ['label' => 'Business case', 'short' => 'Business case', 'description' => 'Validate the baseline, projected value, timing, and implementation requirements.'],
        'review' => ['label' => 'Negotiation', 'short' => 'Negotiate', 'description' => 'Compare options, negotiate terms, and confirm operational feasibility.'],
        'validated' => ['label' => 'Approved', 'short' => 'Approve', 'description' => 'Complete finance and leadership review before implementation.'],
        'approved' => ['label' => 'Realization', 'short' => 'Realize', 'description' => 'Track implementation, accounting validation, and realized value.'],
    ];
}

function gruber_savings_next_stage(string $stage): ?string
{
    return ['draft'=>'submitted','submitted'=>'review','review'=>'validated','validated'=>'approved'][$stage] ?? null;
}

$allOpportunities = sort_records(data_visible_collection('savings_opportunities'), 'annualized_value', 'desc');
$stageDefinitions = gruber_savings_stage_definitions();
$stageFilter = query_string('stage', 'all');
$riskFilter = query_string('risk', 'all');
$search = trim(query_string('q'));

$opportunities = array_values(array_filter($allOpportunities, static function (array $record) use ($stageFilter, $riskFilter, $search): bool {
    if ($stageFilter !== 'all' && (string)($record['stage'] ?? '') !== $stageFilter) return false;
    if ($riskFilter !== 'all' && (string)($record['risk'] ?? 'medium') !== $riskFilter) return false;
    if ($search !== '') {
        $haystack = strtolower(implode(' ', [
            (string)($record['opportunity_number'] ?? ''),
            (string)($record['title'] ?? ''),
            (string)($record['description'] ?? ''),
            (string)($record['category'] ?? ''),
            data_supplier_name((int)($record['supplier_id'] ?? 0)),
            data_company_name($record['company_id'] ?? null),
        ]));
        if (!str_contains($haystack, strtolower($search))) return false;
    }
    return true;
}));

$grossPipeline = array_sum(array_map(static fn(array $o): float => (float)($o['annualized_value'] ?? 0), $allOpportunities));
$weightedForecast = array_sum(array_map(static fn(array $o): float => (float)($o['annualized_value'] ?? 0) * ((int)($o['confidence'] ?? 0) / 100), $allOpportunities));
$realizedSavings = array_sum(array_map(static fn(array $o): float => (float)($o['realized_savings'] ?? 0), $allOpportunities));
$implementationCost = array_sum(array_map(static fn(array $o): float => (float)($o['implementation_cost'] ?? 0), $allOpportunities));
$accountingPending = count(array_filter($allOpportunities, static fn(array $o): bool => in_array((string)($o['accounting_validation'] ?? 'not_requested'), ['pending','not_requested'], true) && in_array((string)($o['stage'] ?? ''), ['validated','approved'], true)));
$highRiskCount = count(array_filter($allOpportunities, static fn(array $o): bool => in_array((string)($o['risk'] ?? ''), ['high','critical'], true)));

$editingId = query_int('edit');
$editingRecord = $editingId ? data_find('savings_opportunities', $editingId) : null;
if ($editingRecord && !data_record_visible($editingRecord)) $editingRecord = null;
$isNew = query_string('new') === '1';
$modalOpen = ($isNew || $editingRecord) && (can('savings.create') || can('savings.edit'));

$defaultCompany = current_company_id() === 'enterprise' ? (int)(current_user()['primary_company_id'] ?? 1) : (int)current_company_id();
$formRecord = $editingRecord ?: [
    'id'=>null,
    'opportunity_number'=>'',
    'company_id'=>query_int('company_id', $defaultCompany),
    'category_id'=>1,
    'supplier_id'=>0,
    'title'=>query_string('title'),
    'description'=>'',
    'opportunity_type'=>'other',
    'owner_id'=>(int)current_user()['id'],
    'stage'=>'draft',
    'operational_status'=>'identified',
    'annualized_value'=>(float)query_string('annualized_value','0'),
    'current_annual_cost'=>0,
    'implementation_cost'=>0,
    'realized_savings'=>0,
    'confidence'=>query_int('confidence',50),
    'risk'=>'medium',
    'due_date'=>date('Y-m-d', strtotime('+30 days')),
    'accounting_validation'=>'not_requested',
    'next_step'=>'',
    'review_status'=>'draft',
];

$actions = '<a class="button secondary" href="'.h(app_url('agent.php?prompt='.rawurlencode('Prioritize the current savings pipeline and identify the next decision for each leading opportunity.'))).'">Ask Savings Agent</a>';
if (can('savings.create')) $actions .= '<a class="button primary" href="'.h(app_url('savings.php?new=1')).'">Create Opportunity</a>';

render_app_start(
    'Savings Opportunity Pipeline',
    'savings',
    'Value realization',
    'Manage procurement value from initial identification through business-case review, negotiation, approval, implementation, and accounting-validated realization.',
    $actions
);
?>
<section class="metric-grid metric-grid-6 savings-metric-grid" data-tour-target="savings-metrics">
    <article class="metric-card"><span>Gross pipeline</span><strong><?= compact_money($grossPipeline) ?></strong><small>Expected annual savings</small><em><?= count($allOpportunities) ?> opportunities</em></article>
    <article class="metric-card"><span>Weighted forecast</span><strong><?= compact_money($weightedForecast) ?></strong><small>Value × confidence</small><em><?= $grossPipeline > 0 ? round(($weightedForecast / $grossPipeline) * 100) : 0 ?>% weighted</em></article>
    <article class="metric-card"><span>Realized savings</span><strong><?= compact_money($realizedSavings) ?></strong><small>Recorded implementation value</small><em><?= $weightedForecast > 0 ? round(($realizedSavings / $weightedForecast) * 100) : 0 ?>% of forecast</em></article>
    <article class="metric-card"><span>Implementation cost</span><strong><?= compact_money($implementationCost) ?></strong><small>One-time investment</small><em><?= compact_money(max(0, $weightedForecast - $implementationCost)) ?> net forecast</em></article>
    <article class="metric-card"><span>Finance review</span><strong><?= number_format($accountingPending) ?></strong><small>Not requested or pending</small><em>Accounting validation</em></article>
    <article class="metric-card"><span>High-risk cases</span><strong><?= number_format($highRiskCount) ?></strong><small>High or critical execution risk</small><em>Leadership attention</em></article>
</section>

<section class="panel savings-portfolio-summary" data-tour-target="savings-portfolio">
    <header class="panel-head"><div><span class="eyebrow">Portfolio health</span><h2>Opportunity progression</h2></div><span class="panel-meta"><?= h(current_scope_label()) ?></span></header>
    <div class="savings-stage-summary">
        <?php foreach ($stageDefinitions as $stage => $definition):
            $stageItems = array_values(array_filter($allOpportunities, static fn(array $o): bool => (string)($o['stage'] ?? 'draft') === $stage));
            $stageValue = array_sum(array_map(static fn(array $o): float => (float)($o['annualized_value'] ?? 0), $stageItems));
            $stageWeighted = array_sum(array_map(static fn(array $o): float => (float)($o['annualized_value'] ?? 0) * ((int)($o['confidence'] ?? 0) / 100), $stageItems));
        ?>
            <a class="savings-stage-stat <?= $stageFilter === $stage ? 'active' : '' ?>" href="?stage=<?= h($stage) ?><?= $riskFilter !== 'all' ? '&risk='.h($riskFilter) : '' ?>">
                <span><?= h($definition['label']) ?></span><strong><?= count($stageItems) ?></strong><b><?= compact_money($stageValue) ?></b><small><?= compact_money($stageWeighted) ?> weighted</small>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel savings-filter-panel">
    <form class="toolbar savings-toolbar" method="get">
        <label class="search-field"><span>Search</span><input type="search" name="q" value="<?= h($search) ?>" placeholder="Opportunity, supplier, company, or number"></label>
        <label><span>Stage</span><select name="stage"><option value="all">All stages</option><?php foreach($stageDefinitions as $stage=>$definition): ?><option value="<?= h($stage) ?>" <?= $stageFilter===$stage?'selected':'' ?>><?= h($definition['label']) ?></option><?php endforeach; ?></select></label>
        <label><span>Risk</span><select name="risk"><option value="all">All risk levels</option><?php foreach(['low','medium','high','critical'] as $risk): ?><option value="<?= h($risk) ?>" <?= $riskFilter===$risk?'selected':'' ?>><?= h(status_label($risk)) ?></option><?php endforeach; ?></select></label>
        <button class="button secondary" type="submit">Apply filters</button>
        <?php if ($stageFilter !== 'all' || $riskFilter !== 'all' || $search !== ''): ?><a class="button ghost" href="savings.php">Clear</a><?php endif; ?>
    </form>
</section>

<section class="savings-pipeline-board" data-tour-target="savings-board">
<?php foreach($stageDefinitions as $stage=>$definition):
    $stageItems = array_values(array_filter($opportunities, static fn(array $o): bool => (string)($o['stage'] ?? 'draft') === $stage));
?>
    <article class="savings-pipeline-column">
        <header><div><span><?= h($definition['label']) ?></span><small><?= h($definition['description']) ?></small></div><b><?= count($stageItems) ?></b></header>
        <div class="savings-column-cards">
        <?php foreach($stageItems as $item):
            $weighted = (float)($item['annualized_value'] ?? 0) * ((int)($item['confidence'] ?? 0) / 100);
            $nextStage = gruber_savings_next_stage((string)($item['stage'] ?? 'draft'));
            $returnTo = 'savings.php' . ($stageFilter !== 'all' ? '?stage='.rawurlencode($stageFilter) : '');
        ?>
            <article class="savings-opportunity-card">
                <div class="savings-card-top"><span><?= h($item['opportunity_number'] ?? ('SAV-'.(int)$item['id'])) ?></span><div><?= badge((string)($item['risk'] ?? 'medium')) ?><?= sample_badge() ?></div></div>
                <h3><?= h($item['title']) ?></h3>
                <p><?= h(data_company_name($item['company_id'] ?? null)) ?><?= !empty($item['supplier_id']) ? ' · '.h(data_supplier_name((int)$item['supplier_id'])) : '' ?></p>
                <div class="savings-value-stack"><strong><?= money((float)$item['annualized_value']) ?></strong><small><?= money($weighted) ?> weighted · <?= (int)$item['confidence'] ?>% confidence</small></div>
                <dl class="savings-card-details">
                    <div><dt>Status</dt><dd><?= h(status_label((string)($item['operational_status'] ?? 'identified'))) ?></dd></div>
                    <div><dt>Owner</dt><dd><?= h(data_user_name($item['owner_id'] ?? null)) ?></dd></div>
                    <div><dt>Target</dt><dd><?= h(date_us($item['due_date'] ?? null)) ?></dd></div>
                    <div><dt>Realized</dt><dd><?= money((float)($item['realized_savings'] ?? 0)) ?></dd></div>
                </dl>
                <?php if (!empty($item['next_step'])): ?><div class="savings-next-step"><span>Next step</span><p><?= h($item['next_step']) ?></p></div><?php endif; ?>
                <footer class="savings-card-actions">
                    <a class="mini-button secondary" href="<?= h(app_url('savings.php?edit='.(int)$item['id'])) ?>"><?= can('savings.edit') ? 'Edit' : 'View' ?></a>
                    <?php if ($nextStage && can('savings.edit')): ?>
                        <form action="<?= h(app_url('action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="advance_savings_stage"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="return_to" value="<?= h($returnTo) ?>"><button class="mini-button primary" type="submit">Move to <?= h($stageDefinitions[$nextStage]['short']) ?></button></form>
                    <?php elseif ((string)($item['stage'] ?? '') === 'approved'): ?>
                        <span class="savings-realization-state"><?= h(status_label((string)($item['accounting_validation'] ?? 'not_requested'))) ?></span>
                    <?php endif; ?>
                </footer>
            </article>
        <?php endforeach; ?>
        <?php if(!$stageItems): ?><div class="pipeline-empty">No opportunities in this stage.</div><?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
</section>

<?php if ($modalOpen): ?>
<div class="modal is-open" id="savingsModal" data-auto-open="true">
    <div class="modal-backdrop" data-modal-close></div>
    <section class="modal-card modal-card-wide" role="dialog" aria-modal="true" aria-labelledby="savingsModalTitle">
        <header><div><span class="eyebrow"><?= $editingRecord ? 'Opportunity record' : 'New opportunity' ?></span><h2 id="savingsModalTitle"><?= $editingRecord ? h($editingRecord['title']) : 'Create savings opportunity' ?></h2></div><a class="modal-close-link" href="<?= h(app_url('savings.php')) ?>" aria-label="Close">×</a></header>
        <form class="modal-body form-grid savings-record-form" action="<?= h(app_url('action.php')) ?>" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_savings"><input type="hidden" name="id" value="<?= (int)($formRecord['id'] ?? 0) ?>"><input type="hidden" name="return_to" value="savings.php">
            <label class="span-2"><span>Opportunity title</span><input name="title" value="<?= h($formRecord['title'] ?? '') ?>" required <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Company</span><select name="company_id" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?= company_options($formRecord['company_id'] ?? $defaultCompany) ?></select></label>
            <label><span>Owner</span><select name="owner_id" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?= user_options($formRecord['owner_id'] ?? current_user()['id']) ?></select></label>
            <label><span>Purchasing category</span><select name="category_id" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?= category_options($formRecord['category_id'] ?? 1) ?></select></label>
            <label><span>Supplier</span><select name="supplier_id" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><option value="0">No single supplier</option><?= supplier_options($formRecord['supplier_id'] ?? 0) ?></select></label>
            <label><span>Opportunity type</span><select name="opportunity_type" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?php foreach(['price','supplier_consolidation','freight','payment_terms','inventory_reduction','transfer','process','quality','warranty_recovery','specification','other'] as $type): ?><option value="<?= h($type) ?>" <?= ($formRecord['opportunity_type']??'other')===$type?'selected':'' ?>><?= h(status_label($type)) ?></option><?php endforeach; ?></select></label>
            <label><span>Execution risk</span><select name="risk" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?php foreach(['low','medium','high','critical'] as $risk): ?><option value="<?= h($risk) ?>" <?= ($formRecord['risk']??'medium')===$risk?'selected':'' ?>><?= h(status_label($risk)) ?></option><?php endforeach; ?></select></label>
            <label><span>Pipeline stage</span><select name="stage" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?php foreach($stageDefinitions as $stage=>$definition): ?><option value="<?= h($stage) ?>" <?= ($formRecord['stage']??'draft')===$stage?'selected':'' ?>><?= h($definition['label']) ?></option><?php endforeach; ?></select></label>
            <label><span>Operational status</span><select name="operational_status" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?php foreach(['identified','analyzing','negotiating','approved','implementing','completed','rejected'] as $status): ?><option value="<?= h($status) ?>" <?= ($formRecord['operational_status']??'identified')===$status?'selected':'' ?>><?= h(status_label($status)) ?></option><?php endforeach; ?></select></label>
            <label><span>Current annual cost</span><input type="number" min="0" step="0.01" name="current_annual_cost" value="<?= h((string)($formRecord['current_annual_cost']??0)) ?>" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Expected annual savings</span><input type="number" min="0" step="0.01" name="annualized_value" value="<?= h((string)($formRecord['annualized_value']??0)) ?>" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Implementation cost</span><input type="number" min="0" step="0.01" name="implementation_cost" value="<?= h((string)($formRecord['implementation_cost']??0)) ?>" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Realized savings</span><input type="number" min="0" step="0.01" name="realized_savings" value="<?= h((string)($formRecord['realized_savings']??0)) ?>" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Confidence %</span><input type="number" min="0" max="100" name="confidence" value="<?= (int)($formRecord['confidence']??50) ?>" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Target date</span><input type="date" name="due_date" value="<?= h((string)($formRecord['due_date']??'')) ?>" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>></label>
            <label><span>Accounting validation</span><select name="accounting_validation" <?= can('savings.edit')||!$editingRecord?'':'disabled' ?>><?php foreach(['not_requested','pending','validated','rejected'] as $state): ?><option value="<?= h($state) ?>" <?= ($formRecord['accounting_validation']??'not_requested')===$state?'selected':'' ?>><?= h(status_label($state)) ?></option><?php endforeach; ?></select></label>
            <label class="span-2"><span>Description and evidence</span><textarea name="description" rows="4" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>><?= h((string)($formRecord['description']??'')) ?></textarea></label>
            <label class="span-2"><span>Next step</span><textarea name="next_step" rows="3" <?= can('savings.edit')||!$editingRecord?'':'readonly' ?>><?= h((string)($formRecord['next_step']??'')) ?></textarea></label>
            <div class="span-2 savings-form-summary"><article><span>Weighted forecast</span><strong><?= money((float)($formRecord['annualized_value']??0) * ((int)($formRecord['confidence']??0)/100)) ?></strong></article><article><span>Net forecast</span><strong><?= money(max(0,(float)($formRecord['annualized_value']??0)-(float)($formRecord['implementation_cost']??0))) ?></strong></article><article><span>Realization progress</span><strong><?= (float)($formRecord['annualized_value']??0)>0 ? round(((float)($formRecord['realized_savings']??0)/(float)$formRecord['annualized_value'])*100) : 0 ?>%</strong></article></div>
            <div class="span-2 modal-actions"><a class="button secondary" href="<?= h(app_url('savings.php')) ?>">Close</a><?php if (can('savings.edit') || (!$editingRecord && can('savings.create'))): ?><button class="button primary" type="submit"><?= $editingRecord ? 'Save opportunity' : 'Create opportunity' ?></button><?php endif; ?></div>
        </form>
    </section>
</div>
<?php endif; ?>
<?php render_app_end(); ?>
