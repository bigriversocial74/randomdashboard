<?php
declare(strict_types=1);

function scenario_default_inputs(): array
{
    return [
        'scenario_type'=>'compound',
        'supplier_id'=>1,
        'category_id'=>1,
        'price_change_pct'=>8.0,
        'demand_change_pct'=>15.0,
        'lead_time_delay_days'=>14,
        'disruption_pct'=>25.0,
        'savings_offset_pct'=>20.0,
        'transfer_recovery_pct'=>30.0,
    ];
}

function scenario_normalize_inputs(array $input): array
{
    $defaults = scenario_default_inputs();
    $types = ['price_increase','lead_time_delay','supply_disruption','demand_spike','compound'];
    $type = (string)($input['scenario_type'] ?? $defaults['scenario_type']);
    if (!in_array($type,$types,true)) $type = 'compound';
    return [
        'scenario_type'=>$type,
        'supplier_id'=>max(0,(int)($input['supplier_id'] ?? $defaults['supplier_id'])),
        'category_id'=>max(0,(int)($input['category_id'] ?? $defaults['category_id'])),
        'price_change_pct'=>max(-50,min(250,(float)($input['price_change_pct'] ?? $defaults['price_change_pct']))),
        'demand_change_pct'=>max(-80,min(300,(float)($input['demand_change_pct'] ?? $defaults['demand_change_pct']))),
        'lead_time_delay_days'=>max(0,min(365,(int)($input['lead_time_delay_days'] ?? $defaults['lead_time_delay_days']))),
        'disruption_pct'=>max(0,min(100,(float)($input['disruption_pct'] ?? $defaults['disruption_pct']))),
        'savings_offset_pct'=>max(0,min(100,(float)($input['savings_offset_pct'] ?? $defaults['savings_offset_pct']))),
        'transfer_recovery_pct'=>max(0,min(100,(float)($input['transfer_recovery_pct'] ?? $defaults['transfer_recovery_pct']))),
    ];
}

function scenario_scope_supplier(array $inputs): ?array
{
    $supplierId = (int)$inputs['supplier_id'];
    if ($supplierId <= 0) return null;
    $supplier = data_find('suppliers',$supplierId);
    return $supplier && data_record_visible($supplier) ? $supplier : null;
}

function scenario_category_id(array $inputs, ?array $supplier): int
{
    $requested = (int)$inputs['category_id'];
    return $requested > 0 ? $requested : (int)($supplier['category_id'] ?? 0);
}

function scenario_affected_purchase_orders(?array $supplier, int $categoryId): array
{
    $orders = data_visible_collection('purchase_orders');
    $lines = data_collection('purchase_order_lines');
    $items = array_fill_keys(array_map('intval', array_column(array_filter(data_visible_collection('items'), static fn(array $item): bool => $categoryId <= 0 || (int)($item['category_id'] ?? 0) === $categoryId),'id')),true);
    $poIdsByCategory = [];
    foreach ($lines as $line) if (isset($items[(int)($line['item_id'] ?? 0)])) $poIdsByCategory[(int)($line['purchase_order_id'] ?? 0)] = true;
    return array_values(array_filter($orders, static function(array $po) use ($supplier,$poIdsByCategory): bool {
        if ($supplier && (int)($po['supplier_id'] ?? 0) === (int)$supplier['id']) return true;
        return isset($poIdsByCategory[(int)($po['id'] ?? 0)]);
    }));
}

function scenario_affected_item_ids(array $orders, int $categoryId): array
{
    $poIds = array_fill_keys(array_map('intval',array_column($orders,'id')),true);
    $ids = [];
    foreach (data_collection('purchase_order_lines') as $line) {
        if (!isset($poIds[(int)($line['purchase_order_id'] ?? 0)])) continue;
        $item = data_find('items',(int)($line['item_id'] ?? 0));
        if ($item && data_record_visible($item) && ($categoryId <= 0 || (int)($item['category_id'] ?? 0) === $categoryId)) $ids[(int)$item['id']] = true;
    }
    return array_keys($ids);
}

function scenario_baseline(array $inputs): array
{
    $supplier = scenario_scope_supplier($inputs);
    if ((int)$inputs['supplier_id'] > 0 && !$supplier) throw new RuntimeException('The selected supplier is outside the active company scope.');
    $categoryId = scenario_category_id($inputs,$supplier);
    $orders = scenario_affected_purchase_orders($supplier,$categoryId);
    $itemIds = scenario_affected_item_ids($orders,$categoryId);
    $itemMap = array_fill_keys($itemIds,true);
    $inventoryValue = 0.0;
    $availableUnits = 0.0;
    foreach (data_visible_collection('inventory_snapshots') as $snapshot) {
        if (!isset($itemMap[(int)($snapshot['item_id'] ?? 0)])) continue;
        $inventoryValue += (float)($snapshot['value'] ?? 0);
        $availableUnits += (float)($snapshot['available'] ?? 0);
    }
    $openPoValue = 0.0;
    $pastDue = 0;
    foreach ($orders as $po) {
        if (in_array((string)($po['status'] ?? ''),['open','past_due','partially_received'],true)) $openPoValue += (float)($po['total_amount'] ?? 0);
        if (($po['status'] ?? '') === 'past_due') $pastDue++;
    }
    $annualSpend = $supplier ? (float)($supplier['annual_spend'] ?? 0) : 0.0;
    if (!$supplier) {
        foreach (data_visible_collection('suppliers') as $candidate) if ($categoryId <= 0 || (int)($candidate['category_id'] ?? 0) === $categoryId) $annualSpend += (float)($candidate['annual_spend'] ?? 0);
    }
    if ($annualSpend <= 0) $annualSpend = array_sum(array_map(static fn(array $po): float => (float)($po['total_amount'] ?? 0),$orders));
    return [
        'supplier'=>$supplier,
        'category_id'=>$categoryId,
        'annual_spend'=>$annualSpend,
        'orders'=>$orders,
        'open_po_value'=>$openPoValue,
        'past_due_count'=>$pastDue,
        'item_ids'=>$itemIds,
        'inventory_value'=>$inventoryValue,
        'available_units'=>$availableUnits,
    ];
}

function scenario_risk_level(float $score): string
{
    return $score >= 80 ? 'critical' : ($score >= 60 ? 'high' : ($score >= 35 ? 'medium' : 'low'));
}

function scenario_alternative_suppliers(array $baseline): array
{
    $selectedId = (int)($baseline['supplier']['id'] ?? 0);
    $categoryId = (int)$baseline['category_id'];
    $alternatives = [];
    foreach (data_visible_collection('suppliers') as $supplier) {
        if ((int)$supplier['id'] === $selectedId || ($categoryId > 0 && (int)($supplier['category_id'] ?? 0) !== $categoryId)) continue;
        $scorecard = null;
        foreach (data_visible_collection('supplier_scorecards') as $candidate) if ((int)($candidate['supplier_id'] ?? 0)===(int)$supplier['id']) { $scorecard=$candidate; break; }
        $riskScore = ['low'=>95,'medium'=>72,'high'=>42,'critical'=>18][(string)($supplier['risk'] ?? 'medium')] ?? 60;
        $overall = (float)($scorecard['overall'] ?? 75);
        $alternatives[] = ['supplier'=>$supplier,'readiness'=>round(($overall*.7)+($riskScore*.3),1),'overall'=>$overall,'risk_score'=>$riskScore];
    }
    usort($alternatives,static fn(array $a,array $b):int=>$b['readiness']<=>$a['readiness']);
    return array_slice($alternatives,0,4);
}

function scenario_calculate(array $raw): array
{
    $inputs = scenario_normalize_inputs($raw);
    $baseline = scenario_baseline($inputs);
    $spend = (float)$baseline['annual_spend'];
    $type = (string)$inputs['scenario_type'];
    $activePriceChange = in_array($type, ['price_increase','compound'], true) ? (float)$inputs['price_change_pct'] : 0.0;
    $activeDemandChange = in_array($type, ['demand_spike','compound'], true) ? (float)$inputs['demand_change_pct'] : 0.0;
    $activeDelayDays = in_array($type, ['lead_time_delay','supply_disruption','compound'], true) ? (int)$inputs['lead_time_delay_days'] : 0;
    $activeDisruption = in_array($type, ['supply_disruption','compound'], true) ? (float)$inputs['disruption_pct'] : 0.0;
    $priceImpact = $spend * ($activePriceChange/100);
    $demandImpact = $spend * ($activeDemandChange/100);
    $delayExpedite = (float)$baseline['open_po_value'] * min(0.35,$activeDelayDays/180) * (0.35 + ($activeDisruption/100));
    $disruptionExposure = (float)$baseline['open_po_value'] * ($activeDisruption/100);
    $grossImpact = $priceImpact + $demandImpact + $delayExpedite;
    $savingsOffset = max(0,$grossImpact) * ($inputs['savings_offset_pct']/100);
    $transferAvoidance = (float)$baseline['inventory_value'] * ($inputs['transfer_recovery_pct']/100);
    $netImpact = $grossImpact - $savingsOffset - $transferAvoidance;
    $inventoryBuffer = min(35,log10(max(1,(float)$baseline['inventory_value']))*5);
    $riskScore = min(100,max(0,
        ($activeDisruption*.45) +
        ($activeDelayDays*.9) +
        (max(0,$activeDemandChange)*.22) +
        ($baseline['past_due_count']*8) -
        ($inputs['transfer_recovery_pct']*.18) -
        $inventoryBuffer
    ));
    $serviceRisk = min(100,max(0,$riskScore + ($activeDemandChange*.12) - 5));
    $cashRisk = min(100,max(0,40 + ($activePriceChange*.9) + ($activeDemandChange*.35) + ($activeDisruption*.2) - ($inputs['savings_offset_pct']*.25)));
    $cases = [];
    foreach (['best'=>0.65,'expected'=>1.0,'worst'=>1.45] as $name=>$factor) {
        $cases[$name] = [
            'net_impact'=>round($netImpact*$factor,2),
            'gross_impact'=>round($grossImpact*$factor,2),
            'risk_score'=>round(min(100,$riskScore*($name==='best'?.78:($name==='worst'?1.2:1))),1),
            'service_risk'=>round(min(100,$serviceRisk*($name==='best'?.8:($name==='worst'?1.18:1))),1),
        ];
    }
    $criticalItems = [];
    foreach ($baseline['item_ids'] as $itemId) {
        $item = data_find('items',$itemId);
        if (!$item) continue;
        $available = 0.0;
        foreach (data_visible_collection('inventory_snapshots') as $snapshot) if ((int)($snapshot['item_id'] ?? 0)===$itemId) $available += (float)($snapshot['available'] ?? 0);
        $criticalItems[] = ['item'=>$item,'available'=>$available,'risk'=>scenario_risk_level(min(100,$riskScore+($available<=10?20:0)))];
    }
    usort($criticalItems,static fn(array $a,array $b):int=>($a['available']<=>$b['available']));
    return [
        'inputs'=>$inputs,
        'baseline'=>$baseline,
        'price_impact'=>round($priceImpact,2),
        'demand_impact'=>round($demandImpact,2),
        'delay_expedite'=>round($delayExpedite,2),
        'disruption_exposure'=>round($disruptionExposure,2),
        'gross_impact'=>round($grossImpact,2),
        'savings_offset'=>round($savingsOffset,2),
        'transfer_avoidance'=>round($transferAvoidance,2),
        'net_impact'=>round($netImpact,2),
        'risk_score'=>round($riskScore,1),
        'risk_level'=>scenario_risk_level($riskScore),
        'service_risk'=>round($serviceRisk,1),
        'cash_risk'=>round($cashRisk,1),
        'cases'=>$cases,
        'alternatives'=>scenario_alternative_suppliers($baseline),
        'critical_items'=>array_slice($criticalItems,0,6),
        'active_assumptions'=>['price_change_pct'=>$activePriceChange,'demand_change_pct'=>$activeDemandChange,'lead_time_delay_days'=>$activeDelayDays,'disruption_pct'=>$activeDisruption],
        'generated_at'=>date('Y-m-d H:i:s'),
    ];
}

function scenario_demo_seed_records(): array
{
    return [[
        'id'=>1,'scenario_number'=>'SCN-2026-0001','company_id'=>null,'title'=>'Battery supply disruption planning',
        'scenario_type'=>'compound','supplier_id'=>1,'category_id'=>1,'inputs'=>scenario_default_inputs(),
        'result_summary'=>['net_impact'=>184000,'risk_score'=>63,'risk_level'=>'high'],'decision_status'=>'draft',
        'owner_id'=>3,'approval_id'=>null,'created_at'=>'2026-07-26 09:00:00','updated_at'=>'2026-07-26 09:00:00',
    ]];
}

function scenario_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo = production_database_connection();
    if (!$pdo) return false;
    try { $pdo->query('SELECT id FROM procurement_scenarios LIMIT 1'); return true; }
    catch (Throwable) { return false; }
}

function scenario_records(): array
{
    if (data_is_demo()) {
        if (!isset($_SESSION['gruber_demo_state']['procurement_scenarios'])) $_SESSION['gruber_demo_state']['procurement_scenarios']=scenario_demo_seed_records();
        return array_values($_SESSION['gruber_demo_state']['procurement_scenarios']);
    }
    if (!scenario_tables_ready()) return [];
    $pdo = production_database_connection();
    if (current_company_id()==='enterprise') $stmt=$pdo->query('SELECT * FROM procurement_scenarios ORDER BY updated_at DESC,id DESC');
    else { $stmt=$pdo->prepare('SELECT * FROM procurement_scenarios WHERE company_id=? ORDER BY updated_at DESC,id DESC'); $stmt->execute([(int)current_company_id()]); }
    $rows=$stmt->fetchAll();
    foreach($rows as &$row){$row['inputs']=scenario_normalize_inputs(json_decode((string)$row['inputs_json'],true)?:[]);$row['result_summary']=json_decode((string)$row['result_json'],true)?:[];} unset($row);
    return $rows;
}

function scenario_find_record(int $id): ?array
{
    foreach(scenario_records() as $record) if((int)$record['id']===$id) return $record;
    return null;
}

function scenario_next_number(): string
{
    return 'SCN-'.date('Y').'-'.str_pad((string)(count(scenario_records())+1),4,'0',STR_PAD_LEFT);
}

function scenario_save_record(array $record): array
{
    $record['inputs']=scenario_normalize_inputs($record['inputs']??[]);
    $record['updated_at']=date('Y-m-d H:i:s');
    $record['created_at']=$record['created_at']??$record['updated_at'];
    $record['scenario_number']=$record['scenario_number']??scenario_next_number();
    if(data_is_demo()){
        $records=scenario_records();$id=(int)($record['id']??0);
        if($id<=0){$id=max([0,...array_map('intval',array_column($records,'id'))])+1;$record['id']=$id;}
        $found=false;foreach($records as $index=>$existing)if((int)$existing['id']===$id){$records[$index]=$record;$found=true;break;}if(!$found)$records[]=$record;
        $_SESSION['gruber_demo_state']['procurement_scenarios']=$records;return $record;
    }
    if(!scenario_tables_ready())throw new RuntimeException('Import the Section 12 scenario migration before saving Production Data simulations.');
    $pdo=production_database_connection();$id=(int)($record['id']??0);
    $params=[$record['scenario_number'],$record['company_id'],$record['title'],$record['scenario_type'],$record['supplier_id'],$record['category_id'],json_encode($record['inputs'],JSON_THROW_ON_ERROR),json_encode($record['result_summary'],JSON_THROW_ON_ERROR),$record['risk_level'],$record['decision_status'],$record['owner_id'],$record['approval_id'],$record['created_at'],$record['updated_at']];
    if($id>0){$stmt=$pdo->prepare('UPDATE procurement_scenarios SET scenario_number=?,company_id=?,title=?,scenario_type=?,supplier_id=?,category_id=?,inputs_json=?,result_json=?,risk_level=?,decision_status=?,owner_id=?,approval_id=?,created_at=?,updated_at=? WHERE id=?');$stmt->execute([...$params,$id]);}
    else{$stmt=$pdo->prepare('INSERT INTO procurement_scenarios (scenario_number,company_id,title,scenario_type,supplier_id,category_id,inputs_json,result_json,risk_level,decision_status,owner_id,approval_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute($params);$id=(int)$pdo->lastInsertId();}
    $record['id']=$id;return $record;
}

function scenario_effective_status(array $record): string
{
    $approvalId=(int)($record['approval_id']??0);
    if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested'],true))return (string)$approval['status'];}
    return (string)($record['decision_status']??'draft');
}

function scenario_csv_cell(mixed $value): string
{
    $value=(string)$value;if($value!==''&&preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value;
}

function scenario_export_csv(array $simulation): never
{
    require_permission('reports.export');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="procurement-risk-scenario-'.date('Ymd-His').'.csv"');
    $out=fopen('php://output','wb');
    fputcsv($out,['Case','Net annual impact','Gross impact','Risk score','Service risk']);
    foreach($simulation['cases'] as $case=>$values)fputcsv($out,array_map('scenario_csv_cell',[ucfirst($case),$values['net_impact'],$values['gross_impact'],$values['risk_score'],$values['service_risk']]));
    fputcsv($out,[]);fputcsv($out,['Formula component','Value']);
    foreach(['price_impact','demand_impact','delay_expedite','disruption_exposure','savings_offset','transfer_avoidance','net_impact'] as $key)fputcsv($out,array_map('scenario_csv_cell',[status_label($key),$simulation[$key]]));
    fclose($out);exit;
}
