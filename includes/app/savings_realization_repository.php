<?php
declare(strict_types=1);

function savings_realization_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo=production_database_connection();
    if (!$pdo) return false;
    try {
        $names=['savings_baselines','savings_realization_periods','savings_evidence_links','savings_finance_validations','savings_leakage_events','savings_governance_events'];
        $placeholders=implode(',',array_fill(0,count($names),'?'));
        $stmt=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ({$placeholders})");
        $stmt->execute($names);
        return (int)$stmt->fetchColumn()===count($names);
    } catch (Throwable) {
        return false;
    }
}

function savings_realization_require_tables(): void
{
    if (!savings_realization_tables_ready()) {
        throw new RuntimeException('Section 20 migration is required before Production Data savings-realization writes can be used.');
    }
}

function savings_realization_demo_collection(string $key, callable $seed): array
{
    if (!isset($_SESSION['gruber_demo_state'][$key])) {
        $_SESSION['gruber_demo_state'][$key]=$seed();
    }
    return array_values($_SESSION['gruber_demo_state'][$key]);
}

function savings_realization_demo_save(string $key,array $record,callable $seed): array
{
    $rows=savings_realization_demo_collection($key,$seed);
    $id=(int)($record['id']??0);
    if ($id<=0) {
        $id=max([0,...array_map('intval',array_column($rows,'id'))])+1;
        $record['id']=$id;
        $rows[]=$record;
    } else {
        $found=false;
        foreach ($rows as $index=>$row) {
            if ((int)$row['id']===$id) {
                $rows[$index]=array_replace($row,$record);
                $record=$rows[$index];
                $found=true;
                break;
            }
        }
        if (!$found) $rows[]=$record;
    }
    $_SESSION['gruber_demo_state'][$key]=array_values($rows);
    return $record;
}

function savings_realization_opportunities(): array
{
    return sort_records(data_visible_collection('savings_opportunities'),'annualized_value','desc');
}

function savings_realization_find_opportunity(int $id): ?array
{
    foreach (savings_realization_opportunities() as $row) {
        if ((int)$row['id']===$id) return $row;
    }
    return null;
}

function savings_realization_visible_opportunity_ids(): array
{
    return array_map('intval',array_column(savings_realization_opportunities(),'id'));
}

function savings_realization_filter_rows(array $rows): array
{
    $ids=savings_realization_visible_opportunity_ids();
    return array_values(array_filter($rows,static fn(array $row):bool=>in_array((int)($row['opportunity_id']??0),$ids,true)));
}

function savings_realization_baselines(?int $opportunityId=null): array
{
    if (data_is_demo()) $rows=savings_realization_demo_collection('savings_baselines','savings_realization_demo_baselines');
    else {
        if (!savings_realization_tables_ready()) return [];
        $rows=production_database_connection()->query('SELECT * FROM savings_baselines ORDER BY version_number DESC,id DESC')->fetchAll();
    }
    $rows=savings_realization_filter_rows($rows);
    if ($opportunityId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['opportunity_id']===$opportunityId));
    return $rows;
}

function savings_realization_find_baseline(int $id): ?array
{
    foreach (savings_realization_baselines() as $row) if ((int)$row['id']===$id) return $row;
    return null;
}

function savings_realization_periods(?int $opportunityId=null): array
{
    if (data_is_demo()) $rows=savings_realization_demo_collection('savings_realization_periods','savings_realization_demo_periods');
    else {
        if (!savings_realization_tables_ready()) return [];
        $rows=production_database_connection()->query('SELECT * FROM savings_realization_periods ORDER BY period_end DESC,id DESC')->fetchAll();
    }
    $rows=savings_realization_filter_rows($rows);
    if ($opportunityId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['opportunity_id']===$opportunityId));
    return $rows;
}

function savings_realization_find_period(int $id): ?array
{
    foreach (savings_realization_periods() as $row) if ((int)$row['id']===$id) return $row;
    return null;
}

function savings_realization_evidence(?int $opportunityId=null,?int $periodId=null): array
{
    if (data_is_demo()) $rows=savings_realization_demo_collection('savings_evidence_links','savings_realization_demo_evidence');
    else {
        if (!savings_realization_tables_ready()) return [];
        $rows=production_database_connection()->query('SELECT * FROM savings_evidence_links ORDER BY evidence_date DESC,id DESC')->fetchAll();
    }
    $rows=savings_realization_filter_rows($rows);
    if ($opportunityId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['opportunity_id']===$opportunityId));
    if ($periodId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)($row['realization_period_id']??0)===$periodId));
    return $rows;
}

function savings_realization_find_evidence(int $id): ?array
{
    foreach (savings_realization_evidence() as $row) if ((int)$row['id']===$id) return $row;
    return null;
}

function savings_realization_validations(?int $opportunityId=null,?int $periodId=null): array
{
    if (data_is_demo()) $rows=savings_realization_demo_collection('savings_finance_validations','savings_realization_demo_validations');
    else {
        if (!savings_realization_tables_ready()) return [];
        $rows=production_database_connection()->query('SELECT * FROM savings_finance_validations ORDER BY created_at DESC,id DESC')->fetchAll();
    }
    $rows=savings_realization_filter_rows($rows);
    if ($opportunityId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['opportunity_id']===$opportunityId));
    if ($periodId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['realization_period_id']===$periodId));
    return $rows;
}

function savings_realization_leakage(?int $opportunityId=null,?int $periodId=null): array
{
    if (data_is_demo()) $rows=savings_realization_demo_collection('savings_leakage_events','savings_realization_demo_leakage');
    else {
        if (!savings_realization_tables_ready()) return [];
        $rows=production_database_connection()->query('SELECT * FROM savings_leakage_events ORDER BY detected_date DESC,id DESC')->fetchAll();
    }
    $rows=savings_realization_filter_rows($rows);
    if ($opportunityId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['opportunity_id']===$opportunityId));
    if ($periodId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)($row['realization_period_id']??0)===$periodId));
    return $rows;
}

function savings_realization_find_leakage(int $id): ?array
{
    foreach (savings_realization_leakage() as $row) if ((int)$row['id']===$id) return $row;
    return null;
}

function savings_realization_events(?int $opportunityId=null): array
{
    if (data_is_demo()) $rows=savings_realization_demo_collection('savings_governance_events','savings_realization_demo_events');
    else {
        if (!savings_realization_tables_ready()) return [];
        $rows=production_database_connection()->query('SELECT * FROM savings_governance_events ORDER BY created_at DESC,id DESC')->fetchAll();
    }
    $rows=savings_realization_filter_rows($rows);
    if ($opportunityId!==null) $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['opportunity_id']===$opportunityId));
    return $rows;
}

function savings_realization_save_baseline(array $record): array
{
    savings_realization_require_tables();
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    $record['updated_at']=date('Y-m-d H:i:s');
    if (data_is_demo()) return savings_realization_demo_save('savings_baselines',$record,'savings_realization_demo_baselines');
    $pdo=production_database_connection();
    $id=(int)($record['id']??0);
    $fields=['opportunity_id','version_number','baseline_type','period_start','period_end','baseline_volume','baseline_unit_cost','baseline_total_cost','currency_code','methodology','assumptions','supplier_id','contract_id','status','owner_id','reviewer_id','approval_id','locked_at','evidence_note'];
    $values=[];foreach($fields as $field)$values[]=$record[$field]??null;
    if ($id) {
        $values[]=$id;
        $pdo->prepare('UPDATE savings_baselines SET '.implode(',',array_map(static fn(string $field):string=>$field.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);
    } else {
        $pdo->prepare('INSERT INTO savings_baselines ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);
        $id=(int)$pdo->lastInsertId();
    }
    $record['id']=$id;
    return $record;
}

function savings_realization_save_period(array $record): array
{
    savings_realization_require_tables();
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    $record['updated_at']=date('Y-m-d H:i:s');
    if (data_is_demo()) return savings_realization_demo_save('savings_realization_periods',$record,'savings_realization_demo_periods');
    $pdo=production_database_connection();
    $id=(int)($record['id']??0);
    $fields=['opportunity_id','period_start','period_end','fiscal_year','fiscal_period','planned_hard_savings','planned_cost_avoidance','planned_recoveries','planned_working_capital','actual_hard_savings','actual_cost_avoidance','actual_recoveries','actual_working_capital','implementation_cost','operating_cost','leakage_amount','adjustment_amount','gross_realized_value','net_realized_value','status','owner_id','reviewer_id','approval_id','submitted_at','validated_at','closed_at','evidence_note'];
    $values=[];foreach($fields as $field)$values[]=$record[$field]??null;
    if ($id) {
        $values[]=$id;
        $pdo->prepare('UPDATE savings_realization_periods SET '.implode(',',array_map(static fn(string $field):string=>$field.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);
    } else {
        $pdo->prepare('INSERT INTO savings_realization_periods ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);
        $id=(int)$pdo->lastInsertId();
    }
    $record['id']=$id;
    return $record;
}

function savings_realization_save_evidence(array $record): array
{
    savings_realization_require_tables();
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    $record['updated_at']=date('Y-m-d H:i:s');
    if (data_is_demo()) return savings_realization_demo_save('savings_evidence_links',$record,'savings_realization_demo_evidence');
    $pdo=production_database_connection();
    $id=(int)($record['id']??0);
    $fields=['opportunity_id','realization_period_id','entity_type','entity_id','evidence_reference','evidence_amount','evidence_date','status','verified_by','verified_at','evidence_note','created_by'];
    $values=[];foreach($fields as $field)$values[]=$record[$field]??null;
    if ($id) {
        $values[]=$id;
        $pdo->prepare('UPDATE savings_evidence_links SET '.implode(',',array_map(static fn(string $field):string=>$field.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);
    } else {
        $pdo->prepare('INSERT INTO savings_evidence_links ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);
        $id=(int)$pdo->lastInsertId();
    }
    $record['id']=$id;
    return $record;
}

function savings_realization_save_validation(array $record): array
{
    savings_realization_require_tables();
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    if (data_is_demo()) return savings_realization_demo_save('savings_finance_validations',$record,'savings_realization_demo_validations');
    $pdo=production_database_connection();
    $fields=['opportunity_id','realization_period_id','validation_number','reviewer_id','decision','completeness_score','validated_hard_savings','validated_cost_avoidance','validated_recoveries','validated_working_capital','validated_net_value','comments','decided_at'];
    $values=[];foreach($fields as $field)$values[]=$record[$field]??null;
    $pdo->prepare('INSERT INTO savings_finance_validations ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);
    $record['id']=(int)$pdo->lastInsertId();
    return $record;
}

function savings_realization_save_leakage(array $record): array
{
    savings_realization_require_tables();
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    $record['updated_at']=date('Y-m-d H:i:s');
    if (data_is_demo()) return savings_realization_demo_save('savings_leakage_events',$record,'savings_realization_demo_leakage');
    $pdo=production_database_connection();
    $id=(int)($record['id']??0);
    $fields=['opportunity_id','realization_period_id','leakage_type','detected_date','amount','recovered_amount','status','owner_id','due_date','source_entity_type','source_entity_id','root_cause','corrective_action','evidence_note'];
    $values=[];foreach($fields as $field)$values[]=$record[$field]??null;
    if ($id) {
        $values[]=$id;
        $pdo->prepare('UPDATE savings_leakage_events SET '.implode(',',array_map(static fn(string $field):string=>$field.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);
    } else {
        $pdo->prepare('INSERT INTO savings_leakage_events ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);
        $id=(int)$pdo->lastInsertId();
    }
    $record['id']=$id;
    return $record;
}

function savings_realization_add_event(int $opportunityId,?int $periodId,string $type,?string $from,?string $to,string $severity,float $value,string $evidence): array
{
    savings_realization_require_tables();
    $record=['id'=>null,'opportunity_id'=>$opportunityId,'realization_period_id'=>$periodId,'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'severity'=>$severity,'value_amount'=>$value,'evidence_note'=>mb_substr($evidence,0,5000),'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];
    if (data_is_demo()) return savings_realization_demo_save('savings_governance_events',$record,'savings_realization_demo_events');
    $pdo=production_database_connection();
    $pdo->prepare('INSERT INTO savings_governance_events (opportunity_id,realization_period_id,event_type,from_status,to_status,severity,value_amount,evidence_note,created_by) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$opportunityId,$periodId,$type,$from,$to,$severity,$value,$record['evidence_note'],$record['created_by']]);
    $record['id']=(int)$pdo->lastInsertId();
    return $record;
}
