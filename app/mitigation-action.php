<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/execution_management.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('mitigations.php'));
verify_csrf();

try{
    $action=post_string('action');
    if($action==='save_plan'){
        require_permission('savings.edit');
        $id=post_int('id');$before=$id?mitigation_find_record($id):null;
        if($id&&!$before)throw new RuntimeException('The mitigation plan is outside the active scope.');
        $scenarioId=post_int('scenario_id');$scenario=$scenarioId?scenario_find_record($scenarioId):null;
        if($scenarioId&&!$scenario)throw new RuntimeException('The source scenario is outside the active scope.');
        $blueprint=mitigation_default_blueprint($scenario);$simulation=$blueprint['simulation'];
        $title=trim(post_string('title'));$summary=trim(post_string('summary'));$activationNotes=trim(post_string('activation_notes'));
        if($title===''||$summary===''||$activationNotes==='')throw new RuntimeException('Plan title, summary, and activation notes are required.');
        $triggerType=post_string('trigger_type','risk_score');if(!isset(mitigation_trigger_types()[$triggerType]))$triggerType='risk_score';
        $triggerOperator=post_string('trigger_operator','>=');if(!in_array($triggerOperator,['>=','>','=','<=','<'],true))$triggerOperator='>=';
        $triggerValue=max(0,min(100000,(float)post_string('trigger_value','0')));
        $record=[
            'id'=>$id?:null,'plan_number'=>$before['plan_number']??mitigation_next_number(),
            'company_id'=>current_company_id()==='enterprise'?null:(int)current_company_id(),'scenario_id'=>$scenarioId?:null,
            'supplier_id'=>(int)$simulation['inputs']['supplier_id']?:null,'category_id'=>(int)$simulation['inputs']['category_id']?:null,
            'title'=>mb_substr($title,0,190),'trigger_type'=>$triggerType,'trigger_operator'=>$triggerOperator,'trigger_value'=>$triggerValue,
            'source_risk_score'=>(float)$simulation['risk_score'],'source_net_impact'=>(float)$simulation['net_impact'],'risk_level'=>(string)$simulation['risk_level'],
            'status'=>$before['status']??'draft','owner_id'=>(int)current_user()['id'],'approval_id'=>$before['approval_id']??null,
            'summary'=>mb_substr($summary,0,5000),'activation_notes'=>mb_substr($activationNotes,0,5000),
            'created_at'=>$before['created_at']??date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),
        ];
        $saved=mitigation_save_plan($record);
        $titles=is_array($_POST['action_title']??null)?$_POST['action_title']:[];$actionRows=[];
        foreach($titles as $index=>$rowTitle){
            $actionRows[]=[
                'action_type'=>(string)(($_POST['action_type'][$index]??'supplier_recovery')),'title'=>(string)$rowTitle,
                'owner_id'=>(int)($_POST['action_owner_id'][$index]??current_user()['id']),'priority'=>(string)($_POST['action_priority'][$index]??'medium'),
                'status'=>(string)($_POST['action_status'][$index]??'planned'),'due_date'=>(string)($_POST['action_due_date'][$index]??date('Y-m-d',strtotime('+14 days'))),
                'estimated_cost'=>(float)($_POST['action_estimated_cost'][$index]??0),'recovery_value'=>(float)($_POST['action_recovery_value'][$index]??0),
                'service_risk_reduction'=>(float)($_POST['action_service_risk_reduction'][$index]??0),'readiness_pct'=>(float)($_POST['action_readiness_pct'][$index]??0),
                'supplier_id'=>(int)($_POST['action_supplier_id'][$index]??0),'notes'=>(string)($_POST['action_notes'][$index]??''),
            ];
        }
        $savedActions=mitigation_replace_actions((int)$saved['id'],$actionRows);$metrics=mitigation_calculate_metrics($saved,$savedActions,$simulation);
        data_add_audit('Mitigation Planning',$before?'mitigation_plan_updated':'mitigation_plan_created','procurement_mitigation_plan',$saved['id'],$before,['plan'=>$saved,'metrics'=>$metrics],$saved['company_id']);
        flash('success','Mitigation plan saved: '.$saved['plan_number'].'.');redirect_to(app_url('mitigations.php?id='.(int)$saved['id']));
    }
    if($action==='update_action'){
        require_permission('savings.edit');$id=post_int('id');$before=mitigation_find_action($id);if(!$before)throw new RuntimeException('The mitigation action is outside the active scope.');
        $status=post_string('status',(string)$before['status']);if(!in_array($status,mitigation_action_statuses(),true))throw new RuntimeException('The selected action status is invalid.');
        $priority=post_string('priority',(string)$before['priority']);if(!in_array($priority,mitigation_priorities(),true))throw new RuntimeException('The selected priority is invalid.');
        $dueDate=post_string('due_date',(string)$before['due_date']);if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$dueDate))throw new RuntimeException('A valid action due date is required.');
        $saved=mitigation_save_action(['id'=>$id,'owner_id'=>max(1,post_int('owner_id')),'priority'=>$priority,'status'=>$status,'due_date'=>$dueDate,'readiness_pct'=>max(0,min(100,(float)post_string('readiness_pct','0'))),'notes'=>mb_substr(trim(post_string('notes')),0,5000)]);
        $plan=mitigation_find_record((int)$saved['plan_id']);data_add_audit('Mitigation Planning','mitigation_action_updated','procurement_mitigation_action',$id,$before,$saved,$plan['company_id']??null);
        if($status==='blocked'&&$plan){data_upsert('notifications',['id'=>null,'company_id'=>$plan['company_id']??data_default_company_id(current_user()),'user_id'=>(int)$plan['owner_id'],'title'=>'Mitigation action blocked','message'=>$saved['title'].' requires intervention in '.$plan['plan_number'].'.','severity'=>'high','read'=>false,'created_at'=>date('Y-m-d H:i:s')]);}
        flash('success','Mitigation action updated.');redirect_to(app_url('mitigations.php?id='.(int)$saved['plan_id']));
    }
    if($action==='submit'){
        require_permission('approvals.submit');$id=post_int('id');$record=mitigation_find_record($id);if(!$record)throw new RuntimeException('The mitigation plan is outside the active scope.');
        $status=mitigation_effective_status($record);if(in_array($status,['pending','in_review','approved','active','contained','closed'],true))throw new RuntimeException('This plan is already routed, approved, or active.');
        $actions=mitigation_actions($id);if(!$actions)throw new RuntimeException('The plan must contain at least one action before approval routing.');
        $reviewerId=(int)current_user()['id'];foreach(data_admin_visible_users() as $candidate){if(($candidate['status']??'')==='active'&&can('approvals.review',$candidate)&&(int)$candidate['id']!==(int)current_user()['id']){$reviewerId=(int)$candidate['id'];break;}}
        $companyId=$record['company_id']??data_default_company_id(current_user());$metrics=mitigation_calculate_metrics($record,$actions);
        $approval=data_upsert('workflow_approvals',['id'=>null,'company_id'=>$companyId,'module'=>'reports','entity_type'=>'procurement_mitigation_plan','entity_id'=>$id,'title'=>$record['title'].' · '.$record['plan_number'],'submitted_by'=>(int)current_user()['id'],'assigned_to'=>$reviewerId,'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+2 days')),'notes'=>'Review trigger quality, action ownership, due dates, estimated mitigation cost, recovery value, contingency coverage, residual risk, and supplier alternatives.']);
        $before=$record;$record['approval_id']=(int)$approval['id'];$record['status']='submitted';$record=mitigation_save_plan($record);
        data_upsert('notifications',['id'=>null,'company_id'=>$companyId,'user_id'=>$reviewerId,'title'=>'Mitigation plan awaiting review','message'=>$record['plan_number'].' has '.$metrics['action_count'].' actions and '.number_format((float)$metrics['coverage_pct'],1).'% modeled coverage.','severity'=>$record['risk_level'],'read'=>false,'created_at'=>date('Y-m-d H:i:s')]);
        data_add_audit('Mitigation Planning','submitted','procurement_mitigation_plan',$id,$before,$record,$companyId);flash('success','Mitigation plan routed to Reviews & Approvals.');redirect_to(app_url('mitigations.php?id='.$id));
    }
    if($action==='activate'){
        require_permission('savings.edit');$id=post_int('id');$record=mitigation_find_record($id);if(!$record)throw new RuntimeException('The mitigation plan is outside the active scope.');
        if(mitigation_effective_status($record)!=='approved')throw new RuntimeException('The mitigation plan must be approved before activation.');
        $before=$record;$record['status']='active';$record=mitigation_save_plan($record);$companyId=$record['company_id']??data_default_company_id(current_user());
        $owners=[];foreach(mitigation_actions($id) as $row)$owners[(int)$row['owner_id']]=true;foreach(array_keys($owners) as $ownerId)data_upsert('notifications',['id'=>null,'company_id'=>$companyId,'user_id'=>$ownerId,'title'=>'Mitigation plan activated','message'=>$record['plan_number'].' is active. Review your assigned actions and due dates.','severity'=>$record['risk_level'],'read'=>false,'created_at'=>date('Y-m-d H:i:s')]);
        data_add_audit('Mitigation Planning','activated','procurement_mitigation_plan',$id,$before,$record,$companyId);flash('success','Mitigation plan activated and action owners notified.');redirect_to(app_url('mitigations.php?id='.$id));
    }
    if($action==='contain'){
        require_permission('savings.edit');$id=post_int('id');$record=mitigation_find_record($id);if(!$record)throw new RuntimeException('The mitigation plan is outside the active scope.');
        $actions=mitigation_actions($id);foreach($actions as $row)if(!in_array((string)$row['status'],['completed','cancelled'],true))throw new RuntimeException('Complete or cancel every action before marking the risk contained.');
        $completedCount=count(array_filter($actions,static fn(array $row): bool => ($row['status']??'')==='completed'));
        if($completedCount>0){if(data_is_production()&&!execution_tables_ready())throw new RuntimeException('Import the Section 14 execution migration before containment verification.');$readiness=execution_plan_readiness($id);if(!$readiness['ready'])throw new RuntimeException('Every completed mitigation action requires a verified execution record before the risk can be marked contained.');}
        $before=$record;$record['status']='contained';$record=mitigation_save_plan($record);data_add_audit('Mitigation Planning','risk_contained','procurement_mitigation_plan',$id,$before,$record,$record['company_id']);flash('success','The procurement risk is marked contained.');redirect_to(app_url('mitigations.php?id='.$id));
    }
    throw new RuntimeException('Unknown mitigation action.');
}catch(Throwable $exception){flash('error','The mitigation action could not be completed: '.$exception->getMessage());redirect_to(app_url('mitigations.php'));}
