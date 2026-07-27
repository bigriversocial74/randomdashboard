<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/work_management.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('work-management.php'));
verify_csrf();
$returnTo=safe_return_to(post_string('return_to'),'work-management.php');
try{
    $action=post_string('action');
    if($action==='sync_sources'){$result=work_management_sync_sources();$message='Source refresh created '.(int)$result['created'].' work item(s) and retained '.(int)$result['existing'].' existing item(s).';if($result['errors'])$message.=' '.count($result['errors']).' scoped source(s) could not be synchronized.';flash($result['errors']?'warning':'success',$message);redirect_to($returnTo);}
    if($action==='scan_escalations'){$result=work_management_run_escalation_scan();flash($result['errors']?'warning':'success','SLA scan escalated '.(int)$result['escalated'].' item(s); '.(int)$result['unchanged'].' remained unchanged.');redirect_to($returnTo);}
    if($action==='create_item'){$item=work_management_create_item(['source_module'=>post_string('source_module','manual'),'source_type'=>post_string('source_type','manual_work'),'source_id'=>post_int('source_id'),'entity_id'=>post_int('entity_id'),'work_type'=>post_string('work_type','general'),'title'=>post_string('title'),'description'=>post_string('description'),'priority'=>post_string('priority','medium'),'required_permission'=>post_string('required_permission','platform.view'),'evidence_required'=>true]);flash('success','Work item '.$item['work_number'].' created with governed routing.');redirect_to($returnTo);}
    $itemId=post_int('work_item_id');$note=post_string('evidence_note');
    if($action==='claim_item'){work_management_claim_item($itemId,$note);flash('success','Work item claimed and started.');redirect_to($returnTo);}
    if($action==='start_item'){work_management_start_item($itemId,$note);flash('success','Work item started.');redirect_to($returnTo);}
    if($action==='block_item'){work_management_block_item($itemId,$note);flash('success','Work item blocked with evidence.');redirect_to($returnTo);}
    if($action==='resume_item'){work_management_resume_item($itemId,$note);flash('success','Work item resumed.');redirect_to($returnTo);}
    if($action==='submit_review'){work_management_submit_review($itemId,$note);flash('success','Work item submitted for independent review.');redirect_to($returnTo);}
    if($action==='review_item'){work_management_review_item($itemId,$note);flash('success','Independent work review recorded.');redirect_to($returnTo);}
    if($action==='approve_item'){work_management_approve_item($itemId,$note);flash('success','Final approval recorded and work completed.');redirect_to($returnTo);}
    if($action==='complete_item'){work_management_complete_item($itemId,$note);flash('success','Work item completed with evidence.');redirect_to($returnTo);}
    if($action==='reassign_item'){work_management_reassign_item($itemId,post_int('assigned_user_id'),$note);flash('success','Work item reassigned within the permitted entity scope.');redirect_to($returnTo);}
    if($action==='escalate_item'){work_management_escalate_item($itemId,$note);flash('success','Work item escalated under its priority policy.');redirect_to($returnTo);}
    if($action==='cancel_item'){work_management_cancel_item($itemId,$note);flash('success','Work item cancelled without changing its canonical source record.');redirect_to($returnTo);}
    if($action==='create_rule'){$rule=work_management_create_rule(['rule_name'=>post_string('rule_name'),'rule_code'=>post_string('rule_code'),'trigger_event'=>post_string('trigger_event'),'source_module'=>post_string('source_module'),'entity_id'=>post_int('entity_id'),'reviewed_by'=>post_int('reviewed_by'),'approved_by'=>post_int('approved_by'),'idempotency_window_seconds'=>post_int('idempotency_window_seconds',86400),'condition_json'=>post_string('condition_json'),'action_json'=>post_string('action_json'),'evidence_note'=>post_string('evidence_note')]);flash('success','Automation rule '.$rule['rule_code'].' created as a governed draft.');redirect_to($returnTo);}
    if($action==='submit_rule'){work_management_submit_rule(post_int('version_id'),post_string('evidence_note'));flash('success','Automation rule submitted for independent review.');redirect_to($returnTo);}
    if($action==='review_rule'){work_management_review_rule(post_int('version_id'),post_string('evidence_note'));flash('success','Automation rule review completed.');redirect_to($returnTo);}
    if($action==='publish_rule'){work_management_publish_rule(post_int('version_id'),post_string('evidence_note'));flash('success','Automation rule published and permanently locked.');redirect_to($returnTo);}
    if($action==='suspend_rule'){work_management_suspend_rule(post_int('rule_id'),post_string('evidence_note'));flash('success','Automation rule suspended.');redirect_to($returnTo);}
    if($action==='simulate_rule'){$context=json_decode(post_string('context_json','{}'),true);if(!is_array($context))throw new RuntimeException('Simulation context must be valid JSON.');$result=work_management_simulate_rule(post_int('rule_id'),$context);flash('info','Simulation '.($result['matched']?'matched':'did not match').' and proposed action '.($result['action']['action']??'none').'. No records were changed.');redirect_to($returnTo);}
    if($action==='run_rule'){$context=json_decode(post_string('context_json','{}'),true);if(!is_array($context))throw new RuntimeException('Automation context must be valid JSON.');$run=work_management_run_rule(post_int('rule_id'),post_string('source_key'),$context);flash($run['status']==='completed'?'success':'error','Automation run '.$run['status'].' with replay-safe receipt #'.$run['id'].'.');redirect_to($returnTo);}
    throw new RuntimeException('Unknown work-management action.');
}catch(Throwable$exception){flash('error',$exception->getMessage());redirect_to($returnTo);}
