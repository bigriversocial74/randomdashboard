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
function a2425(bool $condition,string $message):void{if(!$condition){fwrite(STDERR,"Sections 24-25 audit failure: {$message}\n");exit(1);}}
function a2425_context(int $userId,int|string $company):void{$_SESSION['gruber_demo_user_id']=$userId;$_SESSION['gruber_demo_company_id']=$company;}
function a2425_throws(callable $callback,string $message):void{try{$callback();}catch(RuntimeException){return;}a2425(false,$message);}

// Enterprise baseline: all canonical company/entity relationships and all nine process maps are visible.
a2425_context(1,'enterprise');
a2425(count(entity_system_bindings())===6,'Enterprise View must retain six canonical company bindings.');
a2425(count(process_mapping_processes())===9,'Enterprise View must expose all nine reusable process maps.');
a2425(entity_system_find_by_code('GRUBER-ACCOUNTS-PAYABLE')!==null,'Demo and production must share the canonical finance entity code.');

// Company-scoped user: selected operating company plus governing shared services, never sibling companies.
a2425_context(5,2);
$entityIds=array_map('intval',array_column(entity_system_entities(),'id'));
a2425(in_array(102,$entityIds,true),'GPS operating entity must be visible in GPS context.');
a2425(in_array(2,$entityIds,true)&&in_array(3,$entityIds,true),'GPS must see its shared procurement and finance service entities.');
a2425(!in_array(101,$entityIds,true)&&!in_array(103,$entityIds,true),'GPS context must not expose sibling operating entities.');
foreach(entity_system_external_mappings()as $mapping)a2425((int)$mapping['entity_id']===102,'External ID mappings must be filtered to the active operating company.');
a2425(count(process_mapping_instances())===1&&(int)process_mapping_instances()[0]['entity_id']===102,'GPS context must see only its live process instances.');
$readiness=entity_system_readiness();
a2425(!array_filter($readiness['issues'],static fn(string $issue):bool=>str_contains($issue,'Company 1')||str_contains($issue,'Company 3')),'Company readiness must not report hidden sibling entities as missing.');

// Cross-company IDOR and forged context attempts.
a2425_context(4,1);
a2425(process_mapping_find_instance(1)===null,'A GC-scoped user must not resolve the GPS process instance by ID.');
a2425(process_mapping_step_instances()===[],'A GC-scoped user must not read GPS process-step evidence.');
a2425_throws(static fn()=>process_mapping_start_instance(1,102,'purchase_order',1,'Forged GPS process','Attempted cross-company start.'),'A forged GPS entity ID must be rejected from GC context.');

// Enterprise governance requires three distinct active users and Enterprise View.
a2425_context(1,'enterprise');
a2425_throws(static fn()=>process_mapping_create_from_template('procure_to_pay','Invalid same-person governance.',1,1),'Process governance must reject repeated preparer/reviewer/approver identities.');
$copy=process_mapping_create_from_template('procure_to_pay','Sections 24-25 audit copy reviewed against entity hierarchy, access scopes, shared services, canonical records, controls, and integrations.',3,6);
$version=process_mapping_process_version($copy);
a2425($version!==null&&$version['status']==='draft','Audit process copy must start as a draft.');
$lanes=process_mapping_lanes((int)$version['id']);
$laneByCode=[];foreach($lanes as $lane)$laneByCode[$lane['lane_code']]=$lane;
a2425(empty($laneByCode['requesting_business']['entity_id']),'Reusable requesting-business lane must resolve from the initiating operating entity.');
a2425((int)$laneByCode['shared_procurement']['entity_id']===2,'Shared procurement lane must bind to the canonical Section 24 entity.');
a2425((int)$laneByCode['shared_finance']['entity_id']===3,'Shared finance lane must bind to the canonical Section 24 entity.');
$version=process_mapping_publish_version($version,'Independent reviewer and approver evidence completed; audit process map locked.');
a2425($version['status']==='published'&&!empty($version['locked_at']),'Published audit version must be permanently locked.');
a2425_throws(static function()use($laneByCode):void{process_mapping_save_lane($laneByCode['shared_procurement']);},'Repository writes must not edit a published lane.');

// Lane-based live ownership and duplicate-instance prevention.
$instance=process_mapping_start_instance((int)$copy['id'],102,'purchase_order',8,'GPS governed procure-to-pay audit','Authorized GPS purchase order 8 linked to the published process.');
$steps=process_mapping_steps((int)$version['id']);$stepByCode=[];foreach($steps as $step)$stepByCode[$step['step_code']]=$step;
$stepInstances=process_mapping_step_instances((int)$instance['id']);$assignmentByStep=[];foreach($stepInstances as $stepInstance)$assignmentByStep[(int)$stepInstance['step_id']]=$stepInstance['assigned_entity_id'];
a2425((int)$assignmentByStep[(int)$stepByCode['start']['id']]===102,'Initiating lane steps must belong to the GPS operating entity.');
a2425((int)$assignmentByStep[(int)$stepByCode['source_supplier']['id']]===2,'Shared procurement steps must belong to the procurement service entity.');
a2425($assignmentByStep[(int)$stepByCode['supplier_ack']['id']]===null,'External supplier steps must not be assigned internal entity access.');
a2425((int)$assignmentByStep[(int)$stepByCode['invoice_match']['id']]===3,'Shared finance steps must belong to the finance service entity.');
a2425_throws(static fn()=>process_mapping_start_instance((int)$copy['id'],102,'purchase_order',8,'Duplicate retry','Repeated integration event.'),'A repeated canonical event must not create a duplicate active process instance.');

// Scoped execution and evidence access.
a2425_context(5,2);
$visible=process_mapping_find_instance((int)$instance['id']);
a2425($visible!==null,'Authorized GPS user must see the new GPS process instance.');
$active=array_values(array_filter(process_mapping_step_instances((int)$instance['id']),static fn(array $row):bool=>$row['status']==='active'));
a2425(count($active)===1,'Exactly one scoped step may be active.');
process_mapping_advance_step((int)$active[0]['id'],'complete','GPS contributor recorded governed start evidence.');

// Sibling company remains isolated after state transitions and events are written.
a2425_context(4,1);
a2425(process_mapping_find_instance((int)$instance['id'])===null,'GC user must not see the GPS instance after a transition.');
foreach(process_mapping_events()as $event)a2425((int)($event['instance_id']??0)!==(int)$instance['id'],'GC event stream must not expose GPS process evidence.');

// Company context cannot administer enterprise templates even with a broad company-admin role.
a2425_throws(static fn()=>process_mapping_create_from_template('supplier_onboarding','Attempted local template administration.',3,6),'Company context must not create enterprise process templates.');
a2425_throws(static fn()=>entity_system_apply_template('independent_businesses','Attempted local entity template application.',3,6),'Company context must not apply enterprise entity templates.');

$migration=file_get_contents($root.'/database/20260727_sections24_25_integration_audit_hardening.sql');
a2425(str_contains($migration,"'5.5-sections24-25-audit'"),'Audit migration version must be recorded.');
a2425(str_contains($migration,'business_process_step_instances'),'Audit migration must repair lane-based step ownership.');
a2425(str_contains($migration,'role_permissions'),'Audit migration must map dedicated entity and process permissions.');
$repository=file_get_contents($root.'/includes/app/process_mapping_repository_core.php')
    .file_get_contents($root.'/includes/app/process_mapping_repository_scope.php')
    .file_get_contents($root.'/includes/app/process_mapping_repository_storage.php');
a2425(str_contains($repository,'process_mapping_visible_process_ids'),'Process repository must implement entity-scoped visibility.');
a2425(str_contains($repository,'process_mapping_assert_version_mutable'),'Process repository must enforce published-version immutability.');
$engine=file_get_contents($root.'/includes/app/process_mapping_engine_core.php')
    .file_get_contents($root.'/includes/app/process_mapping_engine_design.php')
    .file_get_contents($root.'/includes/app/process_mapping_engine_runtime.php')
    .file_get_contents($root.'/includes/app/process_mapping_engine_execution.php');
a2425(str_contains($engine,'GET_LOCK'),'Production duplicate prevention must serialize canonical process starts.');
a2425(str_contains($engine,'process_mapping_assert_canonical_record_scope'),'Live processes must validate canonical-record company scope.');

echo "Sections 24-25 multi-corporation governance and process orchestration integration audit passed.\n";
