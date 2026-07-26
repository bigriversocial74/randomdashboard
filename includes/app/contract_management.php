<?php
declare(strict_types=1);

require_once __DIR__ . '/performance_management.php';

function contract_statuses(): array { return ['draft','active','expiring','expired','terminated']; }
function contract_review_statuses(): array { return ['draft','submitted','approved','changes_requested','implemented']; }
function contract_obligation_statuses(): array { return ['not_started','in_progress','met','breached','waived','cancelled']; }
function contract_amendment_statuses(): array { return ['draft','pending_approval','approved','effective','rejected','superseded']; }
function contract_decision_statuses(): array { return ['draft','submitted','approved','changes_requested','implemented','cancelled']; }
function contract_renewal_decisions(): array { return ['renew_unchanged','renew_with_conditions','renegotiate','short_term_extension','competitive_rebid','transition_alternate','terminate_for_cause','expire_without_renewal']; }
function contract_event_types(): array { return ['contract_created','contract_updated','obligation_created','obligation_updated','obligation_breached','amendment_created','amendment_updated','decision_created','decision_updated','decision_submitted','decision_approved','decision_implemented','notice_due','sla_breach','contract_expired','contract_terminated']; }
function contract_json_array(mixed $value): array { if(is_array($value))return $value;if(!is_string($value)||trim($value)==='')return [];$decoded=json_decode($value,true);return is_array($decoded)?$decoded:[]; }

function contract_tables_ready(): bool
{
    if(data_is_demo())return true;
    $pdo=production_database_connection();if(!$pdo)return false;
    try{
        foreach(['supplier_contract_governance_profiles','supplier_contract_obligations','supplier_contract_amendments','supplier_contract_renewal_decisions','supplier_contract_events'] as $table)$pdo->query("SELECT id FROM {$table} LIMIT 1");
        return true;
    }catch(Throwable){return false;}
}

function contract_decode_record(array $row): array
{
    $row['id']=(int)($row['id']??0);
    $row['supplier_id']=(int)($row['supplier_id']??0);
    $row['company_id']=isset($row['company_id'])&&$row['company_id']!==''&&$row['company_id']!==null?(int)$row['company_id']:null;
    $row['number']=(string)($row['number']??$row['contract_number']??'');
    $row['annual_value']=(float)($row['annual_value']??$row['estimated_annual_value']??0);
    $row['auto_renew']=!empty($row['auto_renew']);
    $row['owner_id']=(int)($row['owner_id']??$row['created_by']??data_find('suppliers',(int)($row['supplier_id']??0))['owner_id']??1);
    $row['reviewer_id']=(int)($row['reviewer_id']??6);
    $row['renewal_notice_days']=(int)($row['renewal_notice_days']??90);
    $row['review_status']=(string)($row['review_status']??'draft');
    $row['risk_tier']=(string)($row['risk_tier']??data_find('suppliers',(int)($row['supplier_id']??0))['risk']??'medium');
    $row['performance_review_id']=isset($row['performance_review_id'])?(int)$row['performance_review_id']:null;
    $row['approval_id']=isset($row['approval_id'])?(int)$row['approval_id']:null;
    $row['committed_spend']=(float)($row['committed_spend']??$row['annual_value']);
    $row['actual_spend']=(float)($row['actual_spend']??0);
    $row['off_contract_spend']=(float)($row['off_contract_spend']??0);
    $row['evidence_note']=(string)($row['evidence_note']??'Review executed contract, supplier performance, purchase-order activity, obligation evidence, amendments, notice requirements, and renewal alternatives.');
    $row['next_review_date']=(string)($row['next_review_date']??($row['end_date']??date('Y-m-d',strtotime('+90 days'))));
    $row['payment_terms']=(string)($row['payment_terms']??data_find('suppliers',(int)($row['supplier_id']??0))['payment_terms']??'');
    $row['freight_terms']=(string)($row['freight_terms']??'FOB destination');
    $row['warranty_terms']=(string)($row['warranty_terms']??'Supplier warranty and remedy obligations are governed by the executed agreement.');
    $row['service_level_terms']=(string)($row['service_level_terms']??'On-time delivery, quality, fill rate, responsiveness, and service commitments are measured through supplier performance reviews.');
    $row['document_path']=(string)($row['document_path']??'');
    $row['created_at']=(string)($row['created_at']??date('Y-m-d H:i:s'));
    $row['updated_at']=(string)($row['updated_at']??$row['created_at']);
    return $row;
}

function contract_records(?int $supplierId=null): array
{
    if(data_is_demo()){
        $rows=array_map('contract_decode_record',array_values($_SESSION['gruber_demo_state']['contracts']??data_collection('contracts')));
        $rows=array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));
        if($supplierId)$rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['supplier_id']===$supplierId));
        usort($rows,static fn(array $a,array $b):int=>strcmp((string)($a['end_date']??'9999-12-31'),(string)($b['end_date']??'9999-12-31')));
        return $rows;
    }
    $pdo=production_database_connection();if(!$pdo)return [];
    $profileReady=contract_tables_ready();
    $select=$profileReady?',gp.owner_id,gp.reviewer_id,gp.renewal_notice_days,gp.review_status,gp.risk_tier,gp.performance_review_id,gp.approval_id,gp.committed_spend,gp.actual_spend,gp.off_contract_spend,gp.evidence_note,gp.next_review_date':'';
    $join=$profileReady?' LEFT JOIN supplier_contract_governance_profiles gp ON gp.contract_id=sc.id':'';
    $where=[];$params=[];
    if(current_company_id()!=='enterprise'){$where[]='sc.company_id=?';$params[]=(int)current_company_id();}
    if($supplierId){$where[]='sc.supplier_id=?';$params[]=$supplierId;}
    $stmt=$pdo->prepare('SELECT sc.*'.$select.' FROM supplier_contracts sc'.$join.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY COALESCE(sc.end_date,"9999-12-31"),sc.id');
    $stmt->execute($params);
    return array_map('contract_decode_record',$stmt->fetchAll());
}

function contract_find_record(int $id): ?array { foreach(contract_records() as $row)if((int)$row['id']===$id)return $row;return null; }
function contract_default_record(): ?array { $rows=contract_records();foreach($rows as $row)if(($row['status']??'')==='expiring')return $row;return $rows[0]??null; }
function contract_latest_performance(int $supplierId): ?array { return performance_reviews($supplierId)[0]??null; }

function contract_purchase_spend(array $contract): float
{
    $sum=0.0;
    foreach(data_visible_collection('purchase_orders') as $po){
        if((int)($po['supplier_id']??0)!==(int)$contract['supplier_id'])continue;
        if($contract['company_id']!==null&&(int)($po['company_id']??0)!==(int)$contract['company_id'])continue;
        $orderDate=(string)($po['order_date']??'');
        if(!empty($contract['start_date'])&&$orderDate!==''&&$orderDate<(string)$contract['start_date'])continue;
        if(!empty($contract['end_date'])&&$orderDate!==''&&$orderDate>(string)$contract['end_date'])continue;
        $sum+=(float)($po['total_amount']??0);
    }
    return round($sum,2);
}

function contract_alternate_supplier_count(array $contract): int
{
    $supplier=data_find('suppliers',(int)$contract['supplier_id']);$category=(int)($supplier['category_id']??0);$count=0;
    foreach(data_visible_collection('suppliers') as $candidate){
        if((int)$candidate['id']===(int)$contract['supplier_id'])continue;
        if($category>0&&(int)($candidate['category_id']??0)!==$category)continue;
        if(!in_array((string)($candidate['status']??''),['preferred','approved','candidate'],true))continue;
        if(in_array((string)($candidate['risk']??''),['critical'],true))continue;
        $count++;
    }
    return $count;
}

function contract_blueprint(?array $contract=null): array
{
    $contract??=contract_default_record();if(!$contract)throw new RuntimeException('A supplier contract is required.');
    $performance=contract_latest_performance((int)$contract['supplier_id']);
    $performanceMetrics=$performance?performance_metrics($performance,performance_actions((int)$performance['id'])):[];
    $performanceRecommendation=(string)($performance['recommendation']??$performanceMetrics['recommendation']??'approved');
    $decision=match($performanceRecommendation){
        'disqualified'=>'terminate_for_cause',
        'probationary'=>'competitive_rebid',
        'conditional'=>'renew_with_conditions',
        'preferred'=>'renew_unchanged',
        default=>'renegotiate',
    };
    $actual=contract_purchase_spend($contract);
    $contract['actual_spend']=$contract['actual_spend']>0?$contract['actual_spend']:$actual;
    $contract['committed_spend']=$contract['committed_spend']>0?$contract['committed_spend']:$contract['annual_value'];
    $contract['off_contract_spend']=max($contract['off_contract_spend'],$actual>$contract['annual_value']?round($actual-$contract['annual_value'],2):0);
    $contract['performance_review_id']=$contract['performance_review_id']??($performance['id']??null);
    $contract['risk_tier']=(string)($performance['risk_tier']??$contract['risk_tier']);
    return ['contract'=>$contract,'supplier'=>data_find('suppliers',(int)$contract['supplier_id']),'performance'=>$performance,'performance_metrics'=>$performanceMetrics,'recommended_decision'=>$decision,'alternate_supplier_count'=>contract_alternate_supplier_count($contract)];
}

function contract_demo_seed_obligations(): array
{
    return [
        ['id'=>1,'obligation_number'=>'OBL-2026-0001','contract_id'=>2,'company_id'=>null,'title'=>'Quarterly service-level review','obligation_type'=>'service_level','owner_id'=>3,'due_date'=>'2026-08-15','status'=>'in_progress','target_value'=>95.0,'actual_value'=>91.0,'unit'=>'percent','evidence_note'=>'Q2 scorecard and Section 15 review identify delivery and fill-rate gaps.','completed_at'=>null,'created_at'=>'2026-07-26 19:00:00','updated_at'=>'2026-07-26 19:00:00'],
        ['id'=>2,'obligation_number'=>'OBL-2026-0002','contract_id'=>4,'company_id'=>4,'title'=>'Corrective-action evidence package','obligation_type'=>'quality_remedy','owner_id'=>3,'due_date'=>'2026-08-01','status'=>'breached','target_value'=>100.0,'actual_value'=>35.0,'unit'=>'percent','evidence_note'=>'Required supplier process-capability and shipment recovery evidence remains incomplete.','completed_at'=>null,'created_at'=>'2026-07-26 19:05:00','updated_at'=>'2026-07-26 19:05:00'],
    ];
}
function contract_demo_seed_amendments(): array
{
    return [['id'=>1,'amendment_number'=>'AMD-2026-0001','contract_id'=>2,'company_id'=>null,'title'=>'Service-level and escalation amendment','status'=>'draft','effective_date'=>'2026-10-01','value_change'=>-25000.0,'term_change_days'=>365,'before_terms'=>'Current SLA remedies and escalation windows.','after_terms'=>'Stronger delivery credits, executive escalation, and monthly capacity evidence.','rationale'=>'Section 15 performance evidence supports tighter service remedies before renewal.','approval_id'=>null,'created_by'=>3,'created_at'=>'2026-07-26 19:10:00','updated_at'=>'2026-07-26 19:10:00']];
}
function contract_demo_seed_decisions(): array
{
    return [['id'=>1,'decision_number'=>'REN-2026-0001','contract_id'=>2,'company_id'=>null,'supplier_id'=>2,'performance_review_id'=>null,'decision'=>'renegotiate','status'=>'draft','owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'current_annual_value'=>760000.0,'proposed_annual_value'=>735000.0,'value_change'=>-25000.0,'effective_date'=>'2026-10-01','alternative_supplier_id'=>3,'rationale'=>'Renewal should be conditioned on price, delivery, fill-rate, and escalation improvements.','negotiation_objectives'=>'Secure service credits, monthly performance evidence, improved delivery targets, and reduced annual value.','evidence_note'=>'Contract, PO, scorecard, Section 15 review, and alternate-supplier evidence assembled.','implemented_at'=>null,'created_at'=>'2026-07-26 19:15:00','updated_at'=>'2026-07-26 19:15:00']];
}
function contract_demo_seed_events(): array
{
    return [
        ['id'=>1,'contract_id'=>2,'event_type'=>'notice_due','entity_type'=>'contract','entity_id'=>2,'from_status'=>'active','to_status'=>'expiring','severity'=>'high','evidence_note'=>'Renewal notice deadline is approaching.','created_by'=>3,'created_at'=>'2026-07-26 19:00:00'],
        ['id'=>2,'contract_id'=>4,'event_type'=>'sla_breach','entity_type'=>'obligation','entity_id'=>2,'from_status'=>'in_progress','to_status'=>'breached','severity'=>'critical','evidence_note'=>'Quality-remedy evidence missed the contractual checkpoint.','created_by'=>3,'created_at'=>'2026-07-26 19:05:00'],
    ];
}

function contract_obligations(int $contractId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_contract_obligations']))$_SESSION['gruber_demo_state']['supplier_contract_obligations']=contract_demo_seed_obligations();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['supplier_contract_obligations'],static fn(array $r):bool=>(int)$r['contract_id']===$contractId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$a['due_date'],(string)$b['due_date']));return $rows;}
    if(!contract_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM supplier_contract_obligations WHERE contract_id=? ORDER BY due_date,id');$stmt->execute([$contractId]);return $stmt->fetchAll();
}
function contract_amendments(int $contractId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_contract_amendments']))$_SESSION['gruber_demo_state']['supplier_contract_amendments']=contract_demo_seed_amendments();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['supplier_contract_amendments'],static fn(array $r):bool=>(int)$r['contract_id']===$contractId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));return $rows;}
    if(!contract_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM supplier_contract_amendments WHERE contract_id=? ORDER BY updated_at DESC,id DESC');$stmt->execute([$contractId]);return $stmt->fetchAll();
}
function contract_decisions(int $contractId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_contract_renewal_decisions']))$_SESSION['gruber_demo_state']['supplier_contract_renewal_decisions']=contract_demo_seed_decisions();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['supplier_contract_renewal_decisions'],static fn(array $r):bool=>(int)$r['contract_id']===$contractId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));return $rows;}
    if(!contract_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM supplier_contract_renewal_decisions WHERE contract_id=? ORDER BY updated_at DESC,id DESC');$stmt->execute([$contractId]);return $stmt->fetchAll();
}
function contract_find_obligation(int $id): ?array { foreach(contract_records() as $contract)foreach(contract_obligations((int)$contract['id']) as $row)if((int)$row['id']===$id)return $row;return null; }
function contract_find_amendment(int $id): ?array { foreach(contract_records() as $contract)foreach(contract_amendments((int)$contract['id']) as $row)if((int)$row['id']===$id)return $row;return null; }
function contract_find_decision(int $id): ?array { foreach(contract_records() as $contract)foreach(contract_decisions((int)$contract['id']) as $row)if((int)$row['id']===$id)return $row;return null; }

function contract_events(int $contractId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_contract_events']))$_SESSION['gruber_demo_state']['supplier_contract_events']=contract_demo_seed_events();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['supplier_contract_events'],static fn(array $r):bool=>(int)$r['contract_id']===$contractId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;}
    if(!contract_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM supplier_contract_events WHERE contract_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$contractId]);return $stmt->fetchAll();
}

function contract_metrics(array $contract,array $obligations=[],array $amendments=[],array $decisions=[]): array
{
    $end=!empty($contract['end_date'])?strtotime((string)$contract['end_date']):false;$today=strtotime(date('Y-m-d'));$days=$end!==false?(int)floor(($end-$today)/86400):9999;
    $noticeDays=max(0,(int)($contract['renewal_notice_days']??90));$noticeDeadline=$end!==false?date('Y-m-d',strtotime('-'.$noticeDays.' days',$end)):null;$noticeRemaining=$noticeDeadline?(int)floor((strtotime($noticeDeadline)-$today)/86400):9999;
    $closed=0;$breached=0;$overdue=0;foreach($obligations as $row){$status=(string)($row['status']??'not_started');if(in_array($status,['met','waived','cancelled'],true))$closed++;if($status==='breached')$breached++;if(!empty($row['due_date'])&&$row['due_date']<date('Y-m-d')&&!in_array($status,['met','waived','cancelled'],true))$overdue++;}
    $performance=!empty($contract['performance_review_id'])?performance_find_review((int)$contract['performance_review_id']):contract_latest_performance((int)$contract['supplier_id']);$performanceMetrics=$performance?performance_metrics($performance,performance_actions((int)$performance['id'])):[];
    $correctiveOpen=$performance?count(array_filter(performance_actions((int)$performance['id']),static fn(array $a):bool=>!in_array((string)$a['status'],['verified','cancelled'],true))):0;
    $amendmentDelta=array_sum(array_map(static fn(array $a):float=>in_array((string)$a['status'],['approved','effective'],true)?(float)$a['value_change']:0,$amendments));
    $obligationPct=count($obligations)?round(($closed/count($obligations))*100,1):100.0;$sustainability=(float)($performanceMetrics['sustainability_pct']??80);$performanceScore=(float)($performanceMetrics['current_score']??80);
    $noticeScore=$noticeRemaining>=30?100:($noticeRemaining>=0?70:25);$correctiveScore=$correctiveOpen===0?100:max(0,100-$correctiveOpen*25);$readiness=round($sustainability*.35+$obligationPct*.25+$noticeScore*.15+$correctiveScore*.15+($contract['evidence_note']!==''?10:0),1);
    $actual=(float)($contract['actual_spend']??0);$committed=(float)($contract['committed_spend']??$contract['annual_value']);$latest=$decisions[0]??null;
    return ['days_to_expiry'=>$days,'notice_deadline'=>$noticeDeadline,'days_to_notice_deadline'=>$noticeRemaining,'annual_value'=>(float)$contract['annual_value'],'committed_spend'=>$committed,'actual_spend'=>$actual,'off_contract_spend'=>(float)($contract['off_contract_spend']??0),'spend_variance'=>round($actual-$committed,2),'obligation_count'=>count($obligations),'obligation_completion_pct'=>$obligationPct,'breached_obligations'=>$breached,'overdue_obligations'=>$overdue,'amendment_value_change'=>round($amendmentDelta,2),'performance_score'=>$performanceScore,'performance_sustainability_pct'=>$sustainability,'sla_breach_count'=>(int)($performanceMetrics['regression_count']??0)+$breached,'open_corrective_actions'=>$correctiveOpen,'renewal_readiness_pct'=>$readiness,'risk_tier'=>(string)($performance['risk_tier']??$contract['risk_tier']),'supplier_recommendation'=>(string)($performance['recommendation']??'approved'),'latest_decision'=>$latest['decision']??null,'latest_decision_status'=>$latest?contract_decision_effective_status($latest):null];
}

function contract_decision_requires_approval(array $decision,array $contract): bool
{
    $type=(string)($decision['decision']??'renegotiate');$change=abs((float)($decision['value_change']??0));$value=(float)($decision['proposed_annual_value']??$contract['annual_value']);
    return $type!=='renew_unchanged'||$change>=25000||$value>=100000||in_array($type,['competitive_rebid','transition_alternate','terminate_for_cause','expire_without_renewal'],true);
}
function contract_decision_effective_status(array $decision): string
{
    $stored=(string)($decision['status']??'draft');if(in_array($stored,['implemented','cancelled'],true))return $stored;$approvalId=(int)($decision['approval_id']??0);
    if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested'],true))return (string)$approval['status'];}
    return $stored;
}
function contract_next_number(string $prefix,string $sessionKey,string $table,array $seed): string
{
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state'][$sessionKey]??$seed;$count=count($rows);}
    else{$count=contract_tables_ready()?(int)production_database_connection()->query('SELECT COUNT(*) FROM '.$table)->fetchColumn():0;}
    return $prefix.'-'.date('Y').'-'.str_pad((string)($count+1),4,'0',STR_PAD_LEFT);
}

function contract_save_master(array $record): array
{
    $record=contract_decode_record($record);$record['updated_at']=date('Y-m-d H:i:s');
    if(data_is_demo()){if((int)$record['id']<=0)$record['id']=data_next_id('contracts');data_upsert('contracts',$record);return contract_find_record((int)$record['id'])??$record;}
    $pdo=production_database_connection();if(!$pdo)throw new RuntimeException('The production database is unavailable.');if(!contract_tables_ready())throw new RuntimeException('Import the Section 16 contract-governance migration before saving Production Data contract governance.');
    $id=(int)($record['id']??0);
    $params=[$record['supplier_id'],$record['company_id'],$record['number'],$record['title'],$record['start_date']?:null,$record['end_date']?:null,$record['auto_renew']?1:0,$record['payment_terms'],$record['freight_terms'],$record['warranty_terms'],$record['service_level_terms'],$record['annual_value'],$record['status'],$record['document_path']?:null,(int)current_user()['id']];
    if($id>0){$pdo->prepare('UPDATE supplier_contracts SET supplier_id=?,company_id=?,contract_number=?,title=?,start_date=?,end_date=?,auto_renew=?,payment_terms=?,freight_terms=?,warranty_terms=?,service_level_terms=?,estimated_annual_value=?,status=?,document_path=?,updated_at=NOW() WHERE id=?')->execute([...$params,$id]);}
    else{$pdo->prepare('INSERT INTO supplier_contracts (supplier_id,company_id,contract_number,title,start_date,end_date,auto_renew,payment_terms,freight_terms,warranty_terms,service_level_terms,estimated_annual_value,status,document_path,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}
    $pdo->prepare('INSERT INTO supplier_contract_governance_profiles (contract_id,owner_id,reviewer_id,renewal_notice_days,review_status,risk_tier,performance_review_id,approval_id,committed_spend,actual_spend,off_contract_spend,evidence_note,next_review_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE owner_id=VALUES(owner_id),reviewer_id=VALUES(reviewer_id),renewal_notice_days=VALUES(renewal_notice_days),review_status=VALUES(review_status),risk_tier=VALUES(risk_tier),performance_review_id=VALUES(performance_review_id),approval_id=VALUES(approval_id),committed_spend=VALUES(committed_spend),actual_spend=VALUES(actual_spend),off_contract_spend=VALUES(off_contract_spend),evidence_note=VALUES(evidence_note),next_review_date=VALUES(next_review_date),updated_at=NOW()')->execute([$id,$record['owner_id'],$record['reviewer_id'],$record['renewal_notice_days'],$record['review_status'],$record['risk_tier'],$record['performance_review_id'],$record['approval_id'],$record['committed_spend'],$record['actual_spend'],$record['off_contract_spend'],$record['evidence_note'],$record['next_review_date']?:null]);
    $record['id']=$id;return contract_find_record($id)??$record;
}

function contract_save_obligation(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['obligation_number']=$record['obligation_number']??contract_next_number('OBL','supplier_contract_obligations','supplier_contract_obligations',contract_demo_seed_obligations());
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['supplier_contract_obligations']??contract_demo_seed_obligations();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['supplier_contract_obligations']=$rows;return $record;}
    if(!contract_tables_ready())throw new RuntimeException('Import the Section 16 migration before saving obligations.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['obligation_number'],$record['contract_id'],$record['company_id'],$record['title'],$record['obligation_type'],$record['owner_id'],$record['due_date'],$record['status'],$record['target_value'],$record['actual_value'],$record['unit'],$record['evidence_note'],$record['completed_at'],$record['created_at'],$record['updated_at']];if($id>0){$pdo->prepare('UPDATE supplier_contract_obligations SET obligation_number=?,contract_id=?,company_id=?,title=?,obligation_type=?,owner_id=?,due_date=?,status=?,target_value=?,actual_value=?,unit=?,evidence_note=?,completed_at=?,created_at=?,updated_at=? WHERE id=?')->execute([...$params,$id]);}else{$pdo->prepare('INSERT INTO supplier_contract_obligations (obligation_number,contract_id,company_id,title,obligation_type,owner_id,due_date,status,target_value,actual_value,unit,evidence_note,completed_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}
function contract_save_amendment(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['amendment_number']=$record['amendment_number']??contract_next_number('AMD','supplier_contract_amendments','supplier_contract_amendments',contract_demo_seed_amendments());
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['supplier_contract_amendments']??contract_demo_seed_amendments();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['supplier_contract_amendments']=$rows;return $record;}
    if(!contract_tables_ready())throw new RuntimeException('Import the Section 16 migration before saving amendments.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['amendment_number'],$record['contract_id'],$record['company_id'],$record['title'],$record['status'],$record['effective_date'],$record['value_change'],$record['term_change_days'],$record['before_terms'],$record['after_terms'],$record['rationale'],$record['approval_id'],$record['created_by'],$record['created_at'],$record['updated_at']];if($id>0){$pdo->prepare('UPDATE supplier_contract_amendments SET amendment_number=?,contract_id=?,company_id=?,title=?,status=?,effective_date=?,value_change=?,term_change_days=?,before_terms=?,after_terms=?,rationale=?,approval_id=?,created_by=?,created_at=?,updated_at=? WHERE id=?')->execute([...$params,$id]);}else{$pdo->prepare('INSERT INTO supplier_contract_amendments (amendment_number,contract_id,company_id,title,status,effective_date,value_change,term_change_days,before_terms,after_terms,rationale,approval_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}
function contract_save_decision(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['decision_number']=$record['decision_number']??contract_next_number('REN','supplier_contract_renewal_decisions','supplier_contract_renewal_decisions',contract_demo_seed_decisions());
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['supplier_contract_renewal_decisions']??contract_demo_seed_decisions();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['supplier_contract_renewal_decisions']=$rows;return $record;}
    if(!contract_tables_ready())throw new RuntimeException('Import the Section 16 migration before saving renewal decisions.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['decision_number'],$record['contract_id'],$record['company_id'],$record['supplier_id'],$record['performance_review_id'],$record['decision'],$record['status'],$record['owner_id'],$record['reviewer_id'],$record['approval_id'],$record['current_annual_value'],$record['proposed_annual_value'],$record['value_change'],$record['effective_date'],$record['alternative_supplier_id'],$record['rationale'],$record['negotiation_objectives'],$record['evidence_note'],$record['implemented_at'],$record['created_at'],$record['updated_at']];if($id>0){$pdo->prepare('UPDATE supplier_contract_renewal_decisions SET decision_number=?,contract_id=?,company_id=?,supplier_id=?,performance_review_id=?,decision=?,status=?,owner_id=?,reviewer_id=?,approval_id=?,current_annual_value=?,proposed_annual_value=?,value_change=?,effective_date=?,alternative_supplier_id=?,rationale=?,negotiation_objectives=?,evidence_note=?,implemented_at=?,created_at=?,updated_at=? WHERE id=?')->execute([...$params,$id]);}else{$pdo->prepare('INSERT INTO supplier_contract_renewal_decisions (decision_number,contract_id,company_id,supplier_id,performance_review_id,decision,status,owner_id,reviewer_id,approval_id,current_annual_value,proposed_annual_value,value_change,effective_date,alternative_supplier_id,rationale,negotiation_objectives,evidence_note,implemented_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}
function contract_add_event(int $contractId,string $type,string $entityType,int $entityId,?string $from,?string $to,string $severity,string $note): array
{
    if(!in_array($type,contract_event_types(),true))$type='contract_updated';if(!in_array($severity,['low','medium','high','critical'],true))$severity='medium';$row=['contract_id'=>$contractId,'event_type'=>$type,'entity_type'=>$entityType,'entity_id'=>$entityId,'from_status'=>$from,'to_status'=>$to,'severity'=>$severity,'evidence_note'=>mb_substr(trim($note),0,5000),'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['supplier_contract_events']??contract_demo_seed_events();$row['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$row;$_SESSION['gruber_demo_state']['supplier_contract_events']=$rows;return $row;}
    if(!contract_tables_ready())throw new RuntimeException('Import the Section 16 migration before writing contract events.');$stmt=production_database_connection()->prepare('INSERT INTO supplier_contract_events (contract_id,event_type,entity_type,entity_id,from_status,to_status,severity,evidence_note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');$stmt->execute([$row['contract_id'],$row['event_type'],$row['entity_type'],$row['entity_id'],$row['from_status'],$row['to_status'],$row['severity'],$row['evidence_note'],$row['created_by'],$row['created_at']]);$row['id']=(int)production_database_connection()->lastInsertId();return $row;
}
function contract_csv_cell(mixed $value): string { $value=(string)$value;if($value!==''&&preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value; }
function contract_export_csv(array $contract,array $metrics,array $obligations,array $amendments,array $decisions,array $events): never
{
    require_permission('reports.export');header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="contract-governance-'.date('Ymd-His').'.csv"');$out=fopen('php://output','wb');
    foreach([['Contract',$contract['number']],['Title',$contract['title']],['Supplier',data_supplier_name($contract['supplier_id'])],['Status',$contract['status']],['Annual value',$contract['annual_value']],['End date',$contract['end_date']],['Evidence',$contract['evidence_note']]] as $row)fputcsv($out,array_map('contract_csv_cell',$row));
    fputcsv($out,[]);fputcsv($out,['Metric','Value']);foreach($metrics as $key=>$value)if(!is_array($value))fputcsv($out,array_map('contract_csv_cell',[status_label($key),$value]));
    fputcsv($out,[]);fputcsv($out,['Obligation','Status','Due date','Target','Actual','Evidence']);foreach($obligations as $row)fputcsv($out,array_map('contract_csv_cell',[$row['title'],$row['status'],$row['due_date'],$row['target_value'].' '.$row['unit'],$row['actual_value'].' '.$row['unit'],$row['evidence_note']]));
    fputcsv($out,[]);fputcsv($out,['Amendment','Status','Effective date','Value change','Rationale']);foreach($amendments as $row)fputcsv($out,array_map('contract_csv_cell',[$row['title'],$row['status'],$row['effective_date'],$row['value_change'],$row['rationale']]));
    fputcsv($out,[]);fputcsv($out,['Decision','Status','Effective date','Value change','Rationale']);foreach($decisions as $row)fputcsv($out,array_map('contract_csv_cell',[$row['decision'],$row['status'],$row['effective_date'],$row['value_change'],$row['rationale']]));
    fputcsv($out,[]);fputcsv($out,['Event','Entity','From','To','Severity','Evidence','Created at']);foreach($events as $row)fputcsv($out,array_map('contract_csv_cell',[$row['event_type'],$row['entity_type'],$row['from_status'],$row['to_status'],$row['severity'],$row['evidence_note'],$row['created_at']]));fclose($out);exit;
}
