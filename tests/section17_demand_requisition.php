<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/demand.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 17 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/demand_management.php';

$request=demand_default_request();
if(!$request){fwrite(STDERR,"Demand seed is unavailable.\n");exit(1);}
$lines=demand_request_lines((int)$request['id']);
$blueprint=demand_assessment_blueprint($request,$lines);
foreach(['requested_value','inventory_avoidance_value','open_po_coverage_value','contract_covered_value','off_contract_exposure','budget_status','required_date_risk','assessment_score','recommended_supplier_id','recommended_action'] as $field){
    if(!array_key_exists($field,$blueprint)){fwrite(STDERR,"Demand assessment missing {$field}.\n");exit(1);}
}
$metrics=demand_metrics($request,$lines,$blueprint);
foreach(['requested_value','budget_remaining','budget_utilization_pct','inventory_avoidance_value','contract_coverage_pct','forecast_cash_requirement'] as $field){
    if(!array_key_exists($field,$metrics)){fwrite(STDERR,"Demand metrics missing {$field}.\n");exit(1);}
}

$new=demand_save_request([
    'id'=>null,'request_number'=>'REQ-QUALITY-017','company_id'=>2,'location_id'=>null,'department_id'=>null,
    'requested_by'=>1,'project_workorder_id'=>null,'business_purpose'=>'inventory_replenishment',
    'required_date'=>date('Y-m-d',strtotime('+30 days')),'urgency'=>'normal','status'=>'draft',
    'justification'=>'Section 17 quality request.','estimated_total'=>0,'submitted_at'=>null,'approved_at'=>null,
    'owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'budget_envelope_id'=>1,'sourcing_assessment_id'=>null,
    'converted_po_id'=>null,'capex_opex'=>'operating_expense','unplanned_demand'=>false,'source_status'=>'not_assessed',
    'evidence_note'=>'Quality request evidence.','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
]);
if((int)$new['id']<=0||!demand_find_request((int)$new['id'])){fwrite(STDERR,"Purchase request persistence failed.\n");exit(1);}

$line=demand_save_line([
    'id'=>null,'purchase_request_id'=>(int)$new['id'],'item_id'=>1,'requested_description'=>'Quality battery demand',
    'quantity'=>20,'unit_of_measure'=>'EA','estimated_unit_cost'=>189.50,'preferred_supplier_id'=>1,
    'specification_notes'=>'Approved specification.','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
]);
if((int)$line['id']<=0||!demand_find_line((int)$line['id'])){fwrite(STDERR,"Purchase request line persistence failed.\n");exit(1);}

$newLines=demand_request_lines((int)$new['id']);
$source=demand_assessment_blueprint($new,$newLines);
$assessment=demand_save_assessment(array_replace($source,[
    'purchase_request_id'=>(int)$new['id'],'company_id'=>2,'evidence_note'=>'Quality sourcing assessment evidence.','created_by'=>1
]));
if((int)$assessment['id']<=0||!demand_find_assessment((int)$assessment['id'])){fwrite(STDERR,"Sourcing assessment persistence failed.\n");exit(1);}

$budget=demand_save_budget([
    'id'=>null,'company_id'=>2,'department_id'=>null,'project_workorder_id'=>null,'category_id'=>1,
    'period_start'=>date('Y-01-01'),'period_end'=>date('Y-12-31'),'budget_amount'=>100000,
    'requested_amount'=>0,'approved_amount'=>0,'committed_amount'=>0,'actual_amount'=>0,
    'owner_id'=>3,'status'=>'active','evidence_note'=>'Quality budget evidence.'
]);
if((int)$budget['id']<=0||!demand_find_budget((int)$budget['id'])){fwrite(STDERR,"Budget persistence failed.\n");exit(1);}

$forecast=demand_save_forecast([
    'id'=>null,'company_id'=>2,'category_id'=>1,'period_start'=>date('Y-m-01'),
    'period_end'=>date('Y-m-t',strtotime('+2 months')),'forecast_quantity'=>100,'forecast_value'=>20000,
    'confidence_pct'=>80,'source_note'=>'Quality forecast evidence.','owner_id'=>3,'status'=>'active'
]);
if((int)$forecast['id']<=0){fwrite(STDERR,"Forecast persistence failed.\n");exit(1);}

$event=demand_add_event((int)$new['id'],'assessment_completed','draft','draft','medium','Quality event evidence.');
if((int)$event['id']<=0||count(demand_events((int)$new['id']))<1){fwrite(STDERR,"Request event persistence failed.\n");exit(1);}

$approval=data_upsert('workflow_approvals',[
    'id'=>null,'company_id'=>2,'module'=>'purchase_orders','entity_type'=>'purchase_request','entity_id'=>(int)$new['id'],
    'title'=>'Section 17 quality approval','submitted_by'=>1,'assigned_to'=>6,'status'=>'approved',
    'submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+2 days')),'notes'=>'Approved quality request.'
]);
$new['approval_id']=(int)$approval['id'];$new['sourcing_assessment_id']=(int)$assessment['id'];$new['status']='submitted';
$new=demand_save_request($new);
if(demand_effective_status($new)!=='approved'){fwrite(STDERR,"Purchase request approval precedence failed.\n");exit(1);}

$po=demand_convert_to_po($new,$assessment,$newLines);
if((int)$po['id']<=0||!str_starts_with((string)$po['po_number'],'PO-REQ-')){fwrite(STDERR,"Purchase request conversion failed.\n");exit(1);}
$converted=demand_find_request((int)$new['id']);
if(!$converted||$converted['status']!=='converted'||(int)$converted['converted_po_id']!==(int)$po['id']){fwrite(STDERR,"Request-to-PO traceability failed.\n");exit(1);}
if(demand_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)"){fwrite(STDERR,"Demand CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/demand.php').file_get_contents($root.'/includes/app/demand_view.php');
$actionFile=file_get_contents($root.'/app/demand-action.php');
$sql=file_get_contents($root.'/database/20260727_section17_demand_requisition_budget_governance.sql');
$poPage=file_get_contents($root.'/app/purchase-orders.php');
$contractPage=file_get_contents($root.'/app/contracts.php');
foreach(['Demand Intake, Purchase Requisitions & Budget Governance','Demand intelligence','Budget governance','Purchase request lines','Demand forecasts','Immutable request history'] as $needle){
    if(!str_contains($page,$needle)){fwrite(STDERR,"Demand workspace missing {$needle}.\n");exit(1);}
}
foreach(['save_request','save_line','save_budget','save_forecast','assess_request','submit_request','convert_request','workflow_approvals','demand_add_event','purchase_orders.approve'] as $needle){
    if(!str_contains($actionFile,$needle)){fwrite(STDERR,"Demand handler missing {$needle}.\n");exit(1);}
}
foreach(['CREATE TABLE IF NOT EXISTS procurement_request_governance_profiles','CREATE TABLE IF NOT EXISTS procurement_budget_envelopes','CREATE TABLE IF NOT EXISTS procurement_demand_forecasts','CREATE TABLE IF NOT EXISTS purchase_request_sourcing_assessments','CREATE TABLE IF NOT EXISTS purchase_request_events','4.6-section17'] as $needle){
    if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 17 SQL missing {$needle}.\n");exit(1);}
}
if(!str_contains($poPage,'demand.php')||!str_contains($contractPage,'demand.php')){fwrite(STDERR,"Demand workflow handoffs are missing.\n");exit(1);}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 17 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 17 demand intake, requisition, and budget governance gates passed.\n");
