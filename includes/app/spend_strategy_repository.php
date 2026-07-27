<?php
declare(strict_types=1);

function spend_strategy_tables(): array
{
    return ['procurement_spend_snapshots','procurement_spend_classifications','procurement_category_strategies','procurement_category_strategy_actions','procurement_planning_periods','procurement_plan_targets','procurement_strategy_events'];
}
function spend_strategy_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo=production_database_connection(); if (!$pdo) return false;
    try {$names=spend_strategy_tables();$stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('.implode(',',array_fill(0,count($names),'?')).')');$stmt->execute($names);return (int)$stmt->fetchColumn()===count($names);} catch (Throwable) { return false; }
}
function spend_strategy_require_tables(): void { if (!spend_strategy_tables_ready()) throw new RuntimeException('Section 21 migration is required before Production Data strategy writes can be used.'); }
function spend_strategy_demo_collection(string $key,callable $seed): array { if (!isset($_SESSION['gruber_demo_state'][$key])) $_SESSION['gruber_demo_state'][$key]=$seed(); return array_values($_SESSION['gruber_demo_state'][$key]); }
function spend_strategy_demo_save(string $key,array $record,callable $seed): array
{
    $rows=spend_strategy_demo_collection($key,$seed);$id=(int)($record['id']??0);
    if ($id<=0) {$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;$rows[]=$record;}
    else {$found=false;foreach($rows as $index=>$row)if((int)$row['id']===$id){$rows[$index]=array_replace($row,$record);$record=$rows[$index];$found=true;break;}if(!$found)$rows[]=$record;}
    $_SESSION['gruber_demo_state'][$key]=array_values($rows);return $record;
}
function spend_strategy_row_visible(array $row): bool
{
    $company=$row['company_id']??null;$selected=current_company_id();
    if($selected==='enterprise'){if(can_use_enterprise_view())return $company===null||$company===''||in_array((int)$company,permitted_company_ids(current_user()),true);return $company!==null&&in_array((int)$company,permitted_company_ids(current_user()),true);}return $company!==null&&(int)$company===(int)$selected;
}
function spend_strategy_rows(string $table,string $demoKey,callable $seed,string $order='id DESC'): array
{
    if(data_is_demo())$rows=spend_strategy_demo_collection($demoKey,$seed);else{if(!spend_strategy_tables_ready())return[];$rows=production_database_connection()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();}return array_values(array_filter($rows,'spend_strategy_row_visible'));
}
function spend_strategy_snapshots(): array{return spend_strategy_rows('procurement_spend_snapshots','procurement_spend_snapshots','spend_strategy_demo_snapshots','period_end DESC,id DESC');}
function spend_strategy_classifications(): array{return spend_strategy_rows('procurement_spend_classifications','procurement_spend_classifications','spend_strategy_demo_classifications','created_at DESC,id DESC');}
function spend_strategy_strategies(): array{return spend_strategy_rows('procurement_category_strategies','procurement_category_strategies','spend_strategy_demo_strategies','fiscal_year DESC,id DESC');}
function spend_strategy_actions(?int $strategyId=null): array{$rows=spend_strategy_rows('procurement_category_strategy_actions','procurement_category_strategy_actions','spend_strategy_demo_actions','due_date,id');return $strategyId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['strategy_id']===$strategyId));}
function spend_strategy_plans(): array{return spend_strategy_rows('procurement_planning_periods','procurement_planning_periods','spend_strategy_demo_plans','period_start DESC,id DESC');}
function spend_strategy_targets(?int $planId=null): array{$rows=spend_strategy_rows('procurement_plan_targets','procurement_plan_targets','spend_strategy_demo_targets','metric_code,id');return $planId===null?$rows:array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['planning_period_id']===$planId));}
function spend_strategy_events(): array{return spend_strategy_rows('procurement_strategy_events','procurement_strategy_events','spend_strategy_demo_events','created_at DESC,id DESC');}
function spend_strategy_find(array $rows,int $id): ?array{foreach($rows as $row)if((int)$row['id']===$id)return$row;return null;}
function spend_strategy_find_strategy(int $id): ?array{return spend_strategy_find(spend_strategy_strategies(),$id);}function spend_strategy_find_plan(int $id): ?array{return spend_strategy_find(spend_strategy_plans(),$id);}function spend_strategy_find_target(int $id): ?array{return spend_strategy_find(spend_strategy_targets(),$id);}
function spend_strategy_save_row(string $table,string $demoKey,callable $seed,array $record,array $fields): array
{
    spend_strategy_require_tables();$record['created_at']=$record['created_at']??date('Y-m-d H:i:s');$record['updated_at']=date('Y-m-d H:i:s');if(data_is_demo())return spend_strategy_demo_save($demoKey,$record,$seed);$pdo=production_database_connection();$id=(int)($record['id']??0);$values=[];foreach($fields as $field)$values[]=$record[$field]??null;if($id){$values[]=$id;$pdo->prepare('UPDATE '.$table.' SET '.implode(',',array_map(static fn(string $f):string=>$f.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);}else{$pdo->prepare('INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return$record;
}
function spend_strategy_save_snapshot(array $r): array{return spend_strategy_save_row('procurement_spend_snapshots','procurement_spend_snapshots','spend_strategy_demo_snapshots',$r,['snapshot_number','company_id','period_start','period_end','fiscal_year','fiscal_period','category_id','supplier_id','contract_id','item_id','project_workorder_id','department_id','location_id','buyer_id','business_purpose','ordered_spend','received_spend','invoiced_spend','payment_ready_spend','open_commitments','contracted_spend','off_contract_spend','emergency_spend','freight_cost','quality_cost','inventory_carrying_cost','addressable_spend','spend_under_management','spend_under_contract','high_risk_spend','source_hash','status','owner_id','reviewer_id','approval_id','evidence_note']);}
function spend_strategy_save_classification(array $r): array{return spend_strategy_save_row('procurement_spend_classifications','procurement_spend_classifications','spend_strategy_demo_classifications',$r,['snapshot_id','company_id','classification_type','severity','amount','rationale','root_cause','owner_id','status','due_date','resolution_note']);}
function spend_strategy_save_strategy(array $r): array{return spend_strategy_save_row('procurement_category_strategies','procurement_category_strategies','spend_strategy_demo_strategies',$r,['strategy_number','company_id','category_id','fiscal_year','title','status','owner_id','reviewer_id','approval_id','current_spend','addressable_spend','contracted_spend','supplier_count','concentration_pct','high_risk_spend','validated_savings','demand_summary','market_structure','supplier_panel','risk_summary','inventory_alternatives','performance_summary','negotiation_strategy','sourcing_strategy','target_terms','strategy_decision','review_date','renewal_date','evidence_note','submitted_at','approved_at','locked_at']);}
function spend_strategy_save_action(array $r): array{return spend_strategy_save_row('procurement_category_strategy_actions','procurement_category_strategy_actions','spend_strategy_demo_actions',$r,['strategy_id','company_id','action_number','action_type','title','owner_id','due_date','status','planned_value','actual_value','milestone','evidence_note','completed_at']);}
function spend_strategy_save_plan(array $r): array{return spend_strategy_save_row('procurement_planning_periods','procurement_planning_periods','spend_strategy_demo_plans',$r,['plan_number','company_id','fiscal_year','period_type','period_label','period_start','period_end','version_number','status','owner_id','reviewer_id','approval_id','evidence_note','submitted_at','approved_at','locked_at']);}
function spend_strategy_save_target(array $r): array{return spend_strategy_save_row('procurement_plan_targets','procurement_plan_targets','spend_strategy_demo_targets',$r,['planning_period_id','company_id','metric_code','category_id','supplier_id','target_value','actual_value','variance_value','variance_pct','tolerance_pct','status','owner_id','root_cause','corrective_action','revised_forecast','executive_decision','evidence_note']);}
function spend_strategy_replace_snapshots(array $rows,string $periodStart,string $periodEnd): void
{
    spend_strategy_require_tables();if(data_is_demo()){$existing=spend_strategy_demo_collection('procurement_spend_snapshots','spend_strategy_demo_snapshots');$existing=array_values(array_filter($existing,static fn(array $r):bool=>($r['period_start']??'')!==$periodStart||($r['period_end']??'')!==$periodEnd));$_SESSION['gruber_demo_state']['procurement_spend_snapshots']=$existing;foreach($rows as $row)spend_strategy_save_snapshot($row);return;}$pdo=production_database_connection();$pdo->beginTransaction();try{$company=current_company_id();if($company==='enterprise')$pdo->prepare('DELETE FROM procurement_spend_snapshots WHERE period_start=? AND period_end=?')->execute([$periodStart,$periodEnd]);else$pdo->prepare('DELETE FROM procurement_spend_snapshots WHERE period_start=? AND period_end=? AND company_id=?')->execute([$periodStart,$periodEnd,(int)$company]);foreach($rows as $row)spend_strategy_save_snapshot($row);$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw$e;}
}
function spend_strategy_add_event(?int $companyId,string $entityType,int $entityId,string $eventType,?string $fromStatus,?string $toStatus,string $severity,float $amount,string $note): array
{
    spend_strategy_require_tables();$r=['id'=>null,'company_id'=>$companyId,'entity_type'=>$entityType,'entity_id'=>$entityId,'event_type'=>$eventType,'from_status'=>$fromStatus,'to_status'=>$toStatus,'severity'=>$severity,'amount'=>$amount,'evidence_note'=>$note,'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];if(data_is_demo())return spend_strategy_demo_save('procurement_strategy_events',$r,'spend_strategy_demo_events');$pdo=production_database_connection();$pdo->prepare('INSERT INTO procurement_strategy_events(company_id,entity_type,entity_id,event_type,from_status,to_status,severity,amount,evidence_note,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$companyId,$entityType,$entityId,$eventType,$fromStatus,$toStatus,$severity,$amount,$note,$r['created_by']]);$r['id']=(int)$pdo->lastInsertId();return$r;
}
