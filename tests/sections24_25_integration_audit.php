<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/process-maps.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Sections 24-25 integration audit';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/process_mapping.php';

function s2425(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"Sections 24-25 audit failure: {$message}\n");exit(1);}}
function s2425_user(int $userId,int|string $company='enterprise'):void{$_SESSION['gruber_demo_user_id']=$userId;$_SESSION['gruber_demo_company_id']=$company;}
function s2425_expect_runtime(callable $callback,string $message):void{try{$callback();s2425(false,$message);}catch(RuntimeException){}}

s2425(count(entity_system_bindings())===6,'Enterprise View must retain all six canonical company bindings.');
$codes=array_column(entity_system_all_entities(),'id','entity_code');
s2425(isset($codes['GRUBER-PROCUREMENT'],$codes['GRUBER-ACCOUNTS-PAYABLE'],$codes['COMP-GPS']),'Canonical shared-service and operating entity codes must resolve.');
$lanes=process_mapping_lanes(1);$laneByCode=[];foreach($lanes as$l)$laneByCode[$l['lane_code']]=$l;
s2425((int)$laneByCode['requesting_business']['entity_id']===(int)$codes['COMP-GPS'],'Requesting lane must resolve the GPS operating entity.');
s2425((int)$laneByCode['shared_procurement']['entity_id']===(int)$codes['GRUBER-PROCUREMENT'],'Procurement lane must resolve Shared Procurement.');
s2425((int)$laneByCode['shared_finance']['entity_id']===(int)$codes['GRUBER-ACCOUNTS-PAYABLE'],'Finance lane must resolve Shared Finance/AP.');

$companyOnePo=null;foreach(data_collection('purchase_orders')as$po)if((int)$po['company_id']===1){$companyOnePo=$po;break;}
s2425($companyOnePo!==null,'Audit requires a Company 1 purchase order.');
$instance=process_mapping_start_instance(1,(int)$codes['COMP-GC'],'purchase_order',(int)$companyOnePo['id'],'Company 1 procure-to-pay audit','Verified canonical Company 1 purchase order and process assignment.');
$steps=process_mapping_steps((int)$instance['version_id']);$lanes=process_mapping_lanes((int)$instance['version_id']);$laneMap=[];foreach($lanes as$l)$laneMap[(int)$l['id']]=$l;$stepInstances=process_mapping_step_instances((int)$instance['id']);$assigned=[];foreach($stepInstances as$si){$step=process_mapping_find_step((int)$si['step_id']);$lane=$laneMap[(int)$step['lane_id']];$assigned[$lane['lane_code']]=$si['assigned_entity_id'];}
s2425((int)$assigned['requesting_business']===(int)$codes['COMP-GC'],'Requesting work must follow the initiating operating company.');
s2425((int)$assigned['receiving_business']===(int)$codes['COMP-GC'],'Receiving work must follow the initiating operating company.');
s2425((int)$assigned['shared_procurement']===(int)$codes['GRUBER-PROCUREMENT'],'Procurement work must route to Shared Procurement.');
s2425((int)$assigned['shared_finance']===(int)$codes['GRUBER-ACCOUNTS-PAYABLE'],'Finance work must route to Shared Finance/AP.');
s2425($assigned['supplier']===null,'External supplier steps must not be assigned to an internal entity.');
s2425_expect_runtime(fn()=>process_mapping_start_instance(1,(int)$codes['COMP-GC'],'purchase_order',(int)$companyOnePo['id'],'Duplicate','Duplicate should fail.'),'Duplicate active process instances must be rejected.');
s2425_expect_runtime(fn()=>process_mapping_start_instance(1,(int)$codes['COMP-GPS'],'purchase_order',(int)$companyOnePo['id'],'Wrong company','Mismatched record should fail.'),'A canonical record must not start under a different company entity.');
s2425_expect_runtime(fn()=>process_mapping_create_from_template('procure_to_pay','Invalid same-user governance.',1,1),'Template governance must require three distinct users.');

s2425_user(4,1);
$scopedOperating=array_values(array_filter(entity_system_entities(),static fn(array$e):bool=>$e['entity_type']==='operating_company'));
$scopedCompanyIds=array_values(array_unique(array_map('intval',array_column($scopedOperating,'company_id'))));sort($scopedCompanyIds);
s2425($scopedCompanyIds===[1,4,6],'Company administrator must see only assigned operating companies.');
s2425(entity_system_find_by_code('COMP-GPS')!==null,'Raw code lookup may support governed templates, but visible lookup must remain scoped.');
s2425(entity_system_find_entity((int)$codes['COMP-GPS'])===null,'A sibling operating entity must not be directly accessible.');
foreach(entity_system_external_mappings()as$m)s2425(in_array((int)$m['entity_id'],entity_system_visible_entity_ids(),true),'External mappings must be filtered to visible entities.');
s2425(process_mapping_find_instance(1)===null,'Company 1/4/6 administrator must not see the Company 2 live process instance.');
foreach(process_mapping_instances()as$i)s2425(in_array((int)$i['entity_id'],entity_system_visible_entity_ids(),true),'Process instances must be filtered by visible entity.');
$readiness=entity_system_readiness();s2425($readiness['ready']===true,'Scoped readiness must not report hidden sibling companies as missing.');

s2425_user(5,2);
s2425(process_mapping_find_instance(1)!==null,'Company 2/3/5 contributor must see the Company 2 process instance.');
$active=array_values(array_filter(process_mapping_step_instances(1),static fn(array$r):bool=>$r['status']==='active'));
s2425(count($active)===1,'Seeded instance must expose exactly one active step.');
s2425_expect_runtime(fn()=>process_mapping_advance_step((int)$active[0]['id'],'complete','Attempt without supplier review permission.'),'A user lacking the step permission must not advance the process.');

s2425_user(3,2);
$advanced=process_mapping_advance_step((int)$active[0]['id'],'complete','Supplier and contract evidence independently reviewed.');
s2425($advanced['status']==='in_progress','Authorized procurement manager must advance the active step.');

s2425_user(1,'enterprise');
$metrics=entity_system_metrics();s2425($metrics['preserved_company_ids']===true&&$metrics['scope_company_count']===6,'Enterprise metrics must confirm all six preserved company bindings.');
s2425(count(process_mapping_processes())===9,'Enterprise process catalog must retain all nine visual templates.');

echo "Sections 24-25 multi-corporation and process integration audit tests passed.\n";
