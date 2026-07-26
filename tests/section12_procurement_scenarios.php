<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/scenarios.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 12 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/scenario_planning.php';

$simulation=scenario_calculate(scenario_default_inputs());
foreach(['net_impact','gross_impact','risk_score','risk_level','cases','alternatives','critical_items'] as $field){if(!array_key_exists($field,$simulation)){fwrite(STDERR,"Simulation missing {$field}.\n");exit(1);}}
$priceOnly=scenario_calculate(array_replace(scenario_default_inputs(),['scenario_type'=>'price_increase']));
if($priceOnly['active_assumptions']['demand_change_pct']!==0.0||$priceOnly['active_assumptions']['lead_time_delay_days']!==0||$priceOnly['active_assumptions']['disruption_pct']!==0.0){fwrite(STDERR,"Scenario type activation failed.\n");exit(1);}
if(count($simulation['cases'])!==3){fwrite(STDERR,"Expected best, expected, and worst cases.\n");exit(1);}
if($simulation['cases']['best']['net_impact']>$simulation['cases']['worst']['net_impact']){fwrite(STDERR,"Scenario case ordering is invalid.\n");exit(1);}
$clamped=scenario_normalize_inputs(['price_change_pct'=>999,'demand_change_pct'=>-999,'disruption_pct'=>999,'lead_time_delay_days'=>999]);
if($clamped['price_change_pct']!==250.0||$clamped['demand_change_pct']!==-80.0||$clamped['disruption_pct']!==100.0||$clamped['lead_time_delay_days']!==365){fwrite(STDERR,"Scenario input constraints failed.\n");exit(1);}
$record=scenario_save_record([
'id'=>null,'company_id'=>null,'title'=>'Quality scenario','scenario_type'=>$simulation['inputs']['scenario_type'],'supplier_id'=>1,'category_id'=>1,
'inputs'=>$simulation['inputs'],'result_summary'=>['net_impact'=>$simulation['net_impact'],'risk_score'=>$simulation['risk_score'],'risk_level'=>$simulation['risk_level']],
'risk_level'=>$simulation['risk_level'],'decision_status'=>'draft','decision_note'=>'Quality test','owner_id'=>1,'approval_id'=>null,
]);
if((int)$record['id']<=0||!scenario_find_record((int)$record['id'])){fwrite(STDERR,"Demo scenario persistence failed.\n");exit(1);}
if(scenario_csv_cell('@SUM(A1:A2)')!=="'@SUM(A1:A2)"){fwrite(STDERR,"Scenario CSV protection failed.\n");exit(1);}
$page=file_get_contents($root.'/app/scenarios.php').file_get_contents($root.'/includes/app/scenario_view_controls.php').file_get_contents($root.'/includes/app/scenario_view_results.php').file_get_contents($root.'/includes/app/scenario_view_records.php');
$action=file_get_contents($root.'/app/scenario-action.php');
$sql=file_get_contents($root.'/database/20260726_section12_procurement_scenarios.sql');
foreach(['Scenario Planning &amp; Procurement Risk Simulation','Best','Expected','Worst','Transparent formulas','Alternative suppliers','Route approval'] as $needle){if(!str_contains($page,$needle)){fwrite(STDERR,"Scenario page missing {$needle}.\n");exit(1);}}
foreach(['procurement_scenario','workflow_approvals','notifications','approvals.submit','savings.edit'] as $needle){if(!str_contains($action,$needle)){fwrite(STDERR,"Scenario action missing {$needle}.\n");exit(1);}}
foreach(['CREATE TABLE IF NOT EXISTS procurement_scenarios','4.1-section12'] as $needle){if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 12 SQL missing {$needle}.\n");exit(1);}}
fwrite(STDOUT,"Section 12 procurement scenario gates passed.\n");
