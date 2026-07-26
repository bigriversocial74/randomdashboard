<?php
declare(strict_types=1);

require_once __DIR__ . '/scenario_planning.php';

function mitigation_action_types(): array
{
    return [
        'alternate_supplier' => 'Alternate supplier qualification',
        'inventory_transfer' => 'Inventory transfer',
        'expedite' => 'Expedite / logistics response',
        'contract' => 'Commercial negotiation',
        'demand' => 'Demand prioritization',
        'safety_stock' => 'Safety-stock adjustment',
        'supplier_recovery' => 'Supplier recovery plan',
        'substitution' => 'Item substitution',
    ];
}

function mitigation_trigger_types(): array
{
    return [
        'risk_score' => 'Scenario risk score',
        'lead_time_delay' => 'Lead-time delay days',
        'disruption_pct' => 'Supply disruption percentage',
        'price_change_pct' => 'Price increase percentage',
        'service_level' => 'Service level percentage',
        'manual' => 'Manual executive activation',
    ];
}

function mitigation_action_statuses(): array
{
    return ['planned','in_progress','blocked','completed','cancelled'];
}

function mitigation_priorities(): array
{
    return ['critical','high','medium','low'];
}

function mitigation_status_weight(string $status): float
{
    return match ($status) {
        'completed' => 1.0,
        'in_progress' => 0.65,
        'blocked' => 0.2,
        'cancelled' => 0.0,
        default => 0.1,
    };
}

function mitigation_default_blueprint(?array $scenarioRecord = null): array
{
    $inputs = $scenarioRecord['inputs'] ?? scenario_default_inputs();
    $simulation = scenario_calculate($inputs);
    $supplier = $simulation['baseline']['supplier'];
    $supplierName = (string)($supplier['name'] ?? 'the affected supplier');
    $alternate = $simulation['alternatives'][0]['supplier'] ?? null;
    $alternateName = (string)($alternate['name'] ?? 'a qualified alternate supplier');
    $ownerId = (int)(current_user()['id'] ?? 1);
    $risk = (float)$simulation['risk_score'];
    $netImpact = max(0.0, (float)$simulation['net_impact']);
    $inventoryValue = max(0.0, (float)$simulation['baseline']['inventory_value']);
    $openPoValue = max(0.0, (float)$simulation['baseline']['open_po_value']);
    $dueSoon = date('Y-m-d', strtotime($risk >= 60 ? '+7 days' : '+14 days'));
    $dueLater = date('Y-m-d', strtotime($risk >= 60 ? '+14 days' : '+30 days'));

    $actions = [
        [
            'action_type'=>'alternate_supplier','title'=>'Qualify ' . $alternateName . ' as the primary contingency source',
            'owner_id'=>$ownerId,'priority'=>$risk >= 60 ? 'critical' : 'high','status'=>'planned','due_date'=>$dueSoon,
            'estimated_cost'=>round($openPoValue * 0.015, 2),'recovery_value'=>round($netImpact * 0.32, 2),
            'service_risk_reduction'=>22.0,'readiness_pct'=>45.0,'supplier_id'=>(int)($alternate['id'] ?? 0) ?: null,
            'notes'=>'Validate capacity, pricing, quality evidence, onboarding requirements, and emergency order lead time.',
        ],
        [
            'action_type'=>'inventory_transfer','title'=>'Reserve and transfer available inventory to protected demand',
            'owner_id'=>$ownerId,'priority'=>'high','status'=>'planned','due_date'=>$dueSoon,
            'estimated_cost'=>round($inventoryValue * 0.01, 2),'recovery_value'=>round(min($inventoryValue * 0.35, $netImpact * 0.25), 2),
            'service_risk_reduction'=>18.0,'readiness_pct'=>65.0,'supplier_id'=>null,
            'notes'=>'Confirm cross-company ownership, transfer quantities, receiving location, freight, and replenishment timing.',
        ],
        [
            'action_type'=>'supplier_recovery','title'=>'Obtain a dated recovery commitment from ' . $supplierName,
            'owner_id'=>$ownerId,'priority'=>$risk >= 60 ? 'critical' : 'high','status'=>'planned','due_date'=>$dueSoon,
            'estimated_cost'=>0.0,'recovery_value'=>round($netImpact * 0.18, 2),
            'service_risk_reduction'=>15.0,'readiness_pct'=>35.0,'supplier_id'=>(int)($supplier['id'] ?? 0) ?: null,
            'notes'=>'Capture root cause, capacity recovery, shipment sequence, escalation contacts, checkpoints, and evidence.',
        ],
        [
            'action_type'=>'contract','title'=>'Negotiate cost protection and expedite responsibility',
            'owner_id'=>$ownerId,'priority'=>'medium','status'=>'planned','due_date'=>$dueLater,
            'estimated_cost'=>0.0,'recovery_value'=>round($netImpact * 0.15, 2),
            'service_risk_reduction'=>8.0,'readiness_pct'=>30.0,'supplier_id'=>(int)($supplier['id'] ?? 0) ?: null,
            'notes'=>'Review price protection, service credits, freight responsibility, termination rights, and dual-source language.',
        ],
        [
            'action_type'=>'demand','title'=>'Prioritize critical demand and define controlled allocation rules',
            'owner_id'=>$ownerId,'priority'=>'high','status'=>'planned','due_date'=>$dueSoon,
            'estimated_cost'=>0.0,'recovery_value'=>round($netImpact * 0.10, 2),
            'service_risk_reduction'=>12.0,'readiness_pct'=>55.0,'supplier_id'=>null,
            'notes'=>'Rank protected customers, sites, work orders, and items; document exceptions and executive escalation thresholds.',
        ],
    ];

    return [
        'scenario'=>$scenarioRecord,
        'simulation'=>$simulation,
        'title'=>($scenarioRecord['title'] ?? 'Procurement scenario') . ' mitigation plan',
        'trigger_type'=>'risk_score',
        'trigger_operator'=>'>=',
        'trigger_value'=>max(35.0, round($risk, 1)),
        'summary'=>'Convert the active procurement-risk scenario into owned, dated, measurable mitigation actions with a governed activation path.',
        'activation_notes'=>'Activate when the trigger is met or leadership determines the combined cost, cash, and service exposure requires contingency execution.',
        'actions'=>$actions,
    ];
}

function mitigation_calculate_metrics(array $plan, array $actions, ?array $simulation = null): array
{
    $total = count($actions);
    $completed = 0;
    $blocked = 0;
    $cost = 0.0;
    $recovery = 0.0;
    $weightedRecovery = 0.0;
    $serviceReduction = 0.0;
    $readinessTotal = 0.0;
    $overdue = 0;
    foreach ($actions as $action) {
        $status = (string)($action['status'] ?? 'planned');
        if ($status === 'completed') $completed++;
        if ($status === 'blocked') $blocked++;
        if (!empty($action['due_date']) && $action['due_date'] < date('Y-m-d') && !in_array($status,['completed','cancelled'],true)) $overdue++;
        $actionCost = max(0.0,(float)($action['estimated_cost'] ?? 0));
        $actionRecovery = max(0.0,(float)($action['recovery_value'] ?? 0));
        $readiness = max(0.0,min(100.0,(float)($action['readiness_pct'] ?? 0)));
        $cost += $actionCost;
        $recovery += $actionRecovery;
        $weightedRecovery += $actionRecovery * mitigation_status_weight($status) * ($readiness / 100);
        $serviceReduction += max(0.0,(float)($action['service_risk_reduction'] ?? 0)) * mitigation_status_weight($status);
        $readinessTotal += $readiness;
    }
    $scenarioRisk = (float)($simulation['risk_score'] ?? $plan['source_risk_score'] ?? 50);
    $scenarioImpact = abs((float)($simulation['net_impact'] ?? $plan['source_net_impact'] ?? 0));
    $readiness = $total ? $readinessTotal / $total : 0.0;
    $execution = $total ? (($completed + (count(array_filter($actions,static fn(array $a): bool => ($a['status'] ?? '') === 'in_progress')) * .55)) / $total) * 100 : 0.0;
    $coverage = $scenarioImpact > 0 ? min(100.0,($recovery / $scenarioImpact) * 100) : min(100.0,$serviceReduction * 1.5);
    $residualRisk = max(0.0,$scenarioRisk - min(60.0,$serviceReduction) - ($completed * 2.5));
    return [
        'action_count'=>$total,'completed_count'=>$completed,'blocked_count'=>$blocked,'overdue_count'=>$overdue,
        'estimated_cost'=>round($cost,2),'recovery_value'=>round($recovery,2),'weighted_recovery'=>round($weightedRecovery,2),
        'service_risk_reduction'=>round(min(100.0,$serviceReduction),1),'readiness_pct'=>round($readiness,1),
        'execution_pct'=>round(min(100.0,$execution),1),'coverage_pct'=>round($coverage,1),
        'source_risk_score'=>round($scenarioRisk,1),'residual_risk_score'=>round($residualRisk,1),
        'residual_risk_level'=>scenario_risk_level($residualRisk),
    ];
}

function mitigation_demo_seed_records(): array
{
    return [[
        'id'=>1,'plan_number'=>'MIT-2026-0001','company_id'=>null,'scenario_id'=>1,'supplier_id'=>1,'category_id'=>1,
        'title'=>'Battery supply disruption mitigation plan','trigger_type'=>'risk_score','trigger_operator'=>'>=','trigger_value'=>60.0,
        'source_risk_score'=>63.0,'source_net_impact'=>184000.0,'risk_level'=>'high','status'=>'draft','owner_id'=>1,'approval_id'=>null,
        'summary'=>'Protect critical demand through alternate supply, inventory transfer, supplier recovery, and commercial controls.',
        'activation_notes'=>'Activate when scenario risk remains at or above 60 or a supplier recovery milestone is missed.',
        'created_at'=>'2026-07-26 11:30:00','updated_at'=>'2026-07-26 11:30:00',
    ]];
}

function mitigation_demo_seed_actions(): array
{
    $blueprint = mitigation_default_blueprint(scenario_find_record(1));
    $rows=[];
    foreach($blueprint['actions'] as $index=>$action){
        $action['id']=$index+1;$action['plan_id']=1;$action['sequence_no']=$index+1;
        $action['created_at']='2026-07-26 11:30:00';$action['updated_at']='2026-07-26 11:30:00';$rows[]=$action;
    }
    return $rows;
}

function mitigation_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo=production_database_connection();
    if(!$pdo) return false;
    try{$pdo->query('SELECT id FROM procurement_mitigation_plans LIMIT 1');$pdo->query('SELECT id FROM procurement_mitigation_actions LIMIT 1');return true;}
    catch(Throwable){return false;}
}

function mitigation_records(): array
{
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_plans'])) $_SESSION['gruber_demo_state']['procurement_mitigation_plans']=mitigation_demo_seed_records();
        return array_values($_SESSION['gruber_demo_state']['procurement_mitigation_plans']);
    }
    if(!mitigation_tables_ready()) return [];
    $pdo=production_database_connection();
    if(current_company_id()==='enterprise') $stmt=$pdo->query('SELECT * FROM procurement_mitigation_plans ORDER BY updated_at DESC,id DESC');
    else{$stmt=$pdo->prepare('SELECT * FROM procurement_mitigation_plans WHERE company_id=? ORDER BY updated_at DESC,id DESC');$stmt->execute([(int)current_company_id()]);}
    return $stmt->fetchAll();
}

function mitigation_find_record(int $id): ?array
{
    foreach(mitigation_records() as $record) if((int)$record['id']===$id) return $record;
    return null;
}

function mitigation_actions(int $planId): array
{
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_actions'])) $_SESSION['gruber_demo_state']['procurement_mitigation_actions']=mitigation_demo_seed_actions();
        $rows=array_values(array_filter($_SESSION['gruber_demo_state']['procurement_mitigation_actions'],static fn(array $row): bool => (int)$row['plan_id']===$planId));
        usort($rows,static fn(array $a,array $b): int => ((int)$a['sequence_no'])<=>((int)$b['sequence_no']));return $rows;
    }
    if(!mitigation_tables_ready()) return [];
    $stmt=production_database_connection()->prepare('SELECT * FROM procurement_mitigation_actions WHERE plan_id=? ORDER BY sequence_no,id');$stmt->execute([$planId]);return $stmt->fetchAll();
}

function mitigation_find_action(int $id): ?array
{
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_actions'])) $_SESSION['gruber_demo_state']['procurement_mitigation_actions']=mitigation_demo_seed_actions();
        foreach($_SESSION['gruber_demo_state']['procurement_mitigation_actions'] as $row) if((int)$row['id']===$id && mitigation_find_record((int)$row['plan_id'])) return $row;
        return null;
    }
    if(!mitigation_tables_ready()) return null;
    $stmt=production_database_connection()->prepare('SELECT a.* FROM procurement_mitigation_actions a INNER JOIN procurement_mitigation_plans p ON p.id=a.plan_id WHERE a.id=?'.(current_company_id()==='enterprise'?'':' AND p.company_id=?').' LIMIT 1');
    $params=[$id];if(current_company_id()!=='enterprise')$params[]=(int)current_company_id();$stmt->execute($params);$row=$stmt->fetch();return $row?:null;
}

function mitigation_next_number(): string
{
    return 'MIT-'.date('Y').'-'.str_pad((string)(count(mitigation_records())+1),4,'0',STR_PAD_LEFT);
}

function mitigation_save_plan(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['plan_number']=$record['plan_number']??mitigation_next_number();
    if(data_is_demo()){
        $records=mitigation_records();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($records,'id'))])+1;$record['id']=$id;}
        $found=false;foreach($records as $index=>$existing)if((int)$existing['id']===$id){$records[$index]=$record;$found=true;break;}if(!$found)$records[]=$record;
        $_SESSION['gruber_demo_state']['procurement_mitigation_plans']=$records;return $record;
    }
    if(!mitigation_tables_ready()) throw new RuntimeException('Import the Section 13 mitigation migration before saving Production Data plans.');
    $pdo=production_database_connection();$id=(int)($record['id']??0);
    $params=[$record['plan_number'],$record['company_id'],$record['scenario_id'],$record['supplier_id'],$record['category_id'],$record['title'],$record['trigger_type'],$record['trigger_operator'],$record['trigger_value'],$record['source_risk_score'],$record['source_net_impact'],$record['risk_level'],$record['status'],$record['owner_id'],$record['approval_id'],$record['summary'],$record['activation_notes'],$record['created_at'],$record['updated_at']];
    if($id>0){$stmt=$pdo->prepare('UPDATE procurement_mitigation_plans SET plan_number=?,company_id=?,scenario_id=?,supplier_id=?,category_id=?,title=?,trigger_type=?,trigger_operator=?,trigger_value=?,source_risk_score=?,source_net_impact=?,risk_level=?,status=?,owner_id=?,approval_id=?,summary=?,activation_notes=?,created_at=?,updated_at=? WHERE id=?');$stmt->execute([...$params,$id]);}
    else{$stmt=$pdo->prepare('INSERT INTO procurement_mitigation_plans (plan_number,company_id,scenario_id,supplier_id,category_id,title,trigger_type,trigger_operator,trigger_value,source_risk_score,source_net_impact,risk_level,status,owner_id,approval_id,summary,activation_notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute($params);$id=(int)$pdo->lastInsertId();}
    $record['id']=$id;return $record;
}

function mitigation_replace_actions(int $planId,array $actions): array
{
    $normalized=[];
    foreach(array_values($actions) as $index=>$action){
        $type=(string)($action['action_type']??'supplier_recovery');if(!isset(mitigation_action_types()[$type]))$type='supplier_recovery';
        $status=(string)($action['status']??'planned');if(!in_array($status,mitigation_action_statuses(),true))$status='planned';
        $priority=(string)($action['priority']??'medium');if(!in_array($priority,mitigation_priorities(),true))$priority='medium';
        $title=trim((string)($action['title']??''));if($title==='')continue;
        $normalized[]=['plan_id'=>$planId,'sequence_no'=>$index+1,'action_type'=>$type,'title'=>mb_substr($title,0,190),'owner_id'=>max(1,(int)($action['owner_id']??current_user()['id'])),'priority'=>$priority,'status'=>$status,'due_date'=>(string)($action['due_date']??date('Y-m-d',strtotime('+14 days'))),'estimated_cost'=>max(0,(float)($action['estimated_cost']??0)),'recovery_value'=>max(0,(float)($action['recovery_value']??0)),'service_risk_reduction'=>max(0,min(100,(float)($action['service_risk_reduction']??0))),'readiness_pct'=>max(0,min(100,(float)($action['readiness_pct']??0))),'supplier_id'=>(int)($action['supplier_id']??0)?:null,'notes'=>mb_substr(trim((string)($action['notes']??'')),0,5000),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
    }
    if(!$normalized) throw new RuntimeException('At least one mitigation action is required.');
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['procurement_mitigation_actions']))$_SESSION['gruber_demo_state']['procurement_mitigation_actions']=mitigation_demo_seed_actions();
        $all=array_values(array_filter($_SESSION['gruber_demo_state']['procurement_mitigation_actions'],static fn(array $row): bool => (int)$row['plan_id']!==$planId));
        $next=max([0,...array_map('intval',array_column($all,'id'))])+1;foreach($normalized as &$row){$row['id']=$next++;$all[]=$row;}unset($row);$_SESSION['gruber_demo_state']['procurement_mitigation_actions']=$all;return $normalized;
    }
    if(!mitigation_tables_ready())throw new RuntimeException('Import the Section 13 mitigation migration before saving Production Data actions.');
    $pdo=production_database_connection();$pdo->beginTransaction();
    try{$delete=$pdo->prepare('DELETE FROM procurement_mitigation_actions WHERE plan_id=?');$delete->execute([$planId]);$insert=$pdo->prepare('INSERT INTO procurement_mitigation_actions (plan_id,sequence_no,action_type,title,owner_id,priority,status,due_date,estimated_cost,recovery_value,service_risk_reduction,readiness_pct,supplier_id,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');foreach($normalized as $row)$insert->execute([$row['plan_id'],$row['sequence_no'],$row['action_type'],$row['title'],$row['owner_id'],$row['priority'],$row['status'],$row['due_date'],$row['estimated_cost'],$row['recovery_value'],$row['service_risk_reduction'],$row['readiness_pct'],$row['supplier_id'],$row['notes'],$row['created_at'],$row['updated_at']]);$pdo->commit();}
    catch(Throwable $exception){$pdo->rollBack();throw $exception;}
    return mitigation_actions($planId);
}

function mitigation_save_action(array $action): array
{
    $existing=mitigation_find_action((int)($action['id']??0));if(!$existing)throw new RuntimeException('The mitigation action is outside the active scope.');
    $merged=array_replace($existing,$action);$merged['updated_at']=date('Y-m-d H:i:s');
    if(data_is_demo()){
        $rows=$_SESSION['gruber_demo_state']['procurement_mitigation_actions'];foreach($rows as $index=>$row)if((int)$row['id']===(int)$merged['id']){$rows[$index]=$merged;break;}$_SESSION['gruber_demo_state']['procurement_mitigation_actions']=$rows;return $merged;
    }
    $stmt=production_database_connection()->prepare('UPDATE procurement_mitigation_actions SET owner_id=?,priority=?,status=?,due_date=?,readiness_pct=?,notes=?,updated_at=? WHERE id=?');$stmt->execute([$merged['owner_id'],$merged['priority'],$merged['status'],$merged['due_date'],$merged['readiness_pct'],$merged['notes'],$merged['updated_at'],$merged['id']]);return $merged;
}

function mitigation_effective_status(array $record): string
{
    $approvalId=(int)($record['approval_id']??0);if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested'],true))return (string)$approval['status'];}
    return (string)($record['status']??'draft');
}

function mitigation_csv_cell(mixed $value): string
{
    $value=(string)$value;if($value!==''&&preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value;
}

function mitigation_export_csv(array $plan,array $actions,array $metrics): never
{
    require_permission('reports.export');header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="mitigation-action-plan-'.date('Ymd-His').'.csv"');$out=fopen('php://output','wb');
    fputcsv($out,array_map('mitigation_csv_cell',['Plan number',$plan['plan_number']]));fputcsv($out,array_map('mitigation_csv_cell',['Title',$plan['title']]));fputcsv($out,array_map('mitigation_csv_cell',['Status',mitigation_effective_status($plan)]));fputcsv($out,array_map('mitigation_csv_cell',['Trigger',status_label($plan['trigger_type']).' '.$plan['trigger_operator'].' '.$plan['trigger_value']]));
    fputcsv($out,[]);fputcsv($out,['Sequence','Priority','Status','Action type','Action','Owner ID','Due date','Estimated cost','Recovery value','Service risk reduction','Readiness %','Notes']);foreach($actions as $row)fputcsv($out,array_map('mitigation_csv_cell',[$row['sequence_no'],$row['priority'],$row['status'],$row['action_type'],$row['title'],$row['owner_id'],$row['due_date'],$row['estimated_cost'],$row['recovery_value'],$row['service_risk_reduction'],$row['readiness_pct'],$row['notes']]));
    fputcsv($out,[]);fputcsv($out,['Metric','Value']);foreach($metrics as $key=>$value)fputcsv($out,array_map('mitigation_csv_cell',[status_label($key),$value]));fclose($out);exit;
}
