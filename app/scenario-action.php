<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/scenario_planning.php';
require_app_user();
if(request_method()!=='POST') redirect_to(app_url('scenarios.php'));
verify_csrf();

try {
    $action=post_string('action');
    if($action==='save') {
        require_permission('savings.edit');
        $id=post_int('id');
        $before=$id?scenario_find_record($id):null;
        if($id&&!$before) throw new RuntimeException('The procurement scenario is outside the active scope.');
        $raw=[];
        foreach(array_keys(scenario_default_inputs()) as $key) $raw[$key]=post_string($key,(string)scenario_default_inputs()[$key]);
        $simulation=scenario_calculate($raw);
        $title=trim(post_string('title'));
        $note=trim(post_string('decision_note'));
        if($title===''||$note==='') throw new RuntimeException('Scenario title and decision note are required.');
        $record=[
            'id'=>$id?:null,
            'scenario_number'=>$before['scenario_number']??scenario_next_number(),
            'company_id'=>current_company_id()==='enterprise'?null:(int)current_company_id(),
            'title'=>mb_substr($title,0,190),
            'scenario_type'=>$simulation['inputs']['scenario_type'],
            'supplier_id'=>$simulation['inputs']['supplier_id']?:null,
            'category_id'=>$simulation['inputs']['category_id']?:null,
            'inputs'=>$simulation['inputs'],
            'result_summary'=>[
                'net_impact'=>$simulation['net_impact'],
                'gross_impact'=>$simulation['gross_impact'],
                'risk_score'=>$simulation['risk_score'],
                'risk_level'=>$simulation['risk_level'],
                'service_risk'=>$simulation['service_risk'],
                'cash_risk'=>$simulation['cash_risk'],
                'active_assumptions'=>$simulation['active_assumptions'],
                'decision_note'=>mb_substr($note,0,5000),
            ],
            'risk_level'=>$simulation['risk_level'],
            'decision_status'=>$before['decision_status']??'draft',
            'decision_note'=>mb_substr($note,0,5000),
            'owner_id'=>(int)current_user()['id'],
            'approval_id'=>$before['approval_id']??null,
            'created_at'=>$before['created_at']??date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s'),
        ];
        $saved=scenario_save_record($record);
        data_add_audit('Scenario Planning',$before?'scenario_updated':'scenario_created','procurement_scenario',$saved['id'],$before,$saved,$saved['company_id']);
        flash('success','Procurement scenario saved: '.$saved['scenario_number'].'.');
        redirect_to(app_url('scenarios.php?id='.(int)$saved['id']));
    }
    if($action==='submit') {
        require_permission('approvals.submit');
        $id=post_int('id');
        $record=scenario_find_record($id);
        if(!$record) throw new RuntimeException('The procurement scenario is outside the active scope.');
        $status=scenario_effective_status($record);
        if(in_array($status,['pending','in_review','approved'],true)) throw new RuntimeException('This scenario is already in an active or completed approval workflow.');
        $reviewerId=(int)current_user()['id'];
        foreach(data_admin_visible_users() as $candidate) {
            if(($candidate['status']??'')==='active'&&can('approvals.review',$candidate)&&(int)$candidate['id']!==(int)current_user()['id']) {
                $reviewerId=(int)$candidate['id'];
                break;
            }
        }
        $companyId=$record['company_id']??data_default_company_id(current_user());
        $approval=data_upsert('workflow_approvals',[
            'id'=>null,
            'company_id'=>$companyId,
            'module'=>'reports',
            'entity_type'=>'procurement_scenario',
            'entity_id'=>$id,
            'title'=>$record['title'].' · '.$record['scenario_number'],
            'submitted_by'=>(int)current_user()['id'],
            'assigned_to'=>$reviewerId,
            'status'=>'pending',
            'submitted_at'=>date('Y-m-d H:i:s'),
            'due_date'=>date('Y-m-d',strtotime('+3 days')),
            'notes'=>'Review best, expected, and worst-case impacts, risk scores, alternative suppliers, and mitigation assumptions.',
        ]);
        $before=$record;
        $record['approval_id']=(int)$approval['id'];
        $record['decision_status']='submitted';
        $record=scenario_save_record($record);
        data_upsert('notifications',[
            'id'=>null,
            'company_id'=>$companyId,
            'user_id'=>$reviewerId,
            'title'=>'Procurement scenario awaiting review',
            'message'=>$record['scenario_number'].' carries '.status_label($record['risk_level']).' risk.',
            'severity'=>$record['risk_level'],
            'read'=>false,
            'created_at'=>date('Y-m-d H:i:s'),
        ]);
        data_add_audit('Scenario Planning','submitted','procurement_scenario',$id,$before,$record,$companyId);
        flash('success','Procurement scenario routed to Reviews & Approvals.');
        redirect_to(app_url('scenarios.php?id='.$id));
    }
    throw new RuntimeException('Unknown scenario action.');
} catch(Throwable $exception) {
    flash('error','The scenario action could not be completed: '.$exception->getMessage());
    redirect_to(app_url('scenarios.php'));
}
