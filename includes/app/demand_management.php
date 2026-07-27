<?php
declare(strict_types=1);

require_once __DIR__ . '/contract_management.php';

function demand_request_statuses(): array { return ['draft','submitted','in_review','approved','rejected','converted','canceled']; }
function demand_business_purposes(): array { return ['inventory_replenishment','customer_order','manufacturing_order','service_work_order','construction_project','vehicle_repair_restoration','facility_expense','capital_investment']; }
function demand_urgencies(): array { return ['normal','urgent','critical']; }
function demand_capex_opex(): array { return ['operating_expense','capital_expense']; }
function demand_event_types(): array { return ['request_created','request_updated','line_created','line_updated','assessment_completed','submitted','approved','changes_requested','budget_exception','inventory_opportunity','consolidation_opportunity','converted_to_po','canceled']; }

function demand_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo=production_database_connection();
    if(!$pdo)return false;
    try{
        foreach(['procurement_request_governance_profiles','procurement_budget_envelopes','procurement_demand_forecasts','purchase_request_sourcing_assessments','purchase_request_events'] as $table){
            $pdo->query("SELECT id FROM {$table} LIMIT 1");
        }
        return true;
    }catch(Throwable){return false;}
}

function demand_decode_request(array $row): array
{
    $row['id']=(int)($row['id']??0);
    $row['company_id']=(int)($row['company_id']??0);
    $row['location_id']=isset($row['location_id'])&&$row['location_id']!==null?(int)$row['location_id']:null;
    $row['department_id']=isset($row['department_id'])&&$row['department_id']!==null?(int)$row['department_id']:null;
    $row['project_workorder_id']=isset($row['project_workorder_id'])&&$row['project_workorder_id']!==null?(int)$row['project_workorder_id']:null;
    $row['requested_by']=(int)($row['requested_by']??1);
    $row['owner_id']=(int)($row['owner_id']??$row['requested_by']);
    $row['reviewer_id']=(int)($row['reviewer_id']??6);
    $row['approval_id']=isset($row['approval_id'])&&$row['approval_id']!==null?(int)$row['approval_id']:null;
    $row['budget_envelope_id']=isset($row['budget_envelope_id'])&&$row['budget_envelope_id']!==null?(int)$row['budget_envelope_id']:null;
    $row['sourcing_assessment_id']=isset($row['sourcing_assessment_id'])&&$row['sourcing_assessment_id']!==null?(int)$row['sourcing_assessment_id']:null;
    $row['converted_po_id']=isset($row['converted_po_id'])&&$row['converted_po_id']!==null?(int)$row['converted_po_id']:null;
    $row['estimated_total']=(float)($row['estimated_total']??0);
    $row['capex_opex']=(string)($row['capex_opex']??'operating_expense');
    $row['unplanned_demand']=!empty($row['unplanned_demand']);
    $row['source_status']=(string)($row['source_status']??'not_assessed');
    $row['evidence_note']=(string)($row['evidence_note']??'Validate inventory, budget, contract coverage, supplier performance, required-date risk, duplicate demand, and sourcing alternatives.');
    $row['created_at']=(string)($row['created_at']??date('Y-m-d H:i:s'));
    $row['updated_at']=(string)($row['updated_at']??$row['created_at']);
    return $row;
}

function demand_demo_seed_requests(): array
{
    return [
        ['id'=>1,'request_number'=>'REQ-GPS-2026-001','company_id'=>2,'location_id'=>null,'department_id'=>null,'requested_by'=>3,'project_workorder_id'=>null,'business_purpose'=>'inventory_replenishment','required_date'=>'2026-08-12','urgency'=>'urgent','status'=>'submitted','justification'=>'Restore battery service stock before scheduled customer maintenance windows.','estimated_total'=>47400.0,'submitted_at'=>'2026-07-26 20:05:00','approved_at'=>null,'owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'budget_envelope_id'=>1,'sourcing_assessment_id'=>1,'converted_po_id'=>null,'capex_opex'=>'operating_expense','unplanned_demand'=>false,'source_status'=>'assessed','evidence_note'=>'Inventory, open-PO, contract, supplier performance, and required-date evidence reviewed.','created_at'=>'2026-07-26 20:00:00','updated_at'=>'2026-07-26 20:05:00'],
        ['id'=>2,'request_number'=>'REQ-GMC-2026-002','company_id'=>4,'location_id'=>null,'department_id'=>null,'requested_by'=>3,'project_workorder_id'=>null,'business_purpose'=>'vehicle_repair_restoration','required_date'=>'2026-08-05','urgency'=>'critical','status'=>'draft','justification'=>'Legacy controller demand for two restoration commitments requires sourcing review.','estimated_total'=>22500.0,'submitted_at'=>null,'approved_at'=>null,'owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'budget_envelope_id'=>2,'sourcing_assessment_id'=>2,'converted_po_id'=>null,'capex_opex'=>'operating_expense','unplanned_demand'=>true,'source_status'=>'exception','evidence_note'=>'Critical required date, probationary supplier performance, and off-contract exposure require intervention.','created_at'=>'2026-07-26 20:10:00','updated_at'=>'2026-07-26 20:10:00'],
    ];
}
function demand_demo_seed_lines(): array
{
    return [
        ['id'=>1,'purchase_request_id'=>1,'item_id'=>1,'requested_description'=>'12V 100Ah sealed UPS battery','quantity'=>250.0,'unit_of_measure'=>'EA','estimated_unit_cost'=>189.60,'preferred_supplier_id'=>1,'specification_notes'=>'Use approved sealed UPS battery specification.','created_at'=>'2026-07-26 20:00:00','updated_at'=>'2026-07-26 20:00:00'],
        ['id'=>2,'purchase_request_id'=>2,'item_id'=>4,'requested_description'=>'Legacy EV motor controller assembly','quantity'=>6.0,'unit_of_measure'=>'EA','estimated_unit_cost'=>3750.0,'preferred_supplier_id'=>4,'specification_notes'=>'Compatibility evidence required before commitment.','created_at'=>'2026-07-26 20:10:00','updated_at'=>'2026-07-26 20:10:00'],
    ];
}
function demand_demo_seed_budgets(): array
{
    return [
        ['id'=>1,'budget_number'=>'BUD-2026-0001','company_id'=>2,'department_id'=>null,'project_workorder_id'=>null,'category_id'=>1,'period_start'=>'2026-07-01','period_end'=>'2026-12-31','budget_amount'=>600000.0,'requested_amount'=>47400.0,'approved_amount'=>0.0,'committed_amount'=>94800.0,'actual_amount'=>94800.0,'owner_id'=>3,'status'=>'active','evidence_note'=>'Battery and energy-storage operating budget.','created_at'=>'2026-07-26 20:00:00','updated_at'=>'2026-07-26 20:00:00'],
        ['id'=>2,'budget_number'=>'BUD-2026-0002','company_id'=>4,'department_id'=>null,'project_workorder_id'=>null,'category_id'=>4,'period_start'=>'2026-07-01','period_end'=>'2026-12-31','budget_amount'=>250000.0,'requested_amount'=>22500.0,'approved_amount'=>0.0,'committed_amount'=>22500.0,'actual_amount'=>0.0,'owner_id'=>3,'status'=>'active','evidence_note'=>'Legacy component and restoration operating budget.','created_at'=>'2026-07-26 20:10:00','updated_at'=>'2026-07-26 20:10:00'],
    ];
}
function demand_demo_seed_forecasts(): array
{
    return [
        ['id'=>1,'forecast_number'=>'FCST-2026-0001','company_id'=>2,'category_id'=>1,'period_start'=>'2026-08-01','period_end'=>'2026-10-31','forecast_quantity'=>900.0,'forecast_value'=>171000.0,'confidence_pct'=>82.0,'source_note'=>'Service schedule, historical battery usage, and open customer commitments.','owner_id'=>3,'status'=>'active','created_at'=>'2026-07-26 20:00:00','updated_at'=>'2026-07-26 20:00:00'],
    ];
}
function demand_demo_seed_assessments(): array
{
    return [
        ['id'=>1,'assessment_number'=>'SRC-2026-0001','purchase_request_id'=>1,'company_id'=>2,'recommended_supplier_id'=>1,'recommended_action'=>'revise_quantity','inventory_avoidance_value'=>34447.20,'open_po_coverage_value'=>47400.0,'consolidation_value'=>0.0,'contract_covered_value'=>47400.0,'off_contract_exposure'=>0.0,'duplicate_request_count'=>0,'required_date_risk'=>'medium','budget_status'=>'within_budget','supplier_risk'=>'low','performance_score'=>94.0,'price_variance_pct'=>0.1,'assessment_score'=>88.0,'evidence_note'=>'Available stock and an open order can reduce or defer this request.','created_by'=>1,'created_at'=>'2026-07-26 20:05:00'],
        ['id'=>2,'assessment_number'=>'SRC-2026-0002','purchase_request_id'=>2,'company_id'=>4,'recommended_supplier_id'=>6,'recommended_action'=>'require_sourcing_review','inventory_avoidance_value'=>7500.0,'open_po_coverage_value'=>22500.0,'consolidation_value'=>0.0,'contract_covered_value'=>0.0,'off_contract_exposure'=>22500.0,'duplicate_request_count'=>1,'required_date_risk'=>'critical','budget_status'=>'within_budget','supplier_risk'=>'critical','performance_score'=>58.0,'price_variance_pct'=>0.0,'assessment_score'=>42.0,'evidence_note'=>'Use alternate sourcing and executive review before commitment.','created_by'=>1,'created_at'=>'2026-07-26 20:12:00'],
    ];
}
function demand_demo_seed_events(): array
{
    return [
        ['id'=>1,'purchase_request_id'=>1,'event_type'=>'assessment_completed','from_status'=>'draft','to_status'=>'submitted','severity'=>'medium','evidence_note'=>'Inventory and open-PO coverage identified before approval.','created_by'=>1,'created_at'=>'2026-07-26 20:05:00'],
        ['id'=>2,'purchase_request_id'=>2,'event_type'=>'budget_exception','from_status'=>'draft','to_status'=>'draft','severity'=>'critical','evidence_note'=>'Critical demand is off contract and linked to a probationary supplier.','created_by'=>1,'created_at'=>'2026-07-26 20:12:00'],
    ];
}

function demand_requests(): array
{
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['purchase_requests']))$_SESSION['gruber_demo_state']['purchase_requests']=demand_demo_seed_requests();
        $rows=array_map('demand_decode_request',array_values($_SESSION['gruber_demo_state']['purchase_requests']));
        $rows=array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));
        usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));
        return $rows;
    }
    $pdo=production_database_connection();if(!$pdo)return [];
    $ready=demand_tables_ready();
    $select=$ready?',gp.owner_id,gp.reviewer_id,gp.approval_id,gp.budget_envelope_id,gp.sourcing_assessment_id,gp.converted_po_id,gp.capex_opex,gp.unplanned_demand,gp.source_status,gp.evidence_note':'';
    $join=$ready?' LEFT JOIN procurement_request_governance_profiles gp ON gp.purchase_request_id=pr.id':'';
    $where=[];$params=[];if(current_company_id()!=='enterprise'){$where[]='pr.company_id=?';$params[]=(int)current_company_id();}
    $stmt=$pdo->prepare('SELECT pr.*'.$select.' FROM purchase_requests pr'.$join.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY pr.updated_at DESC,pr.id DESC');
    $stmt->execute($params);return array_map('demand_decode_request',$stmt->fetchAll());
}
function demand_find_request(int $id): ?array { foreach(demand_requests() as $row)if((int)$row['id']===$id)return $row;return null; }
function demand_default_request(): ?array { $rows=demand_requests();foreach($rows as $row)if(in_array($row['status'],['submitted','draft','in_review'],true))return $row;return $rows[0]??null; }

function demand_request_lines(int $requestId): array
{
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['purchase_request_lines']))$_SESSION['gruber_demo_state']['purchase_request_lines']=demand_demo_seed_lines();
        return array_values(array_filter($_SESSION['gruber_demo_state']['purchase_request_lines'],static fn(array $row):bool=>(int)$row['purchase_request_id']===$requestId));
    }
    $pdo=production_database_connection();if(!$pdo)return [];$stmt=$pdo->prepare('SELECT * FROM purchase_request_lines WHERE purchase_request_id=? ORDER BY id');$stmt->execute([$requestId]);return $stmt->fetchAll();
}
function demand_find_line(int $id): ?array { foreach(demand_requests() as $request)foreach(demand_request_lines((int)$request['id']) as $line)if((int)$line['id']===$id)return $line;return null; }

function demand_budgets(): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_budget_envelopes']))$_SESSION['gruber_demo_state']['procurement_budget_envelopes']=demand_demo_seed_budgets();$rows=array_values($_SESSION['gruber_demo_state']['procurement_budget_envelopes']);return array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));}
    if(!demand_tables_ready())return [];$pdo=production_database_connection();$where=current_company_id()==='enterprise'?'':' WHERE company_id=?';$stmt=$pdo->prepare('SELECT * FROM procurement_budget_envelopes'.$where.' ORDER BY period_end,id');$stmt->execute(current_company_id()==='enterprise'?[]:[(int)current_company_id()]);return $stmt->fetchAll();
}
function demand_find_budget(int $id): ?array { foreach(demand_budgets() as $row)if((int)$row['id']===$id)return $row;return null; }
function demand_forecasts(): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_demand_forecasts']))$_SESSION['gruber_demo_state']['procurement_demand_forecasts']=demand_demo_seed_forecasts();$rows=array_values($_SESSION['gruber_demo_state']['procurement_demand_forecasts']);return array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));}
    if(!demand_tables_ready())return [];$pdo=production_database_connection();$where=current_company_id()==='enterprise'?'':' WHERE company_id=?';$stmt=$pdo->prepare('SELECT * FROM procurement_demand_forecasts'.$where.' ORDER BY period_start,id');$stmt->execute(current_company_id()==='enterprise'?[]:[(int)current_company_id()]);return $stmt->fetchAll();
}
function demand_assessments(int $requestId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['purchase_request_sourcing_assessments']))$_SESSION['gruber_demo_state']['purchase_request_sourcing_assessments']=demand_demo_seed_assessments();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['purchase_request_sourcing_assessments'],static fn(array $row):bool=>(int)$row['purchase_request_id']===$requestId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;}
    if(!demand_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM purchase_request_sourcing_assessments WHERE purchase_request_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$requestId]);return $stmt->fetchAll();
}
function demand_find_assessment(int $id): ?array { foreach(demand_requests() as $request)foreach(demand_assessments((int)$request['id']) as $row)if((int)$row['id']===$id)return $row;return null; }
function demand_events(int $requestId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['purchase_request_events']))$_SESSION['gruber_demo_state']['purchase_request_events']=demand_demo_seed_events();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['purchase_request_events'],static fn(array $row):bool=>(int)$row['purchase_request_id']===$requestId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;}
    if(!demand_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM purchase_request_events WHERE purchase_request_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$requestId]);return $stmt->fetchAll();
}

function demand_line_total(array $line): float { return round(max(0,(float)($line['quantity']??0))*max(0,(float)($line['estimated_unit_cost']??0)),2); }
function demand_request_total(array $lines): float { return round(array_sum(array_map('demand_line_total',$lines)),2); }

function demand_inventory_quantity(array $request,array $line): float
{
    $itemId=(int)($line['item_id']??0);if($itemId<=0)return 0.0;$sum=0.0;
    foreach(data_visible_collection('inventory_snapshots') as $row){
        if((int)($row['company_id']??0)!==(int)$request['company_id']||(int)($row['item_id']??0)!==$itemId)continue;
        $sum+=(float)($row['available']??$row['quantity_on_hand']??$row['quantity']??0);
    }
    return max(0,$sum);
}
function demand_open_po_quantity(array $request,array $line): float
{
    $itemId=(int)($line['item_id']??0);if($itemId<=0)return 0.0;$sum=0.0;
    foreach(data_visible_collection('purchase_orders') as $po){
        if((int)($po['company_id']??0)!==(int)$request['company_id']||!in_array((string)($po['status']??''),['open','past_due','partially_received','pending_approval'],true))continue;
        foreach(data_collection('purchase_order_lines') as $poLine){
            if((int)($poLine['purchase_order_id']??0)!==(int)$po['id']||(int)($poLine['item_id']??0)!==$itemId)continue;
            $ordered=(float)($poLine['quantity_ordered']??$poLine['quantity']??0);$received=(float)($poLine['quantity_received']??0);$sum+=max(0,$ordered-$received);
        }
    }
    return $sum;
}
function demand_contract_for_supplier(int $supplierId,int $companyId): ?array
{
    if($supplierId<=0)return null;
    foreach(contract_records($supplierId) as $contract){
        if($contract['company_id']!==null&&(int)$contract['company_id']!==$companyId)continue;
        if(!in_array((string)$contract['status'],['active','expiring'],true))continue;
        if(!empty($contract['end_date'])&&(string)$contract['end_date']<date('Y-m-d'))continue;
        return $contract;
    }
    return null;
}
function demand_duplicate_count(array $request,array $lines): int
{
    $itemIds=array_values(array_filter(array_map(static fn(array $line):int=>(int)($line['item_id']??0),$lines)));if(!$itemIds)return 0;$count=0;
    foreach(demand_requests() as $candidate){
        if((int)$candidate['id']===(int)$request['id']||in_array((string)$candidate['status'],['rejected','converted','canceled'],true))continue;
        foreach(demand_request_lines((int)$candidate['id']) as $line){if(in_array((int)($line['item_id']??0),$itemIds,true)){$count++;break;}}
    }
    return $count;
}
function demand_budget_for_request(array $request,array $lines): ?array
{
    $category=0;foreach($lines as $line){$item=!empty($line['item_id'])?data_find('items',(int)$line['item_id']):null;if($item){$category=(int)($item['category_id']??0);break;}}
    foreach(demand_budgets() as $budget){
        if((int)$budget['company_id']!==(int)$request['company_id'])continue;
        if(!empty($budget['department_id'])&&(int)$budget['department_id']!==(int)($request['department_id']??0))continue;
        if(!empty($budget['project_workorder_id'])&&(int)$budget['project_workorder_id']!==(int)($request['project_workorder_id']??0))continue;
        if(!empty($budget['category_id'])&&$category>0&&(int)$budget['category_id']!==$category)continue;
        if(!empty($request['required_date'])&&((string)$request['required_date']<(string)$budget['period_start']||(string)$request['required_date']>(string)$budget['period_end']))continue;
        return $budget;
    }
    return null;
}

function demand_assessment_blueprint(array $request,array $lines): array
{
    if(!$lines)throw new RuntimeException('Add at least one request line before sourcing assessment.');
    $requested=demand_request_total($lines);$inventory=0.0;$openPo=0.0;$covered=0.0;$offContract=0.0;$recommendedSupplier=0;$performanceScores=[];$supplierRisk='medium';$priceVariance=[];
    foreach($lines as $line){
        $lineValue=demand_line_total($line);$qty=max(0,(float)$line['quantity']);$unit=max(0,(float)$line['estimated_unit_cost']);
        $inventory+=min($qty,demand_inventory_quantity($request,$line))*$unit;
        $openPo+=min($qty,demand_open_po_quantity($request,$line))*$unit;
        $supplierId=(int)($line['preferred_supplier_id']??0);
        if($supplierId>0&&$recommendedSupplier===0)$recommendedSupplier=$supplierId;
        $contract=demand_contract_for_supplier($supplierId,(int)$request['company_id']);
        if($contract)$covered+=$lineValue;else$offContract+=$lineValue;
        $performance=$supplierId>0?contract_latest_performance($supplierId):null;
        if($performance){$pm=performance_metrics($performance,performance_actions((int)$performance['id']));$performanceScores[]=(float)$pm['current_score'];$supplierRisk=(string)$pm['risk_tier'];}
        $historical=[];
        foreach(data_visible_collection('purchase_orders') as $po){
            if((int)($po['company_id']??0)!==(int)$request['company_id'])continue;
            foreach(data_collection('purchase_order_lines') as $poLine){
                if((int)($poLine['purchase_order_id']??0)!==(int)$po['id']||(int)($poLine['item_id']??0)!==(int)($line['item_id']??0))continue;
                $historical[]=(float)($poLine['unit_cost']??0);
            }
        }
        if($historical&&$unit>0){$last=end($historical);$priceVariance[]=(($unit-$last)/max(.01,$last))*100;}
    }
    $budget=demand_budget_for_request($request,$lines);$budgetRemaining=$budget?(float)$budget['budget_amount']-(float)$budget['approved_amount']-(float)$budget['committed_amount']:0.0;$budgetStatus=$budget===null?'no_budget':($requested>$budgetRemaining?'over_budget':'within_budget');
    $days=!empty($request['required_date'])?(int)floor((strtotime((string)$request['required_date'])-strtotime(date('Y-m-d')))/86400):999;
    $dateRisk=$days<7?'critical':($days<14?'high':($days<30?'medium':'low'));
    $duplicates=demand_duplicate_count($request,$lines);$consolidation=$duplicates>0?round($requested*.05,2):0.0;
    $performanceScore=$performanceScores?round(array_sum($performanceScores)/count($performanceScores),1):70.0;
    $priceVar=$priceVariance?round(array_sum($priceVariance)/count($priceVariance),1):0.0;
    $score=100.0;$score-=min(35,($offContract/max(1,$requested))*35);$score-=$budgetStatus==='over_budget'?25:($budgetStatus==='no_budget'?10:0);$score-=in_array($dateRisk,['high','critical'],true)?15:0;$score-=min(15,$duplicates*5);$score-=max(0,(80-$performanceScore)*.5);$score=max(0,min(100,round($score,1)));
    $action='approve';
    if($inventory+$openPo>=$requested*.75)$action='revise_quantity';
    if($duplicates>0)$action='consolidate';
    if($budgetStatus==='over_budget'||$offContract>0||in_array($supplierRisk,['high','critical'],true)||$dateRisk==='critical')$action='require_sourcing_review';
    if($recommendedSupplier===0){foreach(data_visible_collection('suppliers') as $supplier){if(in_array((string)$supplier['status'],['preferred','approved'],true)&&!in_array((string)$supplier['risk'],['critical'],true)){$recommendedSupplier=(int)$supplier['id'];break;}}}
    return ['requested_value'=>$requested,'inventory_avoidance_value'=>round(min($requested,$inventory),2),'open_po_coverage_value'=>round(min($requested,$openPo),2),'consolidation_value'=>$consolidation,'contract_covered_value'=>round($covered,2),'off_contract_exposure'=>round($offContract,2),'duplicate_request_count'=>$duplicates,'required_date_risk'=>$dateRisk,'budget_status'=>$budgetStatus,'budget'=>$budget,'budget_remaining'=>round($budgetRemaining,2),'supplier_risk'=>$supplierRisk,'performance_score'=>$performanceScore,'price_variance_pct'=>$priceVar,'assessment_score'=>$score,'recommended_supplier_id'=>$recommendedSupplier,'recommended_action'=>$action];
}
function demand_metrics(array $request,array $lines,array $assessment=[]): array
{
    $requested=demand_request_total($lines);$budget=demand_budget_for_request($request,$lines);$budgetAmount=(float)($budget['budget_amount']??0);$used=(float)($budget['approved_amount']??0)+(float)($budget['committed_amount']??0);$remaining=$budgetAmount-$used;
    return ['requested_value'=>$requested,'line_count'=>count($lines),'budget_amount'=>$budgetAmount,'budget_used'=>$used,'budget_remaining'=>round($remaining,2),'budget_utilization_pct'=>$budgetAmount>0?round(($used/$budgetAmount)*100,1):0.0,'inventory_avoidance_value'=>(float)($assessment['inventory_avoidance_value']??0),'open_po_coverage_value'=>(float)($assessment['open_po_coverage_value']??0),'consolidation_value'=>(float)($assessment['consolidation_value']??0),'off_contract_exposure'=>(float)($assessment['off_contract_exposure']??0),'contract_coverage_pct'=>$requested>0?round(((float)($assessment['contract_covered_value']??0)/$requested)*100,1):0.0,'required_date_risk'=>(string)($assessment['required_date_risk']??'not_assessed'),'assessment_score'=>(float)($assessment['assessment_score']??0),'recommended_action'=>(string)($assessment['recommended_action']??'assess'),'forecast_cash_requirement'=>$requested,'unplanned_demand'=>!empty($request['unplanned_demand'])];
}
function demand_requires_approval(array $request,array $metrics): bool
{
    return $metrics['requested_value']>=50000||$metrics['off_contract_exposure']>0||$metrics['budget_remaining']<$metrics['requested_value']||($request['urgency']??'normal')==='critical'||in_array((string)$metrics['required_date_risk'],['high','critical'],true);
}
function demand_effective_status(array $request): string
{
    $stored=(string)($request['status']??'draft');if(in_array($stored,['converted','rejected','canceled'],true))return $stored;$approvalId=(int)($request['approval_id']??0);
    if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested','rejected'],true))return (string)$approval['status'];}
    return $stored;
}
function demand_next_number(string $prefix,string $sessionKey,string $table,array $seed): string
{
    if(data_is_demo())$count=count($_SESSION['gruber_demo_state'][$sessionKey]??$seed);
    else{$count=demand_tables_ready()?(int)production_database_connection()->query('SELECT COUNT(*) FROM '.$table)->fetchColumn():0;}
    return $prefix.'-'.date('Y').'-'.str_pad((string)($count+1),4,'0',STR_PAD_LEFT);
}

function demand_save_request(array $record): array
{
    $record=demand_decode_request($record);$record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];
    if(data_is_demo()){
        $rows=demand_requests();$id=(int)$record['id'];if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['id']===$id){$rows[$i]=array_replace($row,$record);$record=$rows[$i];$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['purchase_requests']=$rows;return $record;
    }
    if(!demand_tables_ready())throw new RuntimeException('Import the Section 17 migration before saving Production Data purchase requests.');
    $pdo=production_database_connection();$id=(int)$record['id'];
    $params=[$record['request_number'],$record['company_id'],$record['location_id'],$record['department_id'],$record['requested_by'],$record['project_workorder_id'],$record['business_purpose'],$record['required_date']?:null,$record['urgency'],$record['status'],$record['justification'],$record['estimated_total'],$record['submitted_at']??null,$record['approved_at']??null];
    if($id>0){$pdo->prepare('UPDATE purchase_requests SET request_number=?,company_id=?,location_id=?,department_id=?,requested_by=?,project_workorder_id=?,business_purpose=?,required_date=?,urgency=?,status=?,justification=?,estimated_total=?,submitted_at=?,approved_at=?,updated_at=NOW() WHERE id=?')->execute([...$params,$id]);}
    else{$pdo->prepare('INSERT INTO purchase_requests (request_number,company_id,location_id,department_id,requested_by,project_workorder_id,business_purpose,required_date,urgency,status,justification,estimated_total,submitted_at,approved_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}
    $pdo->prepare('INSERT INTO procurement_request_governance_profiles (purchase_request_id,owner_id,reviewer_id,approval_id,budget_envelope_id,sourcing_assessment_id,converted_po_id,capex_opex,unplanned_demand,source_status,evidence_note,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE owner_id=VALUES(owner_id),reviewer_id=VALUES(reviewer_id),approval_id=VALUES(approval_id),budget_envelope_id=VALUES(budget_envelope_id),sourcing_assessment_id=VALUES(sourcing_assessment_id),converted_po_id=VALUES(converted_po_id),capex_opex=VALUES(capex_opex),unplanned_demand=VALUES(unplanned_demand),source_status=VALUES(source_status),evidence_note=VALUES(evidence_note),updated_at=NOW()')->execute([$id,$record['owner_id'],$record['reviewer_id'],$record['approval_id'],$record['budget_envelope_id'],$record['sourcing_assessment_id'],$record['converted_po_id'],$record['capex_opex'],$record['unplanned_demand']?1:0,$record['source_status'],$record['evidence_note']]);
    $record['id']=$id;return demand_find_request($id)??$record;
}
function demand_save_line(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];
    if(data_is_demo()){
        if(!isset($_SESSION['gruber_demo_state']['purchase_request_lines']))$_SESSION['gruber_demo_state']['purchase_request_lines']=demand_demo_seed_lines();$rows=$_SESSION['gruber_demo_state']['purchase_request_lines'];$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['id']===$id){$rows[$i]=array_replace($row,$record);$record=$rows[$i];$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['purchase_request_lines']=$rows;return $record;
    }
    if(!demand_tables_ready())throw new RuntimeException('Import the Section 17 migration before saving request lines.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['purchase_request_id'],$record['item_id']?:null,$record['requested_description'],$record['quantity'],$record['unit_of_measure'],$record['estimated_unit_cost'],$record['preferred_supplier_id']?:null,$record['specification_notes']];
    if($id>0){$pdo->prepare('UPDATE purchase_request_lines SET purchase_request_id=?,item_id=?,requested_description=?,quantity=?,unit_of_measure=?,estimated_unit_cost=?,preferred_supplier_id=?,specification_notes=?,updated_at=NOW() WHERE id=?')->execute([...$params,$id]);}
    else{$pdo->prepare('INSERT INTO purchase_request_lines (purchase_request_id,item_id,requested_description,quantity,unit_of_measure,estimated_unit_cost,preferred_supplier_id,specification_notes) VALUES (?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}
function demand_save_budget(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['budget_number']=$record['budget_number']??demand_next_number('BUD','procurement_budget_envelopes','procurement_budget_envelopes',demand_demo_seed_budgets());
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['procurement_budget_envelopes']??demand_demo_seed_budgets();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['id']===$id){$rows[$i]=array_replace($row,$record);$record=$rows[$i];$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['procurement_budget_envelopes']=$rows;return $record;}
    if(!demand_tables_ready())throw new RuntimeException('Import the Section 17 migration before saving budgets.');$pdo=production_database_connection();$id=(int)($record['id']??0);$params=[$record['budget_number'],$record['company_id'],$record['department_id'],$record['project_workorder_id'],$record['category_id'],$record['period_start'],$record['period_end'],$record['budget_amount'],$record['requested_amount'],$record['approved_amount'],$record['committed_amount'],$record['actual_amount'],$record['owner_id'],$record['status'],$record['evidence_note']];
    if($id>0){$pdo->prepare('UPDATE procurement_budget_envelopes SET budget_number=?,company_id=?,department_id=?,project_workorder_id=?,category_id=?,period_start=?,period_end=?,budget_amount=?,requested_amount=?,approved_amount=?,committed_amount=?,actual_amount=?,owner_id=?,status=?,evidence_note=?,updated_at=NOW() WHERE id=?')->execute([...$params,$id]);}else{$pdo->prepare('INSERT INTO procurement_budget_envelopes (budget_number,company_id,department_id,project_workorder_id,category_id,period_start,period_end,budget_amount,requested_amount,approved_amount,committed_amount,actual_amount,owner_id,status,evidence_note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($params);$id=(int)$pdo->lastInsertId();}$record['id']=$id;return $record;
}
function demand_save_forecast(array $record): array
{
    $record['updated_at']=date('Y-m-d H:i:s');$record['created_at']=$record['created_at']??$record['updated_at'];$record['forecast_number']=$record['forecast_number']??demand_next_number('FCST','procurement_demand_forecasts','procurement_demand_forecasts',demand_demo_seed_forecasts());
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['procurement_demand_forecasts']??demand_demo_seed_forecasts();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$rows[]=$record;$_SESSION['gruber_demo_state']['procurement_demand_forecasts']=$rows;return $record;}
    if(!demand_tables_ready())throw new RuntimeException('Import the Section 17 migration before saving forecasts.');$pdo=production_database_connection();$pdo->prepare('INSERT INTO procurement_demand_forecasts (forecast_number,company_id,category_id,period_start,period_end,forecast_quantity,forecast_value,confidence_pct,source_note,owner_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$record['forecast_number'],$record['company_id'],$record['category_id'],$record['period_start'],$record['period_end'],$record['forecast_quantity'],$record['forecast_value'],$record['confidence_pct'],$record['source_note'],$record['owner_id'],$record['status']]);$record['id']=(int)$pdo->lastInsertId();return $record;
}
function demand_save_assessment(array $record): array
{
    $record['assessment_number']=$record['assessment_number']??demand_next_number('SRC','purchase_request_sourcing_assessments','purchase_request_sourcing_assessments',demand_demo_seed_assessments());$record['created_at']=$record['created_at']??date('Y-m-d H:i:s');
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['purchase_request_sourcing_assessments']??demand_demo_seed_assessments();$record['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$record;$_SESSION['gruber_demo_state']['purchase_request_sourcing_assessments']=$rows;return $record;}
    if(!demand_tables_ready())throw new RuntimeException('Import the Section 17 migration before saving sourcing assessments.');$pdo=production_database_connection();$pdo->prepare('INSERT INTO purchase_request_sourcing_assessments (assessment_number,purchase_request_id,company_id,recommended_supplier_id,recommended_action,inventory_avoidance_value,open_po_coverage_value,consolidation_value,contract_covered_value,off_contract_exposure,duplicate_request_count,required_date_risk,budget_status,supplier_risk,performance_score,price_variance_pct,assessment_score,evidence_note,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$record['assessment_number'],$record['purchase_request_id'],$record['company_id'],$record['recommended_supplier_id'],$record['recommended_action'],$record['inventory_avoidance_value'],$record['open_po_coverage_value'],$record['consolidation_value'],$record['contract_covered_value'],$record['off_contract_exposure'],$record['duplicate_request_count'],$record['required_date_risk'],$record['budget_status'],$record['supplier_risk'],$record['performance_score'],$record['price_variance_pct'],$record['assessment_score'],$record['evidence_note'],$record['created_by']]);$record['id']=(int)$pdo->lastInsertId();return $record;
}
function demand_add_event(int $requestId,string $type,?string $from,?string $to,string $severity,string $note): array
{
    if(!in_array($type,demand_event_types(),true))$type='request_updated';if(!in_array($severity,['low','medium','high','critical'],true))$severity='medium';$row=['purchase_request_id'=>$requestId,'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'severity'=>$severity,'evidence_note'=>mb_substr(trim($note),0,5000),'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['purchase_request_events']??demand_demo_seed_events();$row['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$row;$_SESSION['gruber_demo_state']['purchase_request_events']=$rows;return $row;}
    if(!demand_tables_ready())throw new RuntimeException('Import the Section 17 migration before saving request events.');$pdo=production_database_connection();$pdo->prepare('INSERT INTO purchase_request_events (purchase_request_id,event_type,from_status,to_status,severity,evidence_note,created_by) VALUES (?,?,?,?,?,?,?)')->execute([$requestId,$type,$from,$to,$severity,$row['evidence_note'],$row['created_by']]);$row['id']=(int)$pdo->lastInsertId();return $row;
}
function demand_notify(int $userId,int $companyId,string $title,string $message,string $severity='info'): void
{
    data_upsert('notifications',['id'=>null,'company_id'=>$companyId,'user_id'=>$userId,'title'=>$title,'message'=>$message,'severity'=>$severity,'read'=>false,'created_at'=>date('Y-m-d H:i:s')]);
}
function demand_convert_to_po(array $request,array $assessment,array $lines): array
{
    if(demand_effective_status($request)!=='approved')throw new RuntimeException('Approve the purchase request before conversion.');
    if(!$lines)throw new RuntimeException('At least one request line is required.');
    $supplierId=(int)($assessment['recommended_supplier_id']??0);if($supplierId<=0)throw new RuntimeException('A recommended supplier is required before conversion.');
    $poNumber='PO-REQ-'.str_pad((string)$request['id'],5,'0',STR_PAD_LEFT);$total=demand_request_total($lines);$today=date('Y-m-d');$expected=$request['required_date']?:date('Y-m-d',strtotime('+14 days'));
    if(data_is_demo()){
        $po=data_upsert('purchase_orders',['id'=>null,'po_number'=>$poNumber,'company_id'=>$request['company_id'],'supplier_id'=>$supplierId,'order_date'=>$today,'required_date'=>$request['required_date'],'expected_date'=>$expected,'status'=>'open','total_amount'=>$total,'buyer_id'=>$request['owner_id'],'review_status'=>'approved','purchase_request_id'=>$request['id'],'business_purpose'=>$request['business_purpose']]);
        foreach($lines as $line)data_upsert('purchase_order_lines',['id'=>null,'purchase_order_id'=>$po['id'],'item_id'=>$line['item_id'],'description'=>$line['requested_description'],'quantity'=>$line['quantity'],'unit_cost'=>$line['estimated_unit_cost'],'line_total'=>demand_line_total($line)]);
    }else{
        $pdo=production_database_connection();if(!$pdo)throw new RuntimeException('Production database unavailable.');$pdo->beginTransaction();
        try{
            $pdo->prepare('INSERT INTO purchase_orders (po_number,company_id,location_id,department_id,supplier_id,buyer_user_id,purchase_request_id,project_workorder_id,business_purpose,order_date,required_date,expected_date,status,currency_code,subtotal,total_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,"USD",?,?)')->execute([$poNumber,$request['company_id'],$request['location_id'],$request['department_id'],$supplierId,$request['owner_id'],$request['id'],$request['project_workorder_id'],$request['business_purpose'],$today,$request['required_date'],$expected,'open',$total,$total]);$poId=(int)$pdo->lastInsertId();$stmt=$pdo->prepare('INSERT INTO purchase_order_lines (purchase_order_id,line_number,item_id,description,category_id,quantity_ordered,quantity_received,unit_of_measure,unit_cost,standard_cost_at_order,required_date,expected_date,status) VALUES (?,?,?,?,?,?,0,?,?,?,?,?,"open")');$lineNo=1;foreach($lines as $line){$item=!empty($line['item_id'])?data_find('items',(int)$line['item_id']):null;$stmt->execute([$poId,$lineNo++,$line['item_id']?:null,$line['requested_description'],$item['category_id']??null,$line['quantity'],$line['unit_of_measure'],$line['estimated_unit_cost'],$item['standard_cost']??null,$request['required_date'],$expected]);}$pdo->commit();$po=['id'=>$poId,'po_number'=>$poNumber,'total_amount'=>$total];
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    $request['status']='converted';$request['converted_po_id']=(int)$po['id'];$request['estimated_total']=$total;demand_save_request($request);demand_add_event((int)$request['id'],'converted_to_po','approved','converted','medium','Converted to '.$poNumber.' with permanent request traceability.');return $po;
}
function demand_csv_cell(string $value): string { return preg_match('/^[=+\-@]/',$value)?"'".$value:$value; }
function demand_export_csv(array $request,array $lines,array $assessment,array $metrics,array $events): never
{
    require_permission('reports.export');$name='purchase-request-'.preg_replace('/[^A-Za-z0-9_-]+/','-',(string)$request['request_number']).'.csv';header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$name.'"');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Section','Field','Value']);foreach(['request_number','business_purpose','required_date','urgency','status','justification','estimated_total','capex_opex','unplanned_demand','evidence_note'] as $key)fputcsv($out,['Request',$key,demand_csv_cell((string)($request[$key]??''))]);foreach($metrics as $key=>$value)if(!is_array($value))fputcsv($out,['Metric',$key,demand_csv_cell((string)$value)]);foreach($lines as $line)fputcsv($out,['Line',$line['requested_description'],demand_csv_cell((string)demand_line_total($line))]);foreach($assessment as $key=>$value)if(!is_array($value))fputcsv($out,['Assessment',$key,demand_csv_cell((string)$value)]);foreach($events as $event)fputcsv($out,['Event',$event['event_type'],demand_csv_cell((string)$event['evidence_note'])]);fclose($out);exit;
}
