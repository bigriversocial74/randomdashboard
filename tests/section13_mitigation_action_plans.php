<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/mitigations.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 13 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/mitigation_planning.php';
require_once $root.'/includes/app/mitigation_status.php';

$scenario=scenario_find_record(1);
$blueprint=mitigation_default_blueprint($scenario);
if(count($blueprint['actions'])<5){fwrite(STDERR,"Mitigation blueprint must provide at least five owned actions.\n");exit(1);}
foreach(['alternate_supplier','inventory_transfer','supplier_recovery','contract','demand'] as $type){
    if(!array_filter($blueprint['actions'],static fn(array $row): bool => $row['action_type']===$type)){fwrite(STDERR,"Mitigation blueprint missing {$type}.\n");exit(1);}
}
$plan=[
    'id'=>null,'company_id'=>null,'scenario_id'=>(int)($scenario['id']??0),'supplier_id'=>1,'category_id'=>1,
    'title'=>'Quality mitigation plan','trigger_type'=>'risk_score','trigger_operator'=>'>=','trigger_value'=>60,
    'source_risk_score'=>$blueprint['simulation']['risk_score'],'source_net_impact'=>$blueprint['simulation']['net_impact'],
    'risk_level'=>$blueprint['simulation']['risk_level'],'status'=>'draft','owner_id'=>1,'approval_id'=>null,
    'summary'=>'Quality plan summary','activation_notes'=>'Quality activation evidence.',
];
$saved=mitigation_save_plan($plan);
if((int)$saved['id']<=0||!mitigation_find_record((int)$saved['id'])){fwrite(STDERR,"Demo mitigation plan persistence failed.\n");exit(1);}
$actions=mitigation_replace_actions((int)$saved['id'],$blueprint['actions']);
if(count($actions)!==count($blueprint['actions'])){fwrite(STDERR,"Demo mitigation action persistence failed.\n");exit(1);}
$metrics=mitigation_calculate_metrics($saved,$actions,$blueprint['simulation']);
foreach(['coverage_pct','readiness_pct','execution_pct','estimated_cost','recovery_value','residual_risk_score','residual_risk_level'] as $field){if(!array_key_exists($field,$metrics)){fwrite(STDERR,"Mitigation metrics missing {$field}.\n");exit(1);}}
if($metrics['residual_risk_score']>$metrics['source_risk_score']){fwrite(STDERR,"Residual risk cannot exceed source risk.\n");exit(1);}
$first=mitigation_find_action((int)$actions[0]['id']);
$updated=mitigation_save_action(['id'=>$first['id'],'status'=>'in_progress','priority'=>'critical','owner_id'=>1,'due_date'=>date('Y-m-d',strtotime('+5 days')),'readiness_pct'=>75,'notes'=>'Quality execution note']);
if($updated['status']!=='in_progress'||(float)$updated['readiness_pct']!==75.0){fwrite(STDERR,"Mitigation action update failed.\n");exit(1);}
$activeRecord=array_replace($saved,['status'=>'active','approval_id'=>987]);
if(mitigation_operational_status($activeRecord)!=='active'){fwrite(STDERR,"Active mitigation lifecycle status was hidden by approval state.\n");exit(1);}
$containedRecord=array_replace($activeRecord,['status'=>'contained']);
if(mitigation_operational_status($containedRecord)!=='contained'){fwrite(STDERR,"Contained mitigation lifecycle status was hidden by approval state.\n");exit(1);}
$exportRecord=mitigation_export_record($activeRecord);
if(!array_key_exists('approval_id',$exportRecord)||$exportRecord['approval_id']!==null){fwrite(STDERR,"Operational mitigation export did not normalize approval state.\n");exit(1);}
if(mitigation_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)"){fwrite(STDERR,"Mitigation CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/mitigations.php').file_get_contents($root.'/includes/app/mitigation_view.php');
$action=file_get_contents($root.'/app/mitigation-action.php');
$sql=file_get_contents($root.'/database/20260726_section13_mitigation_action_plans.sql');
foreach(['Mitigation Action Plans &amp; Supplier Contingency Management','Contingency trigger','Residual risk','Action ownership','Route approval','Activate plan','Protected CSV export'] as $needle){if(!str_contains($page,$needle)){fwrite(STDERR,"Mitigation workspace missing {$needle}.\n");exit(1);}}
foreach(['procurement_mitigation_plan','workflow_approvals','notifications','mitigation_action_updated','risk_contained','approvals.submit','savings.edit'] as $needle){if(!str_contains($action,$needle)){fwrite(STDERR,"Mitigation action workflow missing {$needle}.\n");exit(1);}}
foreach(['CREATE TABLE IF NOT EXISTS procurement_mitigation_plans','CREATE TABLE IF NOT EXISTS procurement_mitigation_actions','4.2-section13'] as $needle){if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 13 SQL missing {$needle}.\n");exit(1);}}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 13 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 13 mitigation action plan gates passed.\n");
