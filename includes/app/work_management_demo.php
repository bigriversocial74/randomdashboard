<?php
declare(strict_types=1);

function work_management_demo_items(): array
{
    $now=time();
    $definitions=[
        [1,'WORK-2026-0001','purchase_orders','purchase_order',1,102,2,'commitment_exception','Escalate past-due GPS-10428','The critical-power purchase order is past due and requires supplier escalation, recovery evidence, and a revised delivery commitment.','critical','blocked',2,3,'procurement_manager',6,null,'purchase_orders.edit',-720,2,null,3],
        [2,'WORK-2026-0002','process_mapping','process_step',5,101,1,'process_step','Validate supplier and contract','The live Procure-to-Pay instance is waiting for Shared Procurement to validate supplier, contract, and sourcing evidence.','high','in_progress',2,3,'procurement_manager',6,null,'suppliers.review',480,0,1,5],
        [3,'WORK-2026-0003','approvals','workflow_approval',1,101,1,'approval','Review communications purchase approval','Review budget, supplier, and request evidence before approving the communications purchase.','high','waiting_review',101,5,'reviewer',6,null,'approvals.review',180,0,null,5],
        [4,'WORK-2026-0004','inventory','inventory_shortage',5,103,3,'inventory_exception','Resolve GTS field-stock shortage','Available field stock is below the operating threshold for an active service requirement.','high','open',103,5,'data_contributor',6,null,'inventory.edit',360,0,null,1],
        [5,'WORK-2026-0005','contracts','contract_renewal',2,104,4,'contract_renewal','Prepare motor-services contract renewal','The supplier contract enters its renewal decision window and requires performance, risk, pricing, and exit analysis.','medium','open',2,4,'procurement_manager',6,2,'strategy.edit',1440,0,null,1],
        [6,'WORK-2026-0006','scorecards','supplier_corrective_action',4,105,5,'supplier_quality','Review EV supplier corrective action','A supplier performance decline requires containment evidence, root-cause review, and an approved corrective-action plan.','high','in_progress',2,3,'procurement_manager',6,null,'scorecards.edit',720,0,null,3],
        [7,'WORK-2026-0007','accounts_payable','invoice_match_exception',2,106,6,'financial_exception','Clear GCP three-way match exception','Invoice quantity and receipt evidence do not reconcile. Shared Finance must review before payment can proceed.','critical','blocked',3,6,'reviewer',2,null,'accounts_payable.review',-90,1,null,6],
        [8,'WORK-2026-0008','data_collection','data_quality_exception',1,101,1,'data_quality','Correct supplier tax classification','The supplier master contains incomplete classification evidence and cannot be treated as approved until independently reviewed.','medium','open',101,4,'company_administrator',6,null,'suppliers.edit',960,0,null,4],
        [9,'WORK-2026-0009','process_mapping','process_exception',1,102,2,'process_exception','Review blocked budget-variance path','A process exception requires an independent reviewer before the active step can resume.','critical','waiting_review',102,5,'reviewer',6,null,'approvals.review',120,0,1,5],
        [10,'WORK-2026-0010','savings','savings_validation',2,103,3,'finance_validation','Validate sourcing savings baseline','Finance must validate the baseline, implementation cost, and evidence period before savings can be certified.','medium','waiting_review',3,3,'reviewer',6,2,'savings.review',1080,0,null,3],
        [11,'WORK-2026-0011','supplier_portal','supplier_response',3,104,4,'supplier_response','Follow up on supplier shipment response','The supplier has not confirmed the expected shipment and the operating company requires a documented response.','high','open',2,3,'procurement_manager',6,null,'supplier_portal.review',300,0,null,3],
        [12,'WORK-2026-0012','inventory','cycle_count_review',7,105,5,'inventory_control','Certify EV Preserve cycle count','The count variance was reviewed and the approved evidence package is ready for closure.','low','completed',105,5,'data_contributor',6,null,'inventory.review',-2880,0,null,5],
    ];
    $rows=[];
    foreach($definitions as$d){
        [$id,$number,$module,$sourceType,$sourceId,$entityId,$companyId,$workType,$title,$description,$priority,$status,$assignedEntity,$assignedUser,$role,$reviewer,$approver,$permission,$dueMinutes,$level,$processInstance,$createdBy]=$d;
        $created=date('Y-m-d H:i:s',$now-(max(360,abs($dueMinutes)+180)*60));
        $active=in_array($status,['in_progress','blocked','waiting_review','waiting_approval','completed'],true);
        $rows[]=[
            'id'=>$id,'work_number'=>$number,'source_key'=>hash('sha256',$module.'|'.$sourceType.'|'.$sourceId.'|'.$workType.'|'.$entityId),
            'source_module'=>$module,'source_type'=>$sourceType,'source_id'=>$sourceId,'entity_id'=>$entityId,'company_id'=>$companyId,
            'process_instance_id'=>$processInstance,'step_instance_id'=>$sourceType==='process_step'?$sourceId:null,'work_type'=>$workType,
            'title'=>$title,'description'=>$description,'priority'=>$priority,'status'=>$status,'assigned_entity_id'=>$assignedEntity,
            'assigned_user_id'=>$assignedUser,'assigned_role_code'=>$role,'reviewer_id'=>$reviewer,'approver_id'=>$approver,
            'required_permission'=>$permission,'evidence_required'=>1,'due_at'=>date('Y-m-d H:i:s',$now+$dueMinutes*60),
            'warning_at'=>date('Y-m-d H:i:s',$now+($dueMinutes-120)*60),'escalation_level'=>$level,
            'automation_rule_id'=>in_array($id,[1,7,9,11],true)?1:null,'created_by'=>$createdBy,
            'completed_by'=>$status==='completed'?6:null,'claimed_at'=>$active?date('Y-m-d H:i:s',strtotime($created)+1800):null,
            'started_at'=>$active?date('Y-m-d H:i:s',strtotime($created)+3600):null,
            'completed_at'=>$status==='completed'?date('Y-m-d H:i:s',$now-1800):null,'last_activity_at'=>date('Y-m-d H:i:s',$now-900),
            'lock_version'=>1,'created_at'=>$created,'updated_at'=>date('Y-m-d H:i:s',$now-900),
        ];
    }
    return $rows;
}

function work_management_demo_events(): array
{
    $rows=[];$id=1;
    foreach(work_management_demo_items()as$item){
        $rows[]=['id'=>$id++,'work_item_id'=>$item['id'],'event_type'=>'work_created','from_status'=>null,'to_status'=>'open','actor_user_id'=>$item['created_by'],'assigned_entity_id'=>$item['assigned_entity_id'],'assigned_user_id'=>$item['assigned_user_id'],'severity'=>$item['priority'],'evidence_note'=>'Work item created from governed source evidence.','metadata_json'=>'{}','created_at'=>$item['created_at']];
        if($item['status']!=='open')$rows[]=['id'=>$id++,'work_item_id'=>$item['id'],'event_type'=>'status_changed','from_status'=>'open','to_status'=>$item['status'],'actor_user_id'=>$item['assigned_user_id'],'assigned_entity_id'=>$item['assigned_entity_id'],'assigned_user_id'=>$item['assigned_user_id'],'severity'=>$item['priority'],'evidence_note'=>'Seeded governed work progression for the demonstration workspace.','metadata_json'=>'{}','created_at'=>$item['updated_at']];
    }
    return $rows;
}

function work_management_demo_routing_rules(): array
{
    $defs=[
        ['ROUTE-PO-OVERDUE','Past-due purchase orders','purchase_orders','commitment_exception','procurement_manager','GRUBER-PROCUREMENT','critical',240,'reviewer','executive'],
        ['ROUTE-PROCESS-STEP','Active process steps','process_mapping','process_step','procurement_manager','',null,480,'reviewer',''],
        ['ROUTE-PROCESS-EXCEPTION','Process control exceptions','process_mapping','process_exception','reviewer','', 'high',120,'reviewer',''],
        ['ROUTE-APPROVAL','Approval decisions','approvals','approval','reviewer','', 'high',480,'reviewer',''],
        ['ROUTE-AP-EXCEPTION','Accounts payable exceptions','accounts_payable','financial_exception','reviewer','GRUBER-ACCOUNTS-PAYABLE','critical',240,'reviewer','executive'],
        ['ROUTE-INVENTORY','Inventory exceptions','inventory','inventory_exception','data_contributor','', 'high',360,'reviewer',''],
        ['ROUTE-CONTRACT','Contract renewal work','contracts','contract_renewal','procurement_manager','GRUBER-PROCUREMENT','medium',1440,'reviewer','executive'],
        ['ROUTE-SUPPLIER-RESPONSE','Supplier response follow-up','supplier_portal','supplier_response','procurement_manager','GRUBER-PROCUREMENT','high',300,'reviewer',''],
    ];
    $rows=[];
    foreach($defs as$i=>$d){[$code,$name,$module,$type,$role,$service,$priority,$sla,$reviewer,$approver]=$d;$rows[]=['id'=>$i+1,'rule_code'=>$code,'rule_name'=>$name,'source_module'=>$module,'work_type'=>$type,'entity_id'=>null,'assigned_role_code'=>$role,'shared_service_entity_code'=>$service,'priority_override'=>$priority,'sla_minutes'=>$sla,'reviewer_role_code'=>$reviewer,'approver_role_code'=>$approver,'status'=>'active','sort_order'=>($i+1)*10,'evidence_note'=>'Governed Section 26 routing rule.','prepared_by'=>1,'reviewed_by'=>3,'approved_by'=>6,'effective_from'=>'2026-07-27','effective_to'=>null,'locked_at'=>'2026-07-27 08:00:00','created_at'=>'2026-07-27 08:00:00','updated_at'=>'2026-07-27 08:00:00'];}
    return $rows;
}

function work_management_demo_automation_rules(): array
{
    $defs=[
        ['AUTO-OVERDUE-COMMITMENT','Create overdue commitment work','purchase_order.overdue','purchase_orders'],
        ['AUTO-PROCESS-EXCEPTION','Create process exception review','process.exception_opened','process_mapping'],
        ['AUTO-INVOICE-MATCH','Route invoice-match exceptions','invoice.match_exception','accounts_payable'],
        ['AUTO-SLA-ESCALATION','Escalate overdue work','work_item.overdue','work_management'],
    ];
    $rows=[];foreach($defs as$i=>$d)$rows[]=['id'=>$i+1,'rule_code'=>$d[0],'rule_name'=>$d[1],'trigger_event'=>$d[2],'source_module'=>$d[3],'entity_id'=>null,'status'=>'active','active_version_id'=>$i+1,'created_by'=>1,'created_at'=>'2026-07-27 08:00:00','updated_at'=>'2026-07-27 08:00:00'];return$rows;
}

function work_management_demo_automation_versions(): array
{
    $defs=[
        ['{"field":"status","operator":"equals","value":"past_due"}','{"action":"create_work_item","work_type":"commitment_exception","priority":"critical","assigned_role_code":"procurement_manager","shared_service_entity_code":"GRUBER-PROCUREMENT","sla_minutes":240}'],
        ['{"field":"status","operator":"in","value":["open","blocked"]}','{"action":"create_work_item","work_type":"process_exception","priority":"high","assigned_role_code":"reviewer","sla_minutes":120}'],
        ['{"field":"match_status","operator":"in","value":["exception","blocked","variance"]}','{"action":"create_work_item","work_type":"financial_exception","priority":"critical","assigned_role_code":"reviewer","shared_service_entity_code":"GRUBER-ACCOUNTS-PAYABLE","sla_minutes":240}'],
        ['{"field":"overdue_minutes","operator":"greater_than","value":0}','{"action":"escalate_work_item","policy":"PRIORITY-DEFAULT"}'],
    ];
    $rows=[];foreach($defs as$i=>$d)$rows[]=['id'=>$i+1,'rule_id'=>$i+1,'version_number'=>'1.0','status'=>'published','condition_json'=>$d[0],'action_json'=>$d[1],'idempotency_window_seconds'=>86400,'prepared_by'=>1,'reviewed_by'=>3,'approved_by'=>6,'review_note'=>'Rule logic and scope independently reviewed.','approval_note'=>'Rule approved for supervised execution.','evidence_note'=>'Published starting automation rule.','published_at'=>'2026-07-27 08:00:00','locked_at'=>'2026-07-27 08:00:00','created_at'=>'2026-07-27 08:00:00','updated_at'=>'2026-07-27 08:00:00'];return$rows;
}

function work_management_demo_automation_runs(): array
{
    return [
        ['id'=>1,'rule_id'=>1,'version_id'=>1,'source_key'=>'purchase_order|1|past_due','idempotency_key'=>hash('sha256','1|1|purchase_order|1|past_due'),'status'=>'completed','matched_count'=>1,'created_work_item_id'=>1,'result_json'=>'{"result":"work_created"}','error_message'=>'','started_at'=>date('Y-m-d H:i:s',strtotime('-2 hours')),'completed_at'=>date('Y-m-d H:i:s',strtotime('-2 hours +4 seconds')),'created_at'=>date('Y-m-d H:i:s',strtotime('-2 hours')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-2 hours'))],
        ['id'=>2,'rule_id'=>3,'version_id'=>3,'source_key'=>'supplier_invoice|2|match_exception','idempotency_key'=>hash('sha256','3|3|supplier_invoice|2|match_exception'),'status'=>'completed','matched_count'=>1,'created_work_item_id'=>7,'result_json'=>'{"result":"work_created"}','error_message'=>'','started_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes')),'completed_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes +3 seconds')),'created_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes'))],
    ];
}

function work_management_demo_escalation_policies(): array
{
    $defs=[['critical',120,0,240,720,'procurement_manager'],['high',240,120,480,1440,'procurement_manager'],['medium',480,240,1440,2880,'company_administrator'],['low',1440,1440,4320,10080,'company_administrator']];
    $rows=[];foreach($defs as$i=>$d)$rows[]=['id'=>$i+1,'policy_code'=>'PRIORITY-'.strtoupper($d[0]),'policy_name'=>status_label($d[0]).' work escalation','priority'=>$d[0],'warning_minutes'=>$d[1],'level1_minutes'=>$d[2],'level2_minutes'=>$d[3],'level3_minutes'=>$d[4],'backup_role_code'=>$d[5],'executive_role_code'=>'executive','status'=>'active','evidence_note'=>'Governed staged escalation policy.','created_by'=>1,'reviewed_by'=>3,'approved_by'=>6,'created_at'=>'2026-07-27 08:00:00','updated_at'=>'2026-07-27 08:00:00'];return$rows;
}

function work_management_demo_escalation_events(): array
{
    return [
        ['id'=>1,'work_item_id'=>1,'policy_id'=>1,'from_level'=>1,'to_level'=>2,'triggered_at'=>date('Y-m-d H:i:s',strtotime('-30 minutes')),'action_json'=>'{"notify_roles":["procurement_manager","executive"],"reason":"critical_overdue"}','idempotency_key'=>hash('sha256','work|1|level|2'),'created_by'=>1,'created_at'=>date('Y-m-d H:i:s',strtotime('-30 minutes')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-30 minutes'))],
        ['id'=>2,'work_item_id'=>7,'policy_id'=>1,'from_level'=>0,'to_level'=>1,'triggered_at'=>date('Y-m-d H:i:s',strtotime('-15 minutes')),'action_json'=>'{"notify_roles":["reviewer"],"reason":"critical_overdue"}','idempotency_key'=>hash('sha256','work|7|level|1'),'created_by'=>1,'created_at'=>date('Y-m-d H:i:s',strtotime('-15 minutes')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-15 minutes'))],
    ];
}
