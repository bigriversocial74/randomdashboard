<?php
declare(strict_types=1);

require_once __DIR__ . '/mitigation_planning.php';
require_once __DIR__ . '/mitigation_status.php';

function execution_statuses(): array
{
    return ['proposed','pending_approval','approved','scheduled','in_progress','blocked','completed','verified','failed','rolled_back','cancelled'];
}

function execution_verification_statuses(): array
{
    return ['draft','verified','failed','needs_follow_up'];
}

function execution_risk_levels(): array
{
    return ['low','medium','high','critical'];
}

function execution_event_types(): array
{
    return ['created','submitted','approved','scheduled','started','progress_updated','blocked','completed','verified','verification_failed','follow_up_required','rolled_back','cancelled'];
}

function execution_json_array(mixed $value): array
{
    if (is_array($value)) return $value;
    if (!is_string($value) || trim($value) === '') return [];
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function execution_default_plan(): ?array
{
    $plans = mitigation_records();
    foreach ($plans as $plan) {
        if (mitigation_operational_status($plan) === 'active') return $plan;
    }
    return $plans[0] ?? null;
}

function execution_default_action(?array $plan): ?array
{
    if (!$plan) return null;
    $actions = mitigation_actions((int)$plan['id']);
    foreach ($actions as $action) {
        if (!in_array((string)($action['status'] ?? ''), ['cancelled'], true)) return $action;
    }
    return $actions[0] ?? null;
}

function execution_change_risk(array $action, array $before): string
{
    $priority = (string)($action['priority'] ?? 'medium');
    $recovery = (float)($action['recovery_value'] ?? 0);
    $openPo = (float)($before['open_po_value'] ?? 0);
    $type = (string)($action['action_type'] ?? 'supplier_recovery');
    if ($priority === 'critical' || $recovery >= 150000 || $openPo >= 500000) return 'critical';
    if ($priority === 'high' || $recovery >= 50000 || in_array($type, ['alternate_supplier','contract'], true)) return 'high';
    if ($priority === 'low' && $recovery < 10000) return 'low';
    return 'medium';
}

function execution_blueprint(?array $plan = null, ?array $action = null): array
{
    $plan ??= execution_default_plan();
    $action ??= execution_default_action($plan);
    if (!$plan || !$action) throw new RuntimeException('A mitigation plan and action are required to create an execution record.');
    if ((int)($action['plan_id'] ?? 0) !== (int)$plan['id']) throw new RuntimeException('The mitigation action does not belong to the selected plan.');

    $scenario = !empty($plan['scenario_id']) ? scenario_find_record((int)$plan['scenario_id']) : null;
    $simulation = scenario_calculate($scenario['inputs'] ?? scenario_default_inputs());
    $before = [
        'risk_score'=>round((float)($plan['source_risk_score'] ?? $simulation['risk_score']),1),
        'net_impact'=>round((float)($plan['source_net_impact'] ?? $simulation['net_impact']),2),
        'lead_time_days'=>(int)($simulation['active_assumptions']['lead_time_delay_days'] ?? 0),
        'inventory_exposure'=>round((float)($simulation['baseline']['inventory_value'] ?? 0),2),
        'open_po_value'=>round((float)($simulation['baseline']['open_po_value'] ?? 0),2),
        'service_level'=>round(max(0,100-(float)($simulation['service_risk'] ?? 50)),1),
    ];
    $plannedRecovery = max(0,(float)($action['recovery_value'] ?? 0));
    $plannedCost = max(0,(float)($action['estimated_cost'] ?? 0));
    $serviceReduction = max(0,min(100,(float)($action['service_risk_reduction'] ?? 0)));
    $target = [
        'recovery_value'=>round($plannedRecovery,2),
        'cost'=>round($plannedCost,2),
        'risk_score'=>round(max(0,$before['risk_score']-$serviceReduction),1),
        'lead_time_days'=>max(0,$before['lead_time_days']-(int)round($serviceReduction/3)),
        'inventory_exposure_reduced'=>round(min($before['inventory_exposure'],$plannedRecovery*.35),2),
        'po_value_redirected'=>round(min($before['open_po_value'],$plannedRecovery*.55),2),
        'service_level'=>round(min(100,$before['service_level']+$serviceReduction),1),
    ];
    $risk = execution_change_risk($action,$before);
    return [
        'plan'=>$plan,'action'=>$action,'scenario'=>$scenario,'simulation'=>$simulation,
        'title'=>$action['title'].' execution',
        'execution_type'=>(string)$action['action_type'],
        'change_risk'=>$risk,
        'before'=>$before,
        'target'=>$target,
        'rollback_plan'=>'Reverse the operational change, restore the previous supplier/order/inventory state where possible, notify affected owners, and document unrecovered exposure.',
        'evidence_note'=>'Execution evidence must identify the source record, owner acknowledgement, effective date, affected supplier/order/inventory scope, and verification source.',
        'scheduled_date'=>(string)($action['due_date'] ?? date('Y-m-d',strtotime('+7 days'))),
    ];
}

function execution_requires_approval(array $execution): bool
{
    $risk = (string)($execution['change_risk'] ?? 'medium');
    $target = execution_json_array($execution['target'] ?? $execution['target_json'] ?? []);
    $changeValue = max((float)($target['recovery_value'] ?? 0),(float)($target['po_value_redirected'] ?? 0),(float)($target['cost'] ?? 0));
    return in_array($risk,['high','critical'],true) || $changeValue >= 50000 || in_array((string)($execution['execution_type'] ?? ''),['alternate_supplier','contract'],true);
}

function execution_metrics(array $execution, ?array $verification = null): array
{
    $before = execution_json_array($execution['before'] ?? $execution['before_json'] ?? []);
    $target = execution_json_array($execution['target'] ?? $execution['target_json'] ?? []);
    $actual = execution_json_array($execution['actual'] ?? $execution['actual_json'] ?? []);
    if ($verification) {
        $actual = array_replace($actual,[
            'recovery_value'=>(float)($verification['actual_recovery_value'] ?? 0),
            'cost'=>(float)($verification['actual_cost'] ?? 0),
            'risk_score'=>(float)($verification['after_risk_score'] ?? 0),
            'lead_time_days'=>(int)($verification['after_lead_time_days'] ?? 0),
            'inventory_exposure_reduced'=>(float)($verification['inventory_exposure_reduced'] ?? 0),
            'po_value_redirected'=>(float)($verification['po_value_redirected'] ?? 0),
            'service_level'=>(float)($verification['service_level_after'] ?? 0),
        ]);
    }
    $plannedRecovery=(float)($target['recovery_value']??0);$actualRecovery=(float)($actual['recovery_value']??0);
    $plannedCost=(float)($target['cost']??0);$actualCost=(float)($actual['cost']??0);
    $beforeRisk=(float)($before['risk_score']??0);$afterRisk=(float)($actual['risk_score']??$beforeRisk);
    $beforeLead=(int)($before['lead_time_days']??0);$afterLead=(int)($actual['lead_time_days']??$beforeLead);
    $serviceBefore=(float)($before['service_level']??0);$serviceAfter=(float)($actual['service_level']??$serviceBefore);
    $recoveryAttainment=$plannedRecovery>0?($actualRecovery/$plannedRecovery)*100:($actualRecovery>0?100:0);
    $costVariance=$actualCost-$plannedCost;
    return [
        'planned_recovery_value'=>round($plannedRecovery,2),'actual_recovery_value'=>round($actualRecovery,2),
        'recovery_variance'=>round($actualRecovery-$plannedRecovery,2),'recovery_attainment_pct'=>round(max(0,min(250,$recoveryAttainment)),1),
        'planned_cost'=>round($plannedCost,2),'actual_cost'=>round($actualCost,2),'cost_variance'=>round($costVariance,2),
        'risk_reduction'=>round(max(0,$beforeRisk-$afterRisk),1),'before_risk_score'=>round($beforeRisk,1),'after_risk_score'=>round($afterRisk,1),
        'lead_time_improvement_days'=>max(0,$beforeLead-$afterLead),'service_level_improvement'=>round(max(0,$serviceAfter-$serviceBefore),1),
        'inventory_exposure_reduced'=>round((float)($actual['inventory_exposure_reduced']??0),2),
        'po_value_redirected'=>round((float)($actual['po_value_redirected']??0),2),
        'benefit_cost_ratio'=>$actualCost>0?round($actualRecovery/$actualCost,2):($actualRecovery>0?999.0:0.0),
        'target_risk_score'=>round((float)($target['risk_score']??$beforeRisk),1),
        'target_lead_time_days'=>(int)($target['lead_time_days']??$beforeLead),
    ];
}

function execution_demo_seed_records(): array
{
    $plan=mitigation_find_record(1);$actions=$plan?mitigation_actions(1):[];
    if(!$plan||!$actions)return [];
    $rows=[];
    foreach(array_slice($actions,0,2) as $index=>$action){
        $blueprint=execution_blueprint($plan,$action);$status=$index===0?'in_progress':'completed';
        $actual=$index===1?['recovery_value'=>round($blueprint['target']['recovery_value']*.92,2),'cost'=>round($blueprint['target']['cost']*1.05,2),'risk_score'=>42.0,'lead_time_days'=>4,'inventory_exposure_reduced'=>32000.0,'po_value_redirected'=>68000.0,'service_level'=>88.0]:[];
        $rows[]=['id'=>$index+1,'execution_number'=>'EXE-2026-'.str_pad((string)($index+1),4,'0',STR_PAD_LEFT),'company_id'=>null,'plan_id'=>1,'action_id'=>(int)$action['id'],'execution_type'=>$blueprint['execution_type'],'title'=>$blueprint['title'],'owner_id'=>(int)$action['owner_id'],'status'=>$status,'change_risk'=>$blueprint['change_risk'],'approval_id'=>null,'before'=>$blueprint['before'],'target'=>$blueprint['target'],'actual'=>$actual,'rollback_plan'=>$blueprint['rollback_plan'],'evidence_note'=>$index===1?'Receiving confirmation, revised PO acknowledgement, and supplier capacity evidence captured.':'Execution owner acknowledged; alternate supplier capacity review is in progress.','scheduled_date'=>$blueprint['scheduled_date'],'started_at'=>'2026-07-26 13:00:00','completed_at'=>$index===1?'2026-07-26 16:30:00':null,'created_at'=>'2026-07-26 12:30:00','updated_at'=>'2026-07-26 16:30:00'];
    }
    return $rows;
}

function execution_demo_seed_events(): array
{
    return [
        ['id'=>1,'execution_id'=>1,'event_type'=>'created','from_status'=>null,'to_status'=>'proposed','evidence_note'=>'Execution record created from mitigation action.','event'=>[],'created_by'=>1,'created_at'=>'2026-07-26 12:30:00'],
        ['id'=>2,'execution_id'=>1,'event_type'=>'started','from_status'=>'approved','to_status'=>'in_progress','evidence_note'=>'Owner acknowledged controlled-change scope.','event'=>[],'created_by'=>1,'created_at'=>'2026-07-26 13:00:00'],
        ['id'=>3,'execution_id'=>2,'event_type'=>'completed','from_status'=>'in_progress','to_status'=>'completed','evidence_note'=>'Inventory transfer and order redirection completed.','event'=>[],'created_by'=>1,'created_at'=>'2026-07-26 16:30:00'],
    ];
}

function execution_demo_seed_verifications(): array
{
    return [[
        'id'=>1,'verification_number'=>'VER-2026-0001','company_id'=>null,'execution_id'=>2,'status'=>'verified',
        'planned_recovery_value'=>46000.0,'actual_recovery_value'=>42320.0,'planned_cost'=>2400.0,'actual_cost'=>2520.0,
        'before_risk_score'=>63.0,'after_risk_score'=>42.0,'before_lead_time_days'=>14,'after_lead_time_days'=>4,
        'inventory_exposure_reduced'=>32000.0,'po_value_redirected'=>68000.0,'service_level_before'=>55.0,'service_level_after'=>88.0,
        'reviewer_id'=>1,'evidence_note'=>'Verified against receiving confirmation, revised PO acknowledgement, and supplier capacity evidence.','verified_at'=>'2026-07-26 17:00:00','created_at'=>'2026-07-26 17:00:00','updated_at'=>'2026-07-26 17:00:00',
    ]];
}

function execution_tables_ready(): bool
{
    if(data_is_demo())return true;$pdo=production_database_connection();if(!$pdo)return false;
    try{$pdo->query('SELECT id FROM procurement_mitigation_executions LIMIT 1');$pdo->query('SELECT id FROM procurement_mitigation_execution_events LIMIT 1');$pdo->query('SELECT id FROM procurement_recovery_verifications LIMIT 1');return true;}catch(Throwable){return false;}
}

function execution_decode_row(array $row): array
{
    $row['before']=execution_json_array($row['before_json']??$row['before']??[]);$row['target']=execution_json_array($row['target_json']??$row['target']??[]);$row['actual']=execution_json_array($row['actual_json']??$row['actual']??[]);return $row;
}

function execution_records(?int $planId=null): array
{
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_executions']))$_SESSION['gruber_demo_state']['procurement_mitigation_executions']=execution_demo_seed_records();
        $rows=array_map('execution_decode_row',array_values($_SESSION['gruber_demo_state']['procurement_mitigation_executions']));
        return $planId?array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['plan_id']===$planId)):$rows;
    }
    if(!execution_tables_ready())return [];$pdo=production_database_connection();$where=[];$params=[];
    if(current_company_id()!=='enterprise'){$where[]='company_id=?';$params[]=(int)current_company_id();}
    if($planId){$where[]='plan_id=?';$params[]=$planId;}
    $sql='SELECT * FROM procurement_mitigation_executions'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY updated_at DESC,id DESC';$stmt=$pdo->prepare($sql);$stmt->execute($params);return array_map('execution_decode_row',$stmt->fetchAll());
}

function execution_find_record(int $id): ?array
{
    foreach(execution_records() as $record)if((int)$record['id']===$id)return $record;return null;
}

function execution_next_number(): string
{
    return 'EXE-'.date('Y').'-'.str_pad((string)(count(execution_records())+1),4,'0',STR_PAD_LEFT);
}

function execution_save_record(array $record): array
{
    $record['before']=execution_json_array($record['before']??$record['before_json']??[]);$record['target']=execution_json_array($record['target']??$record['target_json']??[]);$record['actual']=execution_json_array($record['actual']??$record['actual_json']??[]);
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['execution_number']=$record['execution_number']??execution_next_number();
    if(data_is_demo()){$rows=execution_records();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$existing)if((int)$existing['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['procurement_mitigation_executions']=$rows;return $record;}
    if(!execution_tables_ready())throw new RuntimeException('Import the Section 14 execution migration before saving Production Data changes.');$pdo=production_database_connection();$id=(int)($record['id']??0);
    $params=[$record['execution_number'],$record['company_id'],$record['plan_id'],$record['action_id'],$record['execution_type'],$record['title'],$record['owner_id'],$record['status'],$record['change_risk'],$record['approval_id'],json_encode($record['before'],JSON_THROW_ON_ERROR),json_encode($record['target'],JSON_THROW_ON_ERROR),json_encode($record['actual'],JSON_THROW_ON_ERROR),$record['rollback_plan'],$record['evidence_note'],$record['scheduled_date'],$record['started_at'],$record['completed_at'],$record['created_at'],$record['updated_at']];
    if($id>0){$stmt=$pdo->prepare('UPDATE procurement_mitigation_executions SET execution_number=?,company_id=?,plan_id=?,action_id=?,execution_type=?,title=?,owner_id=?,status=?,change_risk=?,approval_id=?,before_json=?,target_json=?,actual_json=?,rollback_plan=?,evidence_note=?,scheduled_date=?,started_at=?,completed_at=?,created_at=?,updated_at=? WHERE id=?');$stmt->execute([...$params,$id]);}
    else{$stmt=$pdo->prepare('INSERT INTO procurement_mitigation_executions (execution_number,company_id,plan_id,action_id,execution_type,title,owner_id,status,change_risk,approval_id,before_json,target_json,actual_json,rollback_plan,evidence_note,scheduled_date,started_at,completed_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}

function execution_events(int $executionId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_execution_events']))$_SESSION['gruber_demo_state']['procurement_mitigation_execution_events']=execution_demo_seed_events();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['procurement_mitigation_execution_events'],static fn(array $r):bool=>(int)$r['execution_id']===$executionId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;}
    if(!execution_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM procurement_mitigation_execution_events WHERE execution_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$executionId]);$rows=$stmt->fetchAll();foreach($rows as &$row)$row['event']=execution_json_array($row['event_json']??[]);unset($row);return $rows;
}

function execution_add_event(int $executionId,string $eventType,?string $fromStatus,?string $toStatus,string $note,array $event=[]): array
{
    if(!in_array($eventType,execution_event_types(),true))$eventType='progress_updated';$row=['execution_id'=>$executionId,'event_type'=>$eventType,'from_status'=>$fromStatus,'to_status'=>$toStatus,'evidence_note'=>mb_substr(trim($note),0,5000),'event'=>$event,'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_execution_events']))$_SESSION['gruber_demo_state']['procurement_mitigation_execution_events']=execution_demo_seed_events();$rows=$_SESSION['gruber_demo_state']['procurement_mitigation_execution_events'];$row['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$row;$_SESSION['gruber_demo_state']['procurement_mitigation_execution_events']=$rows;return $row;}
    if(!execution_tables_ready())throw new RuntimeException('Import the Section 14 execution migration before writing execution events.');$stmt=production_database_connection()->prepare('INSERT INTO procurement_mitigation_execution_events (execution_id,event_type,from_status,to_status,evidence_note,event_json,created_by,created_at) VALUES (?,?,?,?,?,?,?,?)');$stmt->execute([$row['execution_id'],$row['event_type'],$row['from_status'],$row['to_status'],$row['evidence_note'],json_encode($row['event'],JSON_THROW_ON_ERROR),$row['created_by'],$row['created_at']]);$row['id']=(int)production_database_connection()->lastInsertId();return $row;
}

function execution_verifications(?int $executionId=null): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_recovery_verifications']))$_SESSION['gruber_demo_state']['procurement_recovery_verifications']=execution_demo_seed_verifications();$rows=array_values($_SESSION['gruber_demo_state']['procurement_recovery_verifications']);return $executionId?array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['execution_id']===$executionId)):$rows;}
    if(!execution_tables_ready())return [];$pdo=production_database_connection();$where=[];$params=[];if(current_company_id()!=='enterprise'){$where[]='company_id=?';$params[]=(int)current_company_id();}if($executionId){$where[]='execution_id=?';$params[]=$executionId;}$stmt=$pdo->prepare('SELECT * FROM procurement_recovery_verifications'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY updated_at DESC,id DESC');$stmt->execute($params);return $stmt->fetchAll();
}

function execution_latest_verification(int $executionId): ?array
{
    return execution_verifications($executionId)[0]??null;
}

function execution_next_verification_number(): string
{
    return 'VER-'.date('Y').'-'.str_pad((string)(count(execution_verifications())+1),4,'0',STR_PAD_LEFT);
}

function execution_save_verification(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['verification_number']=$record['verification_number']??execution_next_verification_number();
    if(data_is_demo()){$rows=execution_verifications();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$existing)if((int)$existing['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['procurement_recovery_verifications']=$rows;return $record;}
    if(!execution_tables_ready())throw new RuntimeException('Import the Section 14 execution migration before saving recovery verification.');$pdo=production_database_connection();$id=(int)($record['id']??0);
    $params=[$record['verification_number'],$record['company_id'],$record['execution_id'],$record['status'],$record['planned_recovery_value'],$record['actual_recovery_value'],$record['planned_cost'],$record['actual_cost'],$record['before_risk_score'],$record['after_risk_score'],$record['before_lead_time_days'],$record['after_lead_time_days'],$record['inventory_exposure_reduced'],$record['po_value_redirected'],$record['service_level_before'],$record['service_level_after'],$record['reviewer_id'],$record['evidence_note'],$record['verified_at'],$record['created_at'],$record['updated_at']];
    if($id>0){$stmt=$pdo->prepare('UPDATE procurement_recovery_verifications SET verification_number=?,company_id=?,execution_id=?,status=?,planned_recovery_value=?,actual_recovery_value=?,planned_cost=?,actual_cost=?,before_risk_score=?,after_risk_score=?,before_lead_time_days=?,after_lead_time_days=?,inventory_exposure_reduced=?,po_value_redirected=?,service_level_before=?,service_level_after=?,reviewer_id=?,evidence_note=?,verified_at=?,created_at=?,updated_at=? WHERE id=?');$stmt->execute([...$params,$id]);}
    else{$stmt=$pdo->prepare('INSERT INTO procurement_recovery_verifications (verification_number,company_id,execution_id,status,planned_recovery_value,actual_recovery_value,planned_cost,actual_cost,before_risk_score,after_risk_score,before_lead_time_days,after_lead_time_days,inventory_exposure_reduced,po_value_redirected,service_level_before,service_level_after,reviewer_id,evidence_note,verified_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}

function execution_effective_status(array $record): string
{
    $stored=(string)($record['status']??'proposed');if(in_array($stored,['scheduled','in_progress','blocked','completed','verified','failed','rolled_back','cancelled'],true))return $stored;
    $approvalId=(int)($record['approval_id']??0);if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested'],true))return (string)$approval['status'];}
    return $stored;
}

function execution_can_start(array $record): bool
{
    return !execution_requires_approval($record)||execution_effective_status($record)==='approved';
}

function execution_plan_readiness(int $planId): array
{
    $required=array_values(array_filter(mitigation_actions($planId),static fn(array $a):bool=>($a['status']??'')==='completed'));
    $verified=0;$missing=[];
    foreach($required as $action){$found=false;foreach(execution_records($planId) as $execution){if((int)($execution['action_id']??0)===(int)$action['id']&&execution_effective_status($execution)==='verified'){$found=true;break;}}if($found)$verified++;else$missing[]=$action;}
    return ['required_count'=>count($required),'verified_count'=>$verified,'missing_actions'=>$missing,'ready'=>count($required)>0&&$verified===count($required)];
}

function execution_csv_cell(mixed $value): string
{
    $value=(string)$value;if($value!==''&&preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value;
}

function execution_export_csv(array $execution,?array $verification,array $events): never
{
    require_permission('reports.export');$metrics=execution_metrics($execution,$verification);header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="mitigation-execution-'.date('Ymd-His').'.csv"');$out=fopen('php://output','wb');
    foreach([['Execution number',$execution['execution_number']],['Title',$execution['title']],['Status',execution_effective_status($execution)],['Change risk',$execution['change_risk']],['Approval required',execution_requires_approval($execution)?'Yes':'No'],['Scheduled date',$execution['scheduled_date']],['Evidence note',$execution['evidence_note']],['Rollback plan',$execution['rollback_plan']]] as $row)fputcsv($out,array_map('execution_csv_cell',$row));
    fputcsv($out,[]);fputcsv($out,['Metric','Value']);foreach($metrics as $key=>$value)fputcsv($out,array_map('execution_csv_cell',[status_label($key),$value]));
    if($verification){fputcsv($out,[]);fputcsv($out,['Verification number',$verification['verification_number']]);fputcsv($out,['Verification status',$verification['status']]);fputcsv($out,['Verification evidence',$verification['evidence_note']]);}
    fputcsv($out,[]);fputcsv($out,['Event','From','To','Evidence','Created at']);foreach($events as $event)fputcsv($out,array_map('execution_csv_cell',[$event['event_type'],$event['from_status'],$event['to_status'],$event['evidence_note'],$event['created_at']]));fclose($out);exit;
}
