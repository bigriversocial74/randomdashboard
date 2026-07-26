<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/sourcing.php';
require_permission('suppliers.view');

$allSuppliers = sort_records(data_visible_collection('suppliers'), 'annual_spend', 'desc');
$recordId = query_int('id');
$savedRecord = $recordId ? sourcing_find_record($recordId) : null;
$requestedIds = $_GET['supplier_ids'] ?? ($savedRecord['selected_supplier_ids'] ?? array_slice(array_column($allSuppliers, 'id'), 0, 4));
$weightsInput = [];
foreach (sourcing_default_weights() as $key => $value) {
    $weightsInput[$key] = $_GET['weight_' . $key] ?? ($savedRecord['weights'][$key] ?? $value);
}
$error = null;
try {
    $comparison = sourcing_build_comparison(is_array($requestedIds) ? $requestedIds : [], $weightsInput);
} catch (Throwable $exception) {
    $error = $exception->getMessage();
    $fallback = array_slice(array_column($allSuppliers, 'id'), 0, 2);
    $comparison = count($fallback) >= 2 ? sourcing_build_comparison($fallback, sourcing_default_weights()) : ['weights'=>sourcing_default_weights(),'rows'=>[],'recommended_supplier_id'=>0,'alternate_supplier_id'=>0,'decision_confidence'=>0,'score_spread'=>0,'generated_at'=>date('Y-m-d H:i:s')];
}

if (query_string('export') === 'csv') sourcing_export_csv($comparison);

$recommended = data_find('suppliers', (int)$comparison['recommended_supplier_id']);
$alternate = data_find('suppliers', (int)$comparison['alternate_supplier_id']);
$actions = '<a class="button ghost" href="'.h(app_url('suppliers.php')).'">Supplier Master</a>';
if (can('reports.export')) {
    $exportIds = array_map(static fn(array $row): int => (int)$row['supplier']['id'], $comparison['rows']);
    $actions .= '<a class="button secondary" href="'.h(app_url('sourcing.php?' . http_build_query(['supplier_ids'=>$exportIds,'export'=>'csv']))).'">Export CSV</a>';
}
$agentPrompt = 'Compare ' . implode(', ', array_map(static fn(array $row): string => (string)$row['supplier']['name'], $comparison['rows'])) . ' and explain the strongest sourcing recommendation, evidence gaps, and approval risks.';
$actions .= '<a class="button primary" href="'.h(app_url('agent.php?prompt=' . rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Supplier Comparison','suppliers','Strategic sourcing','Compare supplier economics, performance, risk, coverage, commitments, and evidence in one governed decision workspace.',$actions);
?>
<style>
.sourcing-hero{display:grid;grid-template-columns:1.3fr .7fr;gap:20px;padding:24px;border:1px solid var(--border-color,#dfe4ea);border-radius:20px;background:linear-gradient(135deg,#fff,#f7f9fc)}
.sourcing-hero h2{margin:.35rem 0 .65rem;font-size:clamp(1.65rem,2.6vw,2.4rem)}
.sourcing-score{display:grid;place-items:center;min-height:180px;border-radius:18px;background:#111827;color:#fff;text-align:center;padding:22px}.sourcing-score strong{font-size:3rem}.sourcing-score small{opacity:.75}
.supplier-picker{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.supplier-option{display:flex;gap:12px;align-items:flex-start;padding:14px;border:1px solid #dfe4ea;border-radius:14px;background:#fff}.supplier-option input{margin-top:5px}.supplier-option strong,.supplier-option small{display:block}.supplier-option small{color:#64748b;margin-top:3px}
.weight-grid{display:grid;grid-template-columns:repeat(6,minmax(120px,1fr));gap:12px}.weight-grid input{width:100%}
.sourcing-table th,.sourcing-table td{white-space:nowrap}.sourcing-table tr.is-recommended{background:#ecfdf5}.score-pill{display:inline-flex;min-width:58px;justify-content:center;padding:6px 10px;border-radius:999px;background:#eef2ff;font-weight:800}.score-pill.best{background:#d1fae5;color:#065f46}
.evidence-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}.evidence-card{border:1px solid #dfe4ea;border-radius:16px;padding:16px;background:#fff}.evidence-card dl{display:grid;grid-template-columns:1fr auto;gap:8px;margin:14px 0 0}.evidence-card dt{color:#64748b}.evidence-card dd{margin:0;font-weight:700}
.decision-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.decision-card{border:1px solid #dfe4ea;border-radius:18px;padding:18px;background:#fff}.saved-decision{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid #eef2f7}.saved-decision:last-child{border-bottom:0}
@media(max-width:980px){.sourcing-hero,.decision-grid{grid-template-columns:1fr}.weight-grid{grid-template-columns:repeat(2,minmax(120px,1fr))}}
</style>

<?php if ($error): ?><div class="flash error" role="alert"><?= h($error) ?></div><?php endif; ?>
<?php if (data_is_production() && !sourcing_tables_ready()): ?><div class="flash warning" role="status"><strong>Production save is not active yet.</strong> Import <code>database/20260726_section11_supplier_comparison.sql</code> at the final deployment stage. Live comparison remains available now.</div><?php endif; ?>

<section class="sourcing-hero">
    <div><span class="eyebrow">Evidence-based sourcing decision</span><h2><?= h($recommended['name'] ?? 'Select suppliers to compare') ?></h2><p><?= $recommended ? h('Recommended over ' . ($alternate['name'] ?? 'the next ranked supplier') . ' by ' . number_format((float)$comparison['score_spread'],1) . ' weighted points using current scoped records.') : 'Choose at least two visible suppliers.' ?></p><div class="company-chip-row"><span><?= h(current_scope_label()) ?></span><span><?= count($comparison['rows']) ?> suppliers</span><span><?= (int)$comparison['decision_confidence'] ?>% evidence confidence</span></div></div>
    <div class="sourcing-score"><span>Recommended score</span><strong><?= number_format((float)($comparison['rows'][0]['weighted_score'] ?? 0),1) ?></strong><small>Transparent weighted calculation</small></div>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Comparison setup</span><h2>Select suppliers and decision weights</h2><p>Choose two to five suppliers. Weights are normalized to 100% before scoring.</p></div><span class="panel-meta">Generated <?= h(date_us($comparison['generated_at'], true)) ?></span></header>
<form method="get" class="stacked-form">
    <div class="supplier-picker">
    <?php $selectedMap=array_fill_keys(array_map(static fn(array $row): int => (int)$row['supplier']['id'], $comparison['rows']),true); foreach($allSuppliers as $supplier): ?>
        <label class="supplier-option"><input type="checkbox" name="supplier_ids[]" value="<?= (int)$supplier['id'] ?>" <?= isset($selectedMap[(int)$supplier['id']])?'checked':'' ?>><span><strong><?= h($supplier['name']) ?></strong><small><?= h($supplier['category']) ?> · <?= h($supplier['payment_terms']) ?> · <?= h(status_label($supplier['risk'])) ?> risk</small></span></label>
    <?php endforeach; ?>
    </div>
    <div class="weight-grid">
    <?php foreach(['delivery'=>'Delivery','quality'=>'Quality','cost'=>'Cost','risk'=>'Risk','terms'=>'Terms','coverage'=>'Coverage'] as $key=>$label): ?>
        <label><span><?= h($label) ?> weight</span><input type="number" min="0" max="100" name="weight_<?= h($key) ?>" value="<?= h((string)round((float)$comparison['weights'][$key],2)) ?>"></label>
    <?php endforeach; ?>
    </div>
    <div><button class="button primary" type="submit">Recalculate comparison</button></div>
</form>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Decision matrix</span><h2>Side-by-side supplier comparison</h2><p>Every score links back to operational evidence already present in the active company scope.</p></div><span class="panel-meta">Higher is better</span></header>
<div class="table-wrap"><table class="data-table sourcing-table"><caption class="sr-only">Strategic sourcing supplier comparison</caption><thead><tr><th>Supplier</th><th>Weighted</th><th>Delivery</th><th>Quality</th><th>Cost</th><th>Risk</th><th>Terms</th><th>Coverage</th><th class="numeric">Annual spend</th><th class="numeric">Open POs</th><th>Past due</th><th>Evidence</th></tr></thead><tbody>
<?php foreach($comparison['rows'] as $index=>$row): ?>
<tr class="<?= $index===0?'is-recommended':'' ?>"><td><strong><?= h($row['supplier']['name']) ?></strong><small><?= h($row['supplier']['supplier_number']) ?> · <?= h($row['supplier']['category']) ?></small></td><td><span class="score-pill <?= $index===0?'best':'' ?>"><?= number_format((float)$row['weighted_score'],1) ?></span></td><td><?= number_format((float)$row['delivery'],1) ?></td><td><?= number_format((float)$row['quality'],1) ?></td><td><?= number_format((float)$row['cost'],1) ?></td><td><?= number_format((float)$row['risk_score'],1) ?></td><td><?= number_format((float)$row['terms_score'],1) ?></td><td><?= number_format((float)$row['coverage_score'],1) ?></td><td class="numeric"><?= money($row['annual_spend']) ?></td><td class="numeric"><?= money($row['open_po_value']) ?></td><td><?= (int)$row['past_due_count'] ?></td><td><?= (int)$row['confidence'] ?>%</td></tr>
<?php endforeach; ?>
<?php if(!$comparison['rows']): ?><tr><td colspan="12"><?php render_empty_state('No supplier comparison','Select at least two visible suppliers.'); ?></td></tr><?php endif; ?>
</tbody></table></div>
</section>

<section class="evidence-grid" aria-label="Supplier evidence details">
<?php foreach($comparison['rows'] as $row): ?>
<article class="evidence-card"><header><span class="eyebrow"><?= h($row['supplier']['supplier_number']) ?></span><h3><?= h($row['supplier']['name']) ?></h3><p><?= h(status_label($row['supplier']['status'])) ?> · <?= h(status_label($row['supplier']['risk'])) ?> risk · <?= h($row['supplier']['payment_terms']) ?></p></header><dl><dt>Latest scorecard</dt><dd><?= $row['scorecard']?h($row['scorecard']['period']).' · '.number_format((float)$row['scorecard']['overall'],1):'Missing' ?></dd><dt>Visible purchase orders</dt><dd><?= (int)$row['po_count'] ?></dd><dt>Active/draft contracts</dt><dd><?= (int)$row['contract_count'] ?></dd><dt>Supplied items</dt><dd><?= (int)$row['item_count'] ?></dd><dt>Inventory exposure</dt><dd><?= compact_money($row['inventory_value']) ?></dd><dt>Missing evidence</dt><dd><?= $row['missing_data']?h(implode(', ',$row['missing_data'])):'None' ?></dd></dl></article>
<?php endforeach; ?>
</section>

<section class="decision-grid">
<?php if(can('suppliers.edit')): ?>
<article class="decision-card"><span class="eyebrow">Governed sourcing record</span><h2><?= $savedRecord?'Update decision':'Save comparison decision' ?></h2><form action="<?= h(app_url('sourcing-action.php')) ?>" method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($savedRecord['id']??0) ?>"><?php foreach($comparison['rows'] as $row): ?><input type="hidden" name="supplier_ids[]" value="<?= (int)$row['supplier']['id'] ?>"><?php endforeach; ?><?php foreach($comparison['weights'] as $key=>$value): ?><input type="hidden" name="weight_<?= h($key) ?>" value="<?= h((string)$value) ?>"><?php endforeach; ?><label class="span-2"><span>Decision title</span><input name="title" maxlength="190" required value="<?= h((string)($savedRecord['title']??'Strategic supplier comparison')) ?>"></label><label><span>Category / initiative</span><input name="category" maxlength="190" value="<?= h((string)($savedRecord['category']??'Strategic sourcing')) ?>"></label><label><span>Preferred supplier</span><select name="preferred_supplier_id"><?php foreach($comparison['rows'] as $row): ?><option value="<?= (int)$row['supplier']['id'] ?>" <?= (int)($savedRecord['preferred_supplier_id']??$comparison['recommended_supplier_id'])===(int)$row['supplier']['id']?'selected':'' ?>><?= h($row['supplier']['name']) ?></option><?php endforeach; ?></select></label><label><span>Alternate supplier</span><select name="alternate_supplier_id"><?php foreach($comparison['rows'] as $row): ?><option value="<?= (int)$row['supplier']['id'] ?>" <?= (int)($savedRecord['alternate_supplier_id']??$comparison['alternate_supplier_id'])===(int)$row['supplier']['id']?'selected':'' ?>><?= h($row['supplier']['name']) ?></option><?php endforeach; ?></select></label><label class="span-2"><span>Decision rationale</span><textarea name="rationale" rows="5" required><?= h((string)($savedRecord['rationale']??'Award recommendation is based on the weighted supplier matrix, current risk, open commitments, contracts, scorecards, and company coverage.')) ?></textarea></label><div class="span-2 modal-actions"><button class="button primary" type="submit">Save sourcing decision</button></div></form></article>
<?php endif; ?>
<article class="decision-card"><span class="eyebrow">Saved scenarios</span><h2>Decision history</h2><?php $saved=sourcing_records(); foreach($saved as $record): $status=sourcing_effective_status($record); ?><div class="saved-decision"><div><strong><?= h($record['title']) ?></strong><small><?= h($record['comparison_number']) ?> · <?= count($record['selected_supplier_ids']) ?> suppliers · Updated <?= h(date_us($record['updated_at'],true)) ?></small><div><?= badge($status) ?> <a href="<?= h(app_url('sourcing.php?id='.(int)$record['id'])) ?>">Open comparison</a></div></div><?php if(can('approvals.submit') && in_array($status,['draft','changes_requested'],true)): ?><form action="<?= h(app_url('sourcing-action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="submit"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><button class="mini-button primary" type="submit">Route approval</button></form><?php endif; ?></div><?php endforeach; ?><?php if(!$saved) render_empty_state('No saved sourcing decisions','Save the current comparison to create the first governed decision record.'); ?></article>
</section>
<?php render_app_end(); ?>
