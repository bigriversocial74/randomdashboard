<?php
declare(strict_types=1);

require_once __DIR__ . '/execution_management.php';

function performance_review_statuses(): array { return ['draft','submitted','monitoring','closed']; }
function performance_recommendations(): array { return ['preferred','approved','conditional','probationary','disqualified']; }
function performance_risk_tiers(): array { return ['low','medium','high','critical']; }
function performance_action_statuses(): array { return ['planned','in_progress','blocked','completed','verified','cancelled']; }
function performance_event_types(): array { return ['review_created','review_updated','submitted','monitoring_started','metric_regression','threshold_breach','corrective_action_created','corrective_action_updated','corrective_action_verified','recommendation_changed','review_closed']; }
function performance_json_array(mixed $value): array { if(is_array($value))return $value;if(!is_string($value)||trim($value)==='')return [];$decoded=json_decode($value,true);return is_array($decoded)?$decoded:[]; }

function performance_supplier_scorecards(int $supplierId): array
{
    $rows=array_values(array_filter(data_visible_collection('supplier_scorecards'),static fn(array $row):bool=>(int)($row['supplier_id']??0)===$supplierId));
    usort($rows,static fn(array $a,array $b):int=>strcmp((string)($b['period']??''),(string)($a['period']??'')));
    return $rows;
}

function performance_source_execution(int $supplierId): ?array
{
    foreach(execution_records() as $execution){
        if(execution_effective_status($execution)!=='verified')continue;
        $plan=mitigation_find_record((int)($execution['plan_id']??0));$action=mitigation_find_action((int)($execution['action_id']??0));
        $linked=(int)($action['supplier_id']??0)?:((int)($plan['supplier_id']??0));
        if($linked===$supplierId)return $execution;
    }
    return null;
}

function performance_score(array $metrics): float
{
    $lead=max(0,100-(abs((float)($metrics['lead_time_variance_days']??0))*5));
    $defects=max(0,100-((float)($metrics['defect_rate_pct']??0)*20));
    return round(
        max(0,min(100,(float)($metrics['on_time_delivery']??0)))*.18+
        max(0,min(100,(float)($metrics['quality']??0)))*.18+
        max(0,min(100,(float)($metrics['responsiveness']??0)))*.10+
        max(0,min(100,(float)($metrics['cost_competitiveness']??0)))*.10+
        max(0,min(100,(float)($metrics['fill_rate']??0)))*.14+
        max(0,min(100,(float)($metrics['service_level']??0)))*.14+
        $lead*.08+$defects*.08,1);
}

function performance_default_metrics(int $supplierId): array
{
    $score=performance_supplier_scorecards($supplierId)[0]??null;
    $overall=(float)($score['overall']??82);
    return [
        'on_time_delivery'=>(float)($score['on_time_delivery']??$overall),
        'quality'=>(float)($score['quality']??$overall),
        'responsiveness'=>(float)($score['responsiveness']??$overall),
        'cost_competitiveness'=>(float)($score['cost_competitiveness']??$overall),
        'fill_rate'=>max(0,min(100,$overall-1)),
        'service_level'=>max(0,min(100,$overall-2)),
        'lead_time_variance_days'=>$overall>=90?1:($overall>=80?3:7),
        'defect_rate_pct'=>$overall>=90?.5:($overall>=80?1.5:4.0),
        'price_variance_pct'=>$overall>=90?1.0:($overall>=80?2.5:6.0),
        'corrective_closure_pct'=>$overall>=90?100:($overall>=80?75:35),
    ];
}

function performance_review_blueprint(?array $supplier=null,?array $execution=null,int $windowDays=30): array
{
    $suppliers=data_visible_collection('suppliers');$supplier??=$suppliers[0]??null;if(!$supplier)throw new RuntimeException('A supplier is required for performance monitoring.');
    $supplierId=(int)$supplier['id'];$execution??=performance_source_execution($supplierId);$baseline=performance_default_metrics($supplierId);$current=$baseline;$verification=$execution?execution_latest_verification((int)$execution['id']):null;
    if($verification){
        $service=(float)($verification['service_level_after']??$current['service_level']);$riskBefore=(float)($verification['before_risk_score']??50);$riskAfter=(float)($verification['after_risk_score']??$riskBefore);$lift=max(0,min(12,$riskBefore-$riskAfter));
        $current['service_level']=max($current['service_level'],$service);$current['on_time_delivery']=min(100,$current['on_time_delivery']+$lift*.20);$current['fill_rate']=min(100,$current['fill_rate']+$lift*.25);$current['lead_time_variance_days']=(int)($verification['after_lead_time_days']??$current['lead_time_variance_days']);$current['corrective_closure_pct']=max($current['corrective_closure_pct'],85);
    }
    $targets=['on_time_delivery'=>95.0,'quality'=>97.0,'responsiveness'=>92.0,'cost_competitiveness'=>90.0,'fill_rate'=>95.0,'service_level'=>95.0,'lead_time_variance_days'=>2,'defect_rate_pct'=>1.0,'price_variance_pct'=>2.0,'corrective_closure_pct'=>100.0];
    $baselineScore=performance_score($baseline);$currentScore=performance_score($current);$risk=$currentScore>=93?'low':($currentScore>=82?'medium':($currentScore>=70?'high':'critical'));$recommendation=$currentScore>=93?'preferred':($currentScore>=82?'approved':($currentScore>=70?'conditional':'probationary'));
    $companyIds=array_map('intval',$supplier['company_ids']??[]);$companyId=current_company_id()==='enterprise'?($companyIds[0]??null):(int)current_company_id();
    return ['supplier'=>$supplier,'execution'=>$execution,'verification'=>$verification,'window_days'=>in_array($windowDays,[30,60,90],true)?$windowDays:30,'baseline'=>$baseline,'current'=>$current,'targets'=>$targets,'baseline_score'=>$baselineScore,'current_score'=>$currentScore,'risk_tier'=>$risk,'recommendation'=>$recommendation,'company_id'=>$companyId,'spend_exposure'=>(float)($supplier['annual_spend']??0),'savings_retained'=>(float)($verification['actual_recovery_value']??0),'evidence_note'=>'Review supplier scorecards, receiving and quality evidence, purchase-order performance, verified recovery results, corrective-action evidence, and material changes since the prior checkpoint.'];
}

function performance_target_attainment(array $current,array $targets): float
{
    $scores=[];foreach(['on_time_delivery','quality','responsiveness','cost_competitiveness','fill_rate','service_level','corrective_closure_pct'] as $key){$target=max(.01,(float)($targets[$key]??100));$scores[]=min(100,max(0,((float)($current[$key]??0)/$target)*100));}
    foreach(['lead_time_variance_days','defect_rate_pct','price_variance_pct'] as $key){$target=max(.01,(float)($targets[$key]??1));$actual=max(0,(float)($current[$key]??0));$scores[]=$actual<=$target?100:max(0,min(100,($target/$actual)*100));}
    return round(array_sum($scores)/max(1,count($scores)),1);
}

function performance_regressions(array $baseline,array $current,array $targets): array
{
    $rows=[];foreach(['on_time_delivery','quality','fill_rate','service_level','responsiveness','cost_competitiveness','corrective_closure_pct'] as $key){$before=(float)($baseline[$key]??0);$now=(float)($current[$key]??0);$target=(float)($targets[$key]??0);if($now<$before-3||$now<$target-8)$rows[]=['metric'=>$key,'before'=>$before,'current'=>$now,'threshold'=>$target,'severity'=>$now<$target-15?'critical':'high'];}
    foreach(['lead_time_variance_days','defect_rate_pct','price_variance_pct'] as $key){$before=(float)($baseline[$key]??0);$now=(float)($current[$key]??0);$target=(float)($targets[$key]??0);if($now>$before+2||$now>$target*1.5)$rows[]=['metric'=>$key,'before'=>$before,'current'=>$now,'threshold'=>$target,'severity'=>$now>$target*2.5?'critical':'high'];}
    return $rows;
}

function performance_metrics(array $review,array $actions=[]): array
{
    $baseline=performance_json_array($review['baseline']??$review['baseline_json']??[]);$current=performance_json_array($review['current']??$review['current_json']??[]);$targets=performance_json_array($review['targets']??$review['targets_json']??[]);$baseScore=performance_score($baseline);$currentScore=performance_score($current);$regressions=performance_regressions($baseline,$current,$targets);$total=count($actions);$closed=0;$blocked=0;$overdue=0;foreach($actions as $action){$status=(string)($action['status']??'planned');if(in_array($status,['completed','verified','cancelled'],true))$closed++;if($status==='blocked')$blocked++;if(!empty($action['due_date'])&&$action['due_date']<date('Y-m-d')&&!in_array($status,['completed','verified','cancelled'],true))$overdue++;}
    $improvement=$baseScore>0?(($currentScore-$baseScore)/$baseScore)*100:0;$sustainability=performance_target_attainment($current,$targets);$repeat=max((int)($review['repeated_failure_count']??0),count($regressions));$risk=$currentScore>=93&&$repeat===0?'low':($currentScore>=82&&$repeat<=1?'medium':($currentScore>=70&&$repeat<=2?'high':'critical'));$recommendation=$currentScore>=93&&$sustainability>=95?'preferred':($currentScore>=82&&$sustainability>=85?'approved':($currentScore>=70?'conditional':($currentScore>=55?'probationary':'disqualified')));
    return ['baseline_score'=>$baseScore,'current_score'=>$currentScore,'improvement_pct'=>round($improvement,1),'sustainability_pct'=>$sustainability,'regression_count'=>count($regressions),'regressions'=>$regressions,'corrective_action_count'=>$total,'corrective_action_closed'=>$closed,'corrective_action_closure_pct'=>$total?round(($closed/$total)*100,1):100.0,'blocked_actions'=>$blocked,'overdue_actions'=>$overdue,'risk_tier'=>$risk,'recommendation'=>$recommendation,'spend_exposure'=>round((float)($review['spend_exposure']??0),2),'savings_retained'=>round((float)($review['savings_retained']??0),2),'sourcing_score'=>$currentScore,'scenario_risk_adjustment'=>$risk==='critical'?25:($risk==='high'?15:($risk==='medium'?5:-5)),'mitigation_readiness_pct'=>round(($sustainability+($total?($closed/$total)*100:100))/2,1),'execution_target_confidence_pct'=>round(($sustainability+max(0,100-count($regressions)*15))/2,1)];
}

function performance_requires_approval(array $review,array $metrics=[]): bool
{
    $metrics=$metrics?:performance_metrics($review,performance_actions((int)($review['id']??0)));$recommendation=(string)($review['recommendation']??$metrics['recommendation']);return in_array($recommendation,['conditional','probationary','disqualified'],true)||in_array((string)$metrics['risk_tier'],['high','critical'],true)||(float)($review['spend_exposure']??0)>=100000;
}

function performance_demo_seed_reviews(): array
{
    $supplier1=data_find('suppliers',1);$supplier4=data_find('suppliers',4);if(!$supplier1||!$supplier4)return [];$good=performance_review_blueprint($supplier1,performance_source_execution(1),30);$bad=performance_review_blueprint($supplier4,null,60);$badCurrent=$bad['current'];$badCurrent['on_time_delivery']=68.0;$badCurrent['quality']=86.0;$badCurrent['responsiveness']=63.0;$badCurrent['cost_competitiveness']=70.0;$badCurrent['fill_rate']=66.0;$badCurrent['service_level']=64.0;$badCurrent['lead_time_variance_days']=9;$badCurrent['defect_rate_pct']=5.2;$badCurrent['price_variance_pct']=8.0;$badCurrent['corrective_closure_pct']=25.0;
    return [
        ['id'=>1,'review_number'=>'SPR-2026-0001','company_id'=>$good['company_id'],'supplier_id'=>1,'source_execution_id'=>(int)($good['execution']['id']??0)?:null,'source_verification_id'=>(int)($good['verification']['id']??0)?:null,'review_window_days'=>30,'period_start'=>'2026-06-27','period_end'=>'2026-07-26','owner_id'=>3,'reviewer_id'=>6,'status'=>'monitoring','recommendation'=>'preferred','risk_tier'=>'low','approval_id'=>null,'baseline'=>$good['baseline'],'current'=>$good['current'],'targets'=>$good['targets'],'overall_score'=>performance_score($good['current']),'improvement_pct'=>2.4,'sustainability_pct'=>96.0,'repeated_failure_count'=>0,'spend_exposure'=>(float)$supplier1['annual_spend'],'savings_retained'=>(float)($good['verification']['actual_recovery_value']??0),'evidence_note'=>'Thirty-day post-recovery review confirms stable service, receiving performance, and retained recovery value.','next_review_date'=>'2026-08-25','created_at'=>'2026-07-26 17:30:00','updated_at'=>'2026-07-26 17:30:00'],
        ['id'=>2,'review_number'=>'SPR-2026-0002','company_id'=>4,'supplier_id'=>4,'source_execution_id'=>null,'source_verification_id'=>null,'review_window_days'=>60,'period_start'=>'2026-05-28','period_end'=>'2026-07-26','owner_id'=>3,'reviewer_id'=>6,'status'=>'monitoring','recommendation'=>'probationary','risk_tier'=>'critical','approval_id'=>null,'baseline'=>$bad['baseline'],'current'=>$badCurrent,'targets'=>$bad['targets'],'overall_score'=>performance_score($badCurrent),'improvement_pct'=>-14.0,'sustainability_pct'=>58.0,'repeated_failure_count'=>3,'spend_exposure'=>(float)$supplier4['annual_spend'],'savings_retained'=>0,'evidence_note'=>'Delivery, fill rate, responsiveness, and quality evidence show repeated post-review deterioration.','next_review_date'=>'2026-08-25','created_at'=>'2026-07-26 18:00:00','updated_at'=>'2026-07-26 18:00:00'],
    ];
}

function performance_demo_seed_actions(): array
{
    return [
        ['id'=>1,'action_number'=>'CAP-2026-0001','company_id'=>4,'review_id'=>2,'supplier_id'=>4,'title'=>'Restore confirmed delivery schedule adherence','root_cause'=>'Supplier capacity commitments are not synchronized with purchase-order acknowledgement and production sequencing.','corrective_action'=>'Provide a capacity-backed shipment schedule, weekly exception review, and named escalation owner for every open order.','owner_id'=>3,'status'=>'in_progress','severity'=>'critical','due_date'=>'2026-08-05','target_metric'=>'on_time_delivery','target_value'=>90.0,'actual_value'=>72.0,'evidence_note'=>'Supplier recovery meeting completed; dated shipment schedule remains pending.','completed_at'=>null,'verified_at'=>null,'created_at'=>'2026-07-26 18:05:00','updated_at'=>'2026-07-26 18:05:00'],
        ['id'=>2,'action_number'=>'CAP-2026-0002','company_id'=>4,'review_id'=>2,'supplier_id'=>4,'title'=>'Reduce incoming defect rate','root_cause'=>'Containment inspection and final-test evidence are incomplete for legacy component lots.','corrective_action'=>'Implement lot-level inspection evidence, defect containment, and first-article approval before shipment.','owner_id'=>5,'status'=>'blocked','severity'=>'high','due_date'=>'2026-08-10','target_metric'=>'defect_rate_pct','target_value'=>1.5,'actual_value'=>5.2,'evidence_note'=>'Blocked pending supplier process-capability evidence.','completed_at'=>null,'verified_at'=>null,'created_at'=>'2026-07-26 18:10:00','updated_at'=>'2026-07-26 18:10:00'],
    ];
}

function performance_demo_seed_events(): array
{
    return [
        ['id'=>1,'review_id'=>2,'event_type'=>'metric_regression','metric'=>'on_time_delivery','previous_value'=>72.6,'current_value'=>68.0,'threshold_value'=>95.0,'severity'=>'critical','evidence_note'=>'On-time delivery fell below the prior scorecard and approved threshold.','created_by'=>1,'created_at'=>'2026-07-26 18:00:00'],
        ['id'=>2,'review_id'=>2,'event_type'=>'threshold_breach','metric'=>'defect_rate_pct','previous_value'=>4.0,'current_value'=>5.2,'threshold_value'=>1.0,'severity'=>'critical','evidence_note'=>'Defect rate exceeded the continuous-improvement threshold.','created_by'=>1,'created_at'=>'2026-07-26 18:01:00'],
    ];
}

function performance_tables_ready(): bool
{
    if(data_is_demo())return true;$pdo=production_database_connection();if(!$pdo)return false;try{$pdo->query('SELECT id FROM supplier_performance_reviews LIMIT 1');$pdo->query('SELECT id FROM supplier_corrective_action_plans LIMIT 1');$pdo->query('SELECT id FROM supplier_performance_events LIMIT 1');return true;}catch(Throwable){return false;}
}
function performance_decode_review(array $row): array {$row['baseline']=performance_json_array($row['baseline_json']??$row['baseline']??[]);$row['current']=performance_json_array($row['current_json']??$row['current']??[]);$row['targets']=performance_json_array($row['targets_json']??$row['targets']??[]);return $row;}

function performance_reviews(?int $supplierId=null): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_performance_reviews']))$_SESSION['gruber_demo_state']['supplier_performance_reviews']=performance_demo_seed_reviews();$rows=array_map('performance_decode_review',array_values($_SESSION['gruber_demo_state']['supplier_performance_reviews']));if($supplierId)$rows=array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['supplier_id']===$supplierId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));return $rows;}
    if(!performance_tables_ready())return [];$pdo=production_database_connection();$where=[];$params=[];if(current_company_id()!=='enterprise'){$where[]='company_id=?';$params[]=(int)current_company_id();}if($supplierId){$where[]='supplier_id=?';$params[]=$supplierId;}$stmt=$pdo->prepare('SELECT * FROM supplier_performance_reviews'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY updated_at DESC,id DESC');$stmt->execute($params);return array_map('performance_decode_review',$stmt->fetchAll());
}
function performance_find_review(int $id): ?array {foreach(performance_reviews() as $row)if((int)$row['id']===$id)return $row;return null;}
function performance_next_review_number(): string {return 'SPR-'.date('Y').'-'.str_pad((string)(count(performance_reviews())+1),4,'0',STR_PAD_LEFT);}

function performance_save_review(array $record): array
{
    $record['baseline']=performance_json_array($record['baseline']??$record['baseline_json']??[]);$record['current']=performance_json_array($record['current']??$record['current_json']??[]);$record['targets']=performance_json_array($record['targets']??$record['targets_json']??[]);$record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['review_number']=$record['review_number']??performance_next_review_number();
    if(data_is_demo()){$rows=performance_reviews();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$existing)if((int)$existing['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['supplier_performance_reviews']=$rows;return $record;}
    if(!performance_tables_ready())throw new RuntimeException('Import the Section 15 supplier-performance migration before saving Production Data reviews.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['review_number'],$record['company_id'],$record['supplier_id'],$record['source_execution_id'],$record['source_verification_id'],$record['review_window_days'],$record['period_start'],$record['period_end'],$record['owner_id'],$record['reviewer_id'],$record['status'],$record['recommendation'],$record['risk_tier'],$record['approval_id'],json_encode($record['baseline'],JSON_THROW_ON_ERROR),json_encode($record['current'],JSON_THROW_ON_ERROR),json_encode($record['targets'],JSON_THROW_ON_ERROR),$record['overall_score'],$record['improvement_pct'],$record['sustainability_pct'],$record['repeated_failure_count'],$record['spend_exposure'],$record['savings_retained'],$record['evidence_note'],$record['next_review_date'],$record['created_at'],$record['updated_at']];
    if($id>0){$stmt=$pdo->prepare('UPDATE supplier_performance_reviews SET review_number=?,company_id=?,supplier_id=?,source_execution_id=?,source_verification_id=?,review_window_days=?,period_start=?,period_end=?,owner_id=?,reviewer_id=?,status=?,recommendation=?,risk_tier=?,approval_id=?,baseline_json=?,current_json=?,targets_json=?,overall_score=?,improvement_pct=?,sustainability_pct=?,repeated_failure_count=?,spend_exposure=?,savings_retained=?,evidence_note=?,next_review_date=?,created_at=?,updated_at=? WHERE id=?');$stmt->execute([...$params,$id]);}else{$stmt=$pdo->prepare('INSERT INTO supplier_performance_reviews (review_number,company_id,supplier_id,source_execution_id,source_verification_id,review_window_days,period_start,period_end,owner_id,reviewer_id,status,recommendation,risk_tier,approval_id,baseline_json,current_json,targets_json,overall_score,improvement_pct,sustainability_pct,repeated_failure_count,spend_exposure,savings_retained,evidence_note,next_review_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}

function performance_actions(?int $reviewId=null): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_corrective_action_plans']))$_SESSION['gruber_demo_state']['supplier_corrective_action_plans']=performance_demo_seed_actions();$rows=array_values($_SESSION['gruber_demo_state']['supplier_corrective_action_plans']);if($reviewId)$rows=array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['review_id']===$reviewId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));return $rows;}
    if(!performance_tables_ready())return [];$pdo=production_database_connection();$where=[];$params=[];if(current_company_id()!=='enterprise'){$where[]='company_id=?';$params[]=(int)current_company_id();}if($reviewId){$where[]='review_id=?';$params[]=$reviewId;}$stmt=$pdo->prepare('SELECT * FROM supplier_corrective_action_plans'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY updated_at DESC,id DESC');$stmt->execute($params);return $stmt->fetchAll();
}
function performance_find_action(int $id): ?array {foreach(performance_actions() as $row)if((int)$row['id']===$id)return $row;return null;}
function performance_next_action_number(): string {return 'CAP-'.date('Y').'-'.str_pad((string)(count(performance_actions())+1),4,'0',STR_PAD_LEFT);}

function performance_save_action(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['action_number']=$record['action_number']??performance_next_action_number();
    if(data_is_demo()){$rows=performance_actions();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$existing)if((int)$existing['id']===$id){$rows[$i]=$record;$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['supplier_corrective_action_plans']=$rows;return $record;}
    if(!performance_tables_ready())throw new RuntimeException('Import the Section 15 supplier-performance migration before saving corrective actions.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['action_number'],$record['company_id'],$record['review_id'],$record['supplier_id'],$record['title'],$record['root_cause'],$record['corrective_action'],$record['owner_id'],$record['status'],$record['severity'],$record['due_date'],$record['target_metric'],$record['target_value'],$record['actual_value'],$record['evidence_note'],$record['completed_at'],$record['verified_at'],$record['created_at'],$record['updated_at']];
    if($id>0){$stmt=$pdo->prepare('UPDATE supplier_corrective_action_plans SET action_number=?,company_id=?,review_id=?,supplier_id=?,title=?,root_cause=?,corrective_action=?,owner_id=?,status=?,severity=?,due_date=?,target_metric=?,target_value=?,actual_value=?,evidence_note=?,completed_at=?,verified_at=?,created_at=?,updated_at=? WHERE id=?');$stmt->execute([...$params,$id]);}else{$stmt=$pdo->prepare('INSERT INTO supplier_corrective_action_plans (action_number,company_id,review_id,supplier_id,title,root_cause,corrective_action,owner_id,status,severity,due_date,target_metric,target_value,actual_value,evidence_note,completed_at,verified_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}

function performance_events(int $reviewId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_performance_events']))$_SESSION['gruber_demo_state']['supplier_performance_events']=performance_demo_seed_events();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['supplier_performance_events'],static fn(array $r):bool=>(int)$r['review_id']===$reviewId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;}
    if(!performance_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM supplier_performance_events WHERE review_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$reviewId]);return $stmt->fetchAll();
}

function performance_add_event(int $reviewId,string $type,?string $metric,?float $previous,?float $current,?float $threshold,string $severity,string $note): array
{
    if(!in_array($type,performance_event_types(),true))$type='review_updated';if(!in_array($severity,['low','medium','high','critical'],true))$severity='medium';$row=['review_id'=>$reviewId,'event_type'=>$type,'metric'=>$metric,'previous_value'=>$previous,'current_value'=>$current,'threshold_value'=>$threshold,'severity'=>$severity,'evidence_note'=>mb_substr(trim($note),0,5000),'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_performance_events']))$_SESSION['gruber_demo_state']['supplier_performance_events']=performance_demo_seed_events();$rows=$_SESSION['gruber_demo_state']['supplier_performance_events'];$row['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$row;$_SESSION['gruber_demo_state']['supplier_performance_events']=$rows;return $row;}
    if(!performance_tables_ready())throw new RuntimeException('Import the Section 15 supplier-performance migration before writing events.');$stmt=production_database_connection()->prepare('INSERT INTO supplier_performance_events (review_id,event_type,metric,previous_value,current_value,threshold_value,severity,evidence_note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');$stmt->execute([$row['review_id'],$row['event_type'],$row['metric'],$row['previous_value'],$row['current_value'],$row['threshold_value'],$row['severity'],$row['evidence_note'],$row['created_by'],$row['created_at']]);$row['id']=(int)production_database_connection()->lastInsertId();return $row;
}

function performance_effective_status(array $review): string
{
    $stored=(string)($review['status']??'draft');if(in_array($stored,['monitoring','closed'],true))return $stored;$approvalId=(int)($review['approval_id']??0);if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested'],true))return (string)$approval['status'];}return $stored;
}

function performance_close_readiness(array $review,array $actions): array
{
    $metrics=performance_metrics($review,$actions);$open=array_values(array_filter($actions,static fn(array $a):bool=>!in_array((string)($a['status']??''),['verified','cancelled'],true)));return ['ready'=>!$open&&$metrics['sustainability_pct']>=80&&!array_filter($metrics['regressions'],static fn(array $r):bool=>$r['severity']==='critical'),'open_actions'=>$open,'metrics'=>$metrics];
}
function performance_csv_cell(mixed $value): string {$value=(string)$value;if($value!==''&&preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value;}
function performance_export_csv(array $review,array $actions,array $events): never
{
    require_permission('reports.export');$metrics=performance_metrics($review,$actions);header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="supplier-performance-'.date('Ymd-His').'.csv"');$out=fopen('php://output','wb');foreach([['Review number',$review['review_number']],['Supplier',data_supplier_name((int)$review['supplier_id'])],['Status',performance_effective_status($review)],['Recommendation',$review['recommendation']],['Risk tier',$review['risk_tier']],['Review window',$review['review_window_days'].' days'],['Evidence',$review['evidence_note']]] as $row)fputcsv($out,array_map('performance_csv_cell',$row));fputcsv($out,[]);fputcsv($out,['Metric','Value']);foreach($metrics as $key=>$value)if(!is_array($value))fputcsv($out,array_map('performance_csv_cell',[status_label($key),$value]));fputcsv($out,[]);fputcsv($out,['Corrective action','Status','Severity','Owner','Due date','Target metric','Target','Actual','Evidence']);foreach($actions as $action)fputcsv($out,array_map('performance_csv_cell',[$action['title'],$action['status'],$action['severity'],$action['owner_id'],$action['due_date'],$action['target_metric'],$action['target_value'],$action['actual_value'],$action['evidence_note']]));fputcsv($out,[]);fputcsv($out,['Event','Metric','Previous','Current','Threshold','Severity','Evidence','Created at']);foreach($events as $event)fputcsv($out,array_map('performance_csv_cell',[$event['event_type'],$event['metric'],$event['previous_value'],$event['current_value'],$event['threshold_value'],$event['severity'],$event['evidence_note'],$event['created_at']]));fclose($out);exit;
}
