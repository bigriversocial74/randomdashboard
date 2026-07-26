<?php
declare(strict_types=1);

function sourcing_default_weights(): array
{
    return ['delivery'=>20,'quality'=>20,'cost'=>20,'risk'=>15,'terms'=>10,'coverage'=>15];
}

function sourcing_normalize_weights(array $weights): array
{
    $defaults = sourcing_default_weights();
    $normalized = [];
    foreach ($defaults as $key => $default) {
        $value = isset($weights[$key]) ? (int)$weights[$key] : $default;
        $normalized[$key] = max(0, min(100, $value));
    }
    $total = array_sum($normalized);
    if ($total <= 0) return $defaults;
    foreach ($normalized as $key => $value) {
        $normalized[$key] = round(($value / $total) * 100, 2);
    }
    return $normalized;
}

function sourcing_selected_supplier_ids(array $values): array
{
    $visible = array_fill_keys(array_map('intval', array_column(data_visible_collection('suppliers'), 'id')), true);
    $ids = [];
    foreach ($values as $value) {
        $id = (int)$value;
        if ($id > 0 && isset($visible[$id])) $ids[$id] = true;
    }
    return array_slice(array_keys($ids), 0, 5);
}

function sourcing_latest_scorecard(int $supplierId): ?array
{
    $matches = array_values(array_filter(data_visible_collection('supplier_scorecards'), static fn(array $row): bool => (int)($row['supplier_id'] ?? 0) === $supplierId));
    usort($matches, static fn(array $a, array $b): int => strcmp((string)($b['period'] ?? ''), (string)($a['period'] ?? '')));
    return $matches[0] ?? null;
}

function sourcing_payment_terms_score(string $terms): float
{
    if (preg_match('/net\s*(\d+)/i', $terms, $match)) return min(100, 50 + ((int)$match[1] * 1.2));
    if (stripos($terms, 'prepaid') !== false) return 20;
    if (stripos($terms, 'due on receipt') !== false) return 35;
    return 55;
}

function sourcing_supplier_metrics(int $supplierId): array
{
    $supplier = data_find('suppliers', $supplierId);
    if (!$supplier || !data_record_visible($supplier)) throw new RuntimeException('Supplier is outside the active scope.');

    $scorecard = sourcing_latest_scorecard($supplierId);
    $purchaseOrders = array_values(array_filter(data_visible_collection('purchase_orders'), static fn(array $po): bool => (int)($po['supplier_id'] ?? 0) === $supplierId));
    $poIds = array_fill_keys(array_map('intval', array_column($purchaseOrders, 'id')), true);
    $itemIds = [];
    foreach (data_collection('purchase_order_lines') as $line) {
        if (isset($poIds[(int)($line['purchase_order_id'] ?? 0)]) && !empty($line['item_id'])) $itemIds[(int)$line['item_id']] = true;
    }
    $contracts = array_values(array_filter(data_visible_collection('contracts'), static fn(array $contract): bool => (int)($contract['supplier_id'] ?? 0) === $supplierId));
    $inventoryValue = 0.0;
    foreach (data_visible_collection('inventory_snapshots') as $snapshot) {
        if (isset($itemIds[(int)($snapshot['item_id'] ?? 0)])) $inventoryValue += (float)($snapshot['value'] ?? 0);
    }
    $openPoValue = 0.0;
    $pastDue = 0;
    foreach ($purchaseOrders as $po) {
        if (in_array((string)($po['status'] ?? ''), ['open','past_due','partially_received'], true)) $openPoValue += (float)($po['total_amount'] ?? 0);
        if (($po['status'] ?? '') === 'past_due' || (!empty($po['expected_date']) && strtotime((string)$po['expected_date']) < strtotime(date('Y-m-d')) && !in_array((string)($po['status'] ?? ''), ['closed','cancelled'], true))) $pastDue++;
    }
    $riskMap = ['low'=>95,'medium'=>72,'high'=>42,'critical'=>18];
    $companyCount = count(array_unique(array_map('intval', $supplier['company_ids'] ?? [])));
    $coverageScore = min(100, ($companyCount / max(1, count(data_collection('companies')))) * 100);
    $delivery = (float)($scorecard['on_time_delivery'] ?? ($pastDue ? 65 : 82));
    $quality = (float)($scorecard['quality'] ?? 80);
    $cost = (float)($scorecard['cost_competitiveness'] ?? 75);
    $responsiveness = (float)($scorecard['responsiveness'] ?? 78);
    $riskScore = (float)($riskMap[(string)($supplier['risk'] ?? 'medium')] ?? 60);
    $termsScore = sourcing_payment_terms_score((string)($supplier['payment_terms'] ?? ''));
    $evidence = [
        'scorecard' => $scorecard !== null,
        'purchase_orders' => count($purchaseOrders) > 0,
        'contracts' => count($contracts) > 0,
        'items' => count($itemIds) > 0,
    ];
    $confidence = 40 + (count(array_filter($evidence)) * 15);

    return [
        'supplier'=>$supplier,
        'scorecard'=>$scorecard,
        'annual_spend'=>(float)($supplier['annual_spend'] ?? 0),
        'open_po_value'=>$openPoValue,
        'po_count'=>count($purchaseOrders),
        'past_due_count'=>$pastDue,
        'contract_count'=>count($contracts),
        'contract_value'=>array_sum(array_map(static fn(array $row): float => (float)($row['annual_value'] ?? 0), $contracts)),
        'item_count'=>count($itemIds),
        'inventory_value'=>$inventoryValue,
        'company_count'=>$companyCount,
        'delivery'=>$delivery,
        'quality'=>$quality,
        'cost'=>$cost,
        'responsiveness'=>$responsiveness,
        'risk_score'=>$riskScore,
        'terms_score'=>$termsScore,
        'coverage_score'=>$coverageScore,
        'confidence'=>min(100, $confidence),
        'missing_data'=>array_keys(array_filter($evidence, static fn(bool $value): bool => !$value)),
    ];
}

function sourcing_build_comparison(array $supplierIds, array $weights): array
{
    $supplierIds = sourcing_selected_supplier_ids($supplierIds);
    if (count($supplierIds) < 2) throw new RuntimeException('Select at least two visible suppliers.');
    $weights = sourcing_normalize_weights($weights);
    $rows = [];
    foreach ($supplierIds as $supplierId) {
        $metrics = sourcing_supplier_metrics($supplierId);
        $scores = [
            'delivery'=>$metrics['delivery'],
            'quality'=>$metrics['quality'],
            'cost'=>$metrics['cost'],
            'risk'=>$metrics['risk_score'],
            'terms'=>$metrics['terms_score'],
            'coverage'=>$metrics['coverage_score'],
        ];
        $weighted = 0.0;
        foreach ($scores as $key => $score) $weighted += ($score * ((float)$weights[$key] / 100));
        $metrics['criteria_scores'] = $scores;
        $metrics['weighted_score'] = round($weighted, 1);
        $rows[] = $metrics;
    }
    usort($rows, static fn(array $a, array $b): int => $b['weighted_score'] <=> $a['weighted_score']);
    $spread = ($rows[0]['weighted_score'] ?? 0) - ($rows[1]['weighted_score'] ?? 0);
    return [
        'weights'=>$weights,
        'rows'=>$rows,
        'recommended_supplier_id'=>(int)$rows[0]['supplier']['id'],
        'alternate_supplier_id'=>(int)($rows[1]['supplier']['id'] ?? 0),
        'decision_confidence'=>(int)round(array_sum(array_column($rows, 'confidence')) / max(1, count($rows))),
        'score_spread'=>round($spread, 1),
        'generated_at'=>date('Y-m-d H:i:s'),
    ];
}

function sourcing_demo_seed_records(): array
{
    return [[
        'id'=>1,'comparison_number'=>'SRC-2026-0001','company_id'=>null,'title'=>'Enterprise strategic supplier review',
        'category'=>'Cross-company sourcing','selected_supplier_ids'=>[1,2,3,4],
        'weights'=>sourcing_default_weights(),'preferred_supplier_id'=>1,'alternate_supplier_id'=>3,
        'decision_status'=>'draft','rationale'=>'Seeded demonstration of a governed supplier award using operational evidence.',
        'owner_id'=>3,'approval_id'=>null,'created_at'=>'2026-07-26 08:00:00','updated_at'=>'2026-07-26 08:00:00',
    ]];
}

function sourcing_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo = production_database_connection();
    if (!$pdo) return false;
    try { $pdo->query('SELECT id FROM sourcing_comparisons LIMIT 1'); return true; }
    catch (Throwable) { return false; }
}

function sourcing_records(): array
{
    if (data_is_demo()) {
        if (!isset($_SESSION['gruber_demo_state']['sourcing_comparisons'])) $_SESSION['gruber_demo_state']['sourcing_comparisons'] = sourcing_demo_seed_records();
        return array_values($_SESSION['gruber_demo_state']['sourcing_comparisons']);
    }
    if (!sourcing_tables_ready()) return [];
    $pdo = production_database_connection();
    $selected = current_company_id();
    if ($selected === 'enterprise') {
        $stmt = $pdo->query('SELECT * FROM sourcing_comparisons ORDER BY updated_at DESC, id DESC');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM sourcing_comparisons WHERE company_id = ? ORDER BY updated_at DESC, id DESC');
        $stmt->execute([(int)$selected]);
    }
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['selected_supplier_ids'] = array_values(array_map('intval', json_decode((string)$row['selected_supplier_ids_json'], true) ?: []));
        $row['weights'] = sourcing_normalize_weights(json_decode((string)$row['criteria_weights_json'], true) ?: []);
    }
    unset($row);
    return $rows;
}

function sourcing_find_record(int $id): ?array
{
    foreach (sourcing_records() as $record) if ((int)$record['id'] === $id) return $record;
    return null;
}

function sourcing_next_number(): string
{
    return 'SRC-' . date('Y') . '-' . str_pad((string)(count(sourcing_records()) + 1), 4, '0', STR_PAD_LEFT);
}

function sourcing_save_record(array $record): array
{
    $record['selected_supplier_ids'] = sourcing_selected_supplier_ids($record['selected_supplier_ids'] ?? []);
    $record['weights'] = sourcing_normalize_weights($record['weights'] ?? []);
    $record['updated_at'] = date('Y-m-d H:i:s');
    $record['created_at'] = $record['created_at'] ?? $record['updated_at'];
    $record['comparison_number'] = $record['comparison_number'] ?? sourcing_next_number();

    if (data_is_demo()) {
        $records = sourcing_records();
        $id = (int)($record['id'] ?? 0);
        if ($id <= 0) { $id = max([0, ...array_map('intval', array_column($records, 'id'))]) + 1; $record['id'] = $id; }
        $found = false;
        foreach ($records as $index => $existing) if ((int)$existing['id'] === $id) { $records[$index] = $record; $found = true; break; }
        if (!$found) $records[] = $record;
        $_SESSION['gruber_demo_state']['sourcing_comparisons'] = $records;
        return $record;
    }

    if (!sourcing_tables_ready()) throw new RuntimeException('Import the Section 11 sourcing migration before saving Production Data decisions.');
    $pdo = production_database_connection();
    $id = (int)($record['id'] ?? 0);
    $params = [
        $record['comparison_number'], $record['company_id'], $record['title'], $record['category'],
        json_encode($record['selected_supplier_ids'], JSON_THROW_ON_ERROR), json_encode($record['weights'], JSON_THROW_ON_ERROR),
        $record['preferred_supplier_id'], $record['alternate_supplier_id'], $record['decision_status'], $record['rationale'],
        $record['owner_id'], $record['approval_id'], $record['created_at'], $record['updated_at'],
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE sourcing_comparisons SET comparison_number=?,company_id=?,title=?,category=?,selected_supplier_ids_json=?,criteria_weights_json=?,preferred_supplier_id=?,alternate_supplier_id=?,decision_status=?,rationale=?,owner_id=?,approval_id=?,created_at=?,updated_at=? WHERE id=?');
        $stmt->execute([...$params, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO sourcing_comparisons (comparison_number,company_id,title,category,selected_supplier_ids_json,criteria_weights_json,preferred_supplier_id,alternate_supplier_id,decision_status,rationale,owner_id,approval_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute($params);
        $id = (int)$pdo->lastInsertId();
    }
    $record['id'] = $id;
    return $record;
}

function sourcing_effective_status(array $record): string
{
    $approvalId = (int)($record['approval_id'] ?? 0);
    if ($approvalId > 0) {
        $approval = data_find('workflow_approvals', $approvalId);
        if ($approval && in_array((string)($approval['status'] ?? ''), ['pending','in_review','approved','changes_requested'], true)) return (string)$approval['status'];
    }
    return (string)($record['decision_status'] ?? 'draft');
}

function sourcing_csv_cell(mixed $value): string
{
    $value = (string)$value;
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) $value = "'" . $value;
    return $value;
}

function sourcing_export_csv(array $comparison): never
{
    require_permission('reports.export');
    $filename = 'supplier-comparison-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'wb');
    fputcsv($out, ['Supplier','Weighted score','Delivery','Quality','Cost competitiveness','Risk score','Terms score','Coverage score','Annual spend','Open PO value','Past-due POs','Contracts','Items','Evidence confidence']);
    foreach ($comparison['rows'] as $row) {
        fputcsv($out, array_map('sourcing_csv_cell', [
            $row['supplier']['name'],$row['weighted_score'],$row['delivery'],$row['quality'],$row['cost'],$row['risk_score'],$row['terms_score'],$row['coverage_score'],$row['annual_spend'],$row['open_po_value'],$row['past_due_count'],$row['contract_count'],$row['item_count'],$row['confidence'],
        ]));
    }
    fclose($out);
    exit;
}
