<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/savings-realization.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 20 quality test';
require $root.'/includes/app/bootstrap.php';
if (!demo_start_session(1)) { fwrite(STDERR,"Could not start demo session.\n"); exit(1); }
require_once $root.'/includes/app/savings_realization.php';

$opportunity=savings_realization_find_opportunity(1)??savings_realization_default_opportunity();
if (!$opportunity) { fwrite(STDERR,"No savings opportunity is available.\n"); exit(1); }
if ((int)($opportunity['owner_id']??0)===(int)current_user()['id']) {
    $opportunity['owner_id']=3;
    $opportunity=data_upsert('savings_opportunities',$opportunity);
}

$baseline=savings_realization_save_baseline([
    'id'=>null,'opportunity_id'=>$opportunity['id'],'version_number'=>99,'baseline_type'=>'historical_spend',
    'period_start'=>'2025-01-01','period_end'=>'2025-12-31','baseline_volume'=>1000,
    'baseline_unit_cost'=>100,'baseline_total_cost'=>100000,'currency_code'=>'USD',
    'methodology'=>'Section 20 quality baseline methodology.','assumptions'=>'Comparable volume and specification.',
    'supplier_id'=>$opportunity['supplier_id']?:null,'contract_id'=>null,'status'=>'draft',
    'owner_id'=>$opportunity['owner_id'],'reviewer_id'=>6,'approval_id'=>null,'locked_at'=>null,
    'evidence_note'=>'Section 20 quality baseline evidence.',
]);
if ((int)$baseline['id']<=0 || !savings_realization_find_baseline((int)$baseline['id'])) { fwrite(STDERR,"Baseline persistence failed.\n"); exit(1); }
$submittedBaseline=savings_realization_submit_baseline($baseline);
$baselineApproval=data_find('workflow_approvals',(int)$submittedBaseline['approval_id']);
$baselineApproval['status']='approved';
data_upsert('workflow_approvals',$baselineApproval);
$locked=savings_realization_lock_baseline($submittedBaseline,'Section 20 finance-approved baseline lock evidence.');
if ($locked['status']!=='approved' || empty($locked['locked_at'])) { fwrite(STDERR,"Baseline approval and lock failed.\n"); exit(1); }

$period=savings_realization_save_period(array_replace([
    'id'=>null,'opportunity_id'=>$opportunity['id'],'period_start'=>'2026-08-01','period_end'=>'2026-08-31',
    'fiscal_year'=>2026,'fiscal_period'=>'2026-08','planned_hard_savings'=>10000,'planned_cost_avoidance'=>2000,
    'planned_recoveries'=>1000,'planned_working_capital'=>500,'actual_hard_savings'=>9000,
    'actual_cost_avoidance'=>1800,'actual_recoveries'=>1200,'actual_working_capital'=>400,
    'implementation_cost'=>500,'operating_cost'=>100,'leakage_amount'=>300,'adjustment_amount'=>50,
    'status'=>'draft','owner_id'=>$opportunity['owner_id'],'reviewer_id'=>6,'approval_id'=>null,
    'submitted_at'=>null,'validated_at'=>null,'closed_at'=>null,
    'evidence_note'=>'Section 20 quality period evidence.','created_at'=>date('Y-m-d H:i:s'),
],savings_realization_calculate_period([
    'actual_hard_savings'=>9000,'actual_cost_avoidance'=>1800,'actual_recoveries'=>1200,'actual_working_capital'=>400,
    'implementation_cost'=>500,'operating_cost'=>100,'leakage_amount'=>300,'adjustment_amount'=>50,
])));
if ((int)$period['id']<=0 || abs((float)$period['net_realized_value']-11550)>0.001) { fwrite(STDERR,"Period calculation or persistence failed.\n"); exit(1); }

$evidence=savings_realization_save_evidence([
    'id'=>null,'opportunity_id'=>$opportunity['id'],'realization_period_id'=>$period['id'],
    'entity_type'=>'supplier_invoice','entity_id'=>1,'evidence_reference'=>'QUALITY-S20-INV',
    'evidence_amount'=>9000,'evidence_date'=>'2026-08-20','status'=>'linked','verified_by'=>null,'verified_at'=>null,
    'evidence_note'=>'Section 20 source invoice evidence.','created_by'=>(int)$opportunity['owner_id'],
]);
$verified=savings_realization_verify_evidence($evidence,'Section 20 evidence independently verified.');
if ($verified['status']!=='verified' || empty($verified['verified_at'])) { fwrite(STDERR,"Evidence verification failed.\n"); exit(1); }

$submitted=savings_realization_submit_period($period);
$approval=data_find('workflow_approvals',(int)$submitted['approval_id']);
$approval['status']='approved';
data_upsert('workflow_approvals',$approval);
if (savings_realization_evidence_completeness($opportunity,$submitted)<75) { fwrite(STDERR,"Evidence completeness failed.\n"); exit(1); }
$result=savings_realization_validate_period($submitted,'Section 20 independent finance validation evidence.');
$validated=$result['period'];
if ($validated['status']!=='validated' || (float)$result['validation']['validated_net_value']!==11550.0) { fwrite(STDERR,"Finance validation failed.\n"); exit(1); }
$canonical=savings_realization_find_opportunity((int)$opportunity['id']);
if ((float)$canonical['realized_savings']<11550 || $canonical['accounting_validation']!=='validated') { fwrite(STDERR,"Canonical realized-savings rollup failed.\n"); exit(1); }
$closed=savings_realization_close_period($validated,'Section 20 validated fiscal-period close evidence.');
if ($closed['status']!=='closed') { fwrite(STDERR,"Finance period close failed.\n"); exit(1); }

$leakage=savings_realization_save_leakage([
    'id'=>null,'opportunity_id'=>$opportunity['id'],'realization_period_id'=>$period['id'],
    'leakage_type'=>'missed_credit','detected_date'=>'2026-08-25','amount'=>700,'recovered_amount'=>0,
    'status'=>'open','owner_id'=>$opportunity['owner_id'],'due_date'=>'2026-09-10',
    'source_entity_type'=>'supplier_invoice','source_entity_id'=>1,'root_cause'=>'Credit was omitted from the initial invoice.',
    'corrective_action'=>'Issue supplier credit and reconcile the period evidence.',
    'evidence_note'=>'Section 20 quality leakage evidence.',
]);
$recovered=savings_realization_recover_leakage($leakage,700,'Section 20 supplier-credit recovery evidence.');
if ($recovered['status']!=='recovered' || (float)$recovered['recovered_amount']!==700.0) { fwrite(STDERR,"Leakage recovery failed.\n"); exit(1); }

$metrics=savings_realization_metrics($canonical);
foreach (['gross_pipeline','weighted_forecast','finance_validated','net_realized','hard_savings','cost_avoidance','recoveries','working_capital','implementation_cost','leakage_value','benefits_at_risk','realization_pct','forecast_accuracy','validated_periods','pending_periods'] as $field) {
    if (!array_key_exists($field,$metrics)) { fwrite(STDERR,"Savings metrics missing {$field}.\n"); exit(1); }
}
if (savings_realization_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)") { fwrite(STDERR,"Savings CSV protection failed.\n"); exit(1); }
if (count(savings_realization_events((int)$opportunity['id']))<5) { fwrite(STDERR,"Immutable savings event history failed.\n"); exit(1); }

$page=file_get_contents($root.'/app/savings-realization.php').file_get_contents($root.'/includes/app/savings_realization_view_styles.php');
$action=file_get_contents($root.'/app/savings-realization-action.php');
$sql=file_get_contents($root.'/database/20260727_section20_savings_realization_finance_governance.sql');
$savingsPage=file_get_contents($root.'/app/savings.php');
foreach (['Savings Realization, Finance Validation & Procurement Value Governance','Baseline governance','Realization periods','Evidence ledger','Finance validations','Leakage governance','Governance events'] as $needle) {
    if (!str_contains($page,$needle)) { fwrite(STDERR,"Savings workspace missing {$needle}.\n"); exit(1); }
}
foreach (['save_baseline','submit_baseline','lock_baseline','save_period','add_evidence','verify_evidence','submit_period','validate_period','request_changes_period','close_period','save_leakage','recover_leakage'] as $needle) {
    if (!str_contains($action,$needle)) { fwrite(STDERR,"Savings action handler missing {$needle}.\n"); exit(1); }
}
foreach (['CREATE TABLE IF NOT EXISTS savings_baselines','CREATE TABLE IF NOT EXISTS savings_realization_periods','CREATE TABLE IF NOT EXISTS savings_evidence_links','CREATE TABLE IF NOT EXISTS savings_finance_validations','CREATE TABLE IF NOT EXISTS savings_leakage_events','CREATE TABLE IF NOT EXISTS savings_governance_events','4.9-section20'] as $needle) {
    if (!str_contains($sql,$needle)) { fwrite(STDERR,"Section 20 SQL missing {$needle}.\n"); exit(1); }
}
if (!str_contains($savingsPage,'savings-realization.php')) { fwrite(STDERR,"Savings pipeline handoff is missing.\n"); exit(1); }
if (str_contains($sql,'gruber_ai_procurement_single_install_v3')) { fwrite(STDERR,"Section 20 migration must not reimport the fresh-install schema.\n"); exit(1); }

fwrite(STDOUT,"Section 20 savings realization, finance validation, evidence, leakage, period close, and canonical rollup gates passed.\n");
