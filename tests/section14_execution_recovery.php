<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/executions.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 14 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/execution_management.php';

$plan=mitigation_find_record(1);$action=mitigation_actions(1)[0]??null;
if(!$plan||!$action){fwrite(STDERR,"Section 13 mitigation seed is unavailable.\n");exit(1);}
$blueprint=execution_blueprint($plan,$action);
foreach(['before','target','rollback_plan','evidence_note','change_risk','execution_type'] as $field){if(!array_key_exists($field,$blueprint)){fwrite(STDERR,"Execution blueprint missing {$field}.\n");exit(1);}}
$approvalProbe=['change_risk'=>'high','execution_type'=>'alternate_supplier','target'=>$blueprint['target']];
if(!execution_requires_approval($approvalProbe)){fwrite(STDERR,"High-impact change approval rule failed.\n");exit(1);}
$directProbe=['change_risk'=>'low','execution_type'=>'demand','target'=>['recovery_value'=>1000,'po_value_redirected'=>0,'cost'=>100]];
if(execution_requires_approval($directProbe)){fwrite(STDERR,"Low-risk direct control rule failed.\n");exit(1);}
$record=execution_save_record(['id'=>null,'company_id'=>null,'plan_id'=>1,'action_id'=>(int)$action['id'],'execution_type'=>$blueprint['execution_type'],'title'=>'Quality controlled execution','owner_id'=>1,'status'=>'proposed','change_risk'=>$blueprint['change_risk'],'approval_id'=>null,'before'=>$blueprint['before'],'target'=>$blueprint['target'],'actual'=>[],'rollback_plan'=>$blueprint['rollback_plan'],'evidence_note'=>'Quality execution evidence.','scheduled_date'=>date('Y-m-d',strtotime('+3 days')),'started_at'=>null,'completed_at'=>null]);
if((int)$record['id']<=0||!execution_find_record((int)$record['id'])){fwrite(STDERR,"Demo execution persistence failed.\n");exit(1);}
$event=execution_add_event((int)$record['id'],'created',null,'proposed','Quality event evidence.');
if((int)$event['id']<=0||count(execution_events((int)$record['id']))<1){fwrite(STDERR,"Execution event persistence failed.\n");exit(1);}
$actual=['recovery_value'=>50000.0,'cost'=>2500.0,'risk_score'=>35.0,'lead_time_days'=>3,'inventory_exposure_reduced'=>20000.0,'po_value_redirected'=>40000.0,'service_level'=>92.0];
$record['actual']=$actual;$record['status']='completed';$record=execution_save_record($record);
$verification=execution_save_verification(['id'=>null,'company_id'=>null,'execution_id'=>(int)$record['id'],'status'=>'verified','planned_recovery_value'=>(float)$record['target']['recovery_value'],'actual_recovery_value'=>$actual['recovery_value'],'planned_cost'=>(float)$record['target']['cost'],'actual_cost'=>$actual['cost'],'before_risk_score'=>(float)$record['before']['risk_score'],'after_risk_score'=>$actual['risk_score'],'before_lead_time_days'=>(int)$record['before']['lead_time_days'],'after_lead_time_days'=>$actual['lead_time_days'],'inventory_exposure_reduced'=>$actual['inventory_exposure_reduced'],'po_value_redirected'=>$actual['po_value_redirected'],'service_level_before'=>(float)$record['before']['service_level'],'service_level_after'=>$actual['service_level'],'reviewer_id'=>1,'evidence_note'=>'Quality verification evidence.','verified_at'=>date('Y-m-d H:i:s')]);
if((int)$verification['id']<=0||execution_latest_verification((int)$record['id'])===null){fwrite(STDERR,"Recovery verification persistence failed.\n");exit(1);}
$metrics=execution_metrics($record,$verification);
foreach(['recovery_attainment_pct','cost_variance','risk_reduction','lead_time_improvement_days','benefit_cost_ratio'] as $field){if(!array_key_exists($field,$metrics)){fwrite(STDERR,"Execution metrics missing {$field}.\n");exit(1);}}
if((float)$metrics['risk_reduction']<=0||(float)$metrics['actual_recovery_value']!==50000.0){fwrite(STDERR,"Execution recovery calculation failed.\n");exit(1);}
$record['status']='verified';$record=execution_save_record($record);$completed=mitigation_save_action(['id'=>$action['id'],'status'=>'completed']);
$readiness=execution_plan_readiness(1);
if(!$readiness['ready']||(int)$readiness['verified_count']<1){fwrite(STDERR,"Plan containment verification gate failed.\n");exit(1);}
if(execution_csv_cell('@SUM(A1:A2)')!=="'@SUM(A1:A2)"){fwrite(STDERR,"Execution CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/executions.php').file_get_contents($root.'/includes/app/execution_view.php');
$actionFile=file_get_contents($root.'/app/execution-action.php');
$sql=file_get_contents($root.'/database/20260726_section14_execution_recovery_verification.sql');
foreach(['Mitigation Execution, Recovery Verification &amp; Procurement Change Control','Before / target / actual','Controlled execution','Independent recovery verification','Rollback controlled change','Protected CSV export'] as $needle){if(!str_contains($page,$needle)){fwrite(STDERR,"Execution workspace missing {$needle}.\n");exit(1);}}
foreach(['procurement_mitigation_execution','procurement_recovery_verification','workflow_approvals','execution_add_event','approvals.review','savings.edit','rolled_back'] as $needle){if(!str_contains($actionFile,$needle)){fwrite(STDERR,"Execution workflow missing {$needle}.\n");exit(1);}}
foreach(['CREATE TABLE IF NOT EXISTS procurement_mitigation_executions','CREATE TABLE IF NOT EXISTS procurement_mitigation_execution_events','CREATE TABLE IF NOT EXISTS procurement_recovery_verifications','4.3-section14'] as $needle){if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 14 SQL missing {$needle}.\n");exit(1);}}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 14 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 14 execution and recovery verification gates passed.\n");
