<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/work-management.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Section 26 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/work_management.php';
function s26(bool$condition,string$message):void{if(!$condition){fwrite(STDERR,"Section 26 failure: {$message}\n");exit(1);}}
function s26_user(int$userId,int|string$company='enterprise'):void{$_SESSION['gruber_demo_user_id']=$userId;$_SESSION['gruber_demo_company_id']=$company;}
function s26_throws(callable$callback,string$message):void{try{$callback();s26(false,$message);}catch(RuntimeException){}}

s26(work_management_tables_ready(),'Demo mode must report work-management tables ready.');
s26(count(work_management_tables())===8,'Section 26 must govern eight work, routing, automation, and escalation tables.');
s26(count(work_management_items())===12,'Demo mode must seed twelve cross-module work items.');
s26(count(work_management_routing_rules())===8,'Eight reusable routing rules are required.');
s26(count(work_management_automation_rules())===4,'Four supervised automation rules are required.');
s26(count(work_management_escalation_policies())===4,'Four priority escalation policies are required.');
$metrics=work_management_metrics();foreach(['open','my_work','critical','overdue','blocked','waiting_review','sla_compliance_percent','automation_rules']as$key)s26(array_key_exists($key,$metrics),'Metric '.$key.' must be available.');
$result=work_management_sync_sources();s26(isset($result['created'],$result['existing'],$result['errors']),'Source synchronization must return a governed receipt.');
$entity=work_management_find_entity_by_code('COMP-GPS');s26($entity!==null,'The GPS operating entity must resolve.');
$item=work_management_create_item(['source_module'=>'manual','source_type'=>'quality_test','source_id'=>2601,'entity_id'=>$entity['id'],'work_type'=>'general','title'=>'Section 26 governed work test','description'=>'Validate assignment, evidence, independent review, and completion without changing a canonical source record.','priority'=>'high','required_permission'=>'platform.view','assigned_user_id'=>1,'reviewer_id'=>6,'evidence_required'=>1,'sla_minutes'=>240]);
s26($item['status']==='open'&&$item['company_id']===2,'Manual work must preserve the operating company and start open.');
$item=work_management_start_item((int)$item['id'],'System administrator accepted execution ownership for the scoped test.');s26($item['status']==='in_progress','Authorized work must start in progress.');
$item=work_management_submit_review((int)$item['id'],'Execution evidence completed and submitted for independent review.');s26($item['status']==='waiting_review','Governed work must enter independent review.');
s26_user(6,2);$item=work_management_review_item((int)$item['id'],'Independent reviewer verified evidence and completed the work.');s26($item['status']==='completed','Independent review must complete work when no final approver is assigned.');
s26_user(4,1);s26(work_management_find_item((int)$item['id'])===null,'A sibling-company user must not resolve the GPS work item by ID.');foreach(work_management_events()as$event)s26((int)$event['work_item_id']!==(int)$item['id'],'Sibling-company event streams must not expose hidden work evidence.');

s26_user(1,'enterprise');
s26_throws(static fn()=>work_management_create_rule(['rule_name'=>'Invalid governance','rule_code'=>'AUTO-INVALID-26','trigger_event'=>'test.invalid','source_module'=>'manual','reviewed_by'=>1,'approved_by'=>1,'condition_json'=>'{"field":"status","operator":"equals","value":"open"}','action_json'=>'{"action":"create_work_item"}','evidence_note'=>'Invalid same-user governance.']),'Automation governance must reject repeated preparer, reviewer, and approver identities.');
$rule=work_management_create_rule(['rule_name'=>'Section 26 replay-safe test','rule_code'=>'AUTO-SECTION26-TEST','trigger_event'=>'test.work_required','source_module'=>'manual','reviewed_by'=>3,'approved_by'=>6,'condition_json'=>'{"field":"status","operator":"equals","value":"required"}','action_json'=>'{"action":"create_work_item","work_type":"general","priority":"medium","assigned_role_code":"data_contributor","sla_minutes":480}','evidence_note'=>'Test rule prepared from bounded condition and supervised action JSON.']);
$version=work_management_find_version((int)$rule['draft_version_id']);s26($version!==null&&$version['status']==='draft','New automation rules must begin as drafts.');
$version=work_management_submit_rule((int)$version['id'],'Draft rule submitted with test evidence.');
s26_user(3,'enterprise');$version=work_management_review_rule((int)$version['id'],'Independent reviewer verified condition, routing, and scope.');
s26_user(6,'enterprise');$version=work_management_publish_rule((int)$version['id'],'Approver authorized the locked supervised rule.');s26($version['status']==='published'&&!empty($version['locked_at']),'Published automation versions must be locked.');
s26_throws(static function()use($version):void{$version['action_json']='{"action":"escalate_work_item"}';work_management_save_automation_version($version);},'Published automation versions must be immutable.');

s26_user(1,'enterprise');$rule=work_management_find_rule((int)$rule['id']);$context=['status'=>'required','entity_id'=>(int)$entity['id'],'source_type'=>'quality_test','source_id'=>2602,'title'=>'Automated Section 26 test','description'=>'Replay-safe supervised automation created this supplemental work item.','required_permission'=>'platform.view'];$run=work_management_run_rule((int)$rule['id'],'section26-quality-source',$context);$repeat=work_management_run_rule((int)$rule['id'],'section26-quality-source',$context);s26($run['status']==='completed'&&(int)$run['id']===(int)$repeat['id'],'Automation replays must return the original immutable receipt.');s26(!empty($run['created_work_item_id']),'Matched create-work automation must create one supplemental work item.');
$scan=work_management_run_escalation_scan();s26(isset($scan['escalated'],$scan['unchanged'],$scan['errors']),'SLA scans must return a governed result.');
$workspace=work_management_agent_extend_workspace(['prompts'=>[],'lookups'=>[],'metrics'=>[],'briefing'=>['priorities'=>[]]]);s26(count($workspace['prompts'])===1&&($workspace['metrics']['work_items']??0)>0,'Agent Workspace must receive scoped work intelligence.');
$migration=file_get_contents($root.'/database/20260727_section26_enterprise_work_management_command_center.sql');foreach(work_management_tables()as$table)s26(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),'Migration must create '.$table.'.');s26(str_contains($migration,"'5.5-section26'"),'Migration version must be recorded.');s26(str_contains($migration,"'work_management.view'"),'Dedicated work-management permissions must be seeded.');s26(str_contains($migration,'idempotency_key CHAR(64) NOT NULL UNIQUE'),'Automation and escalation replay protection must be persisted.');
$export=file_get_contents($root.'/includes/app/work_management_export.php');s26(str_contains($export,'work_management_spreadsheet_safe'),'CSV export must protect against spreadsheet formula injection.');$action=file_get_contents($root.'/app/work-management-action.php');s26(str_contains($action,'verify_csrf'),'All work-management mutations must verify CSRF.');s26(str_contains($action,"action==='run_rule'"),'Governed automation execution must be available.');
echo "Section 26 enterprise work management, exceptions, SLAs, automation, and intelligence tests passed.\n";
