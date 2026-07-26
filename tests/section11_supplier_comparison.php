<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$_SERVER['SCRIPT_NAME'] = '/gruber/app/sourcing.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Gruber Section 11 quality test';
require $root . '/includes/app/bootstrap.php';
if (!demo_start_session(1)) { fwrite(STDERR,"Could not start demo session.\n"); exit(1); }
require_once $root . '/includes/app/sourcing.php';

$comparison = sourcing_build_comparison([1,2,3,4], sourcing_default_weights());
if (count($comparison['rows']) !== 4) { fwrite(STDERR,"Expected four suppliers in comparison.\n"); exit(1); }
if ((int)$comparison['recommended_supplier_id'] <= 0 || (float)$comparison['rows'][0]['weighted_score'] <= 0) { fwrite(STDERR,"Recommendation calculation failed.\n"); exit(1); }
if (abs(array_sum($comparison['weights']) - 100) > 0.1) { fwrite(STDERR,"Weights were not normalized to 100.\n"); exit(1); }
foreach ($comparison['rows'] as $row) {
    foreach (['delivery','quality','cost','risk_score','terms_score','coverage_score','confidence','weighted_score'] as $field) {
        if (!isset($row[$field]) || !is_numeric($row[$field])) { fwrite(STDERR,"Comparison row missing {$field}.\n"); exit(1); }
    }
}
$record = sourcing_save_record([
    'id'=>null,'company_id'=>null,'title'=>'Quality test sourcing decision','category'=>'Test',
    'selected_supplier_ids'=>[1,2,3],'weights'=>sourcing_default_weights(),
    'preferred_supplier_id'=>$comparison['recommended_supplier_id'],'alternate_supplier_id'=>$comparison['alternate_supplier_id'],
    'decision_status'=>'draft','rationale'=>'Static quality validation only.','owner_id'=>1,'approval_id'=>null,
]);
if ((int)$record['id'] <= 0 || !sourcing_find_record((int)$record['id'])) { fwrite(STDERR,"Demo sourcing persistence failed.\n"); exit(1); }
if (sourcing_csv_cell('=SUM(A1:A2)') !== "'=SUM(A1:A2)") { fwrite(STDERR,"CSV formula protection failed.\n"); exit(1); }

$page = file_get_contents($root . '/app/sourcing.php');
$action = file_get_contents($root . '/app/sourcing-action.php');
$sql = file_get_contents($root . '/database/20260726_section11_supplier_comparison.sql');
foreach (['Side-by-side supplier comparison','Decision matrix','Analyze in Agent','Route approval'] as $needle) {
    if (!str_contains($page,$needle)) { fwrite(STDERR,"Sourcing page missing {$needle}.\n"); exit(1); }
}
foreach (['sourcing_save_record','workflow_approvals','notifications','approvals.submit'] as $needle) {
    if (!str_contains($action,$needle)) { fwrite(STDERR,"Sourcing action missing {$needle}.\n"); exit(1); }
}
foreach (['CREATE TABLE IF NOT EXISTS sourcing_comparisons','4.0-section11'] as $needle) {
    if (!str_contains($sql,$needle)) { fwrite(STDERR,"Section 11 SQL missing {$needle}.\n"); exit(1); }
}

fwrite(STDOUT,"Section 11 supplier comparison gates passed.\n");
