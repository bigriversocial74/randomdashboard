<?php
declare(strict_types=1);

function process_mapping_process_assigned_to_entity(int $processId, int $entityId): bool
{
    foreach (process_mapping_raw_assignments() as $assignment) {
        if ((int)$assignment['process_id'] !== $processId || (int)$assignment['entity_id'] !== $entityId) continue;
        if (process_mapping_row_effective($assignment)) return true;
    }
    return false;
}

function process_mapping_canonical_collection(string $recordType): ?string
{
    return match ($recordType) {
        'purchase_order' => 'purchase_orders',
        'purchase_requisition' => 'purchase_requests',
        'supplier' => 'suppliers',
        'supplier_invoice' => 'supplier_invoices',
        'contract', 'supplier_contract' => 'contracts',
        'inventory_snapshot' => 'inventory_snapshots',
        'savings_opportunity' => 'savings_opportunities',
        default => null,
    };
}

function process_mapping_assert_canonical_record_scope(string $recordType, int $recordId, array $entity): array
{
    if ($recordId <= 0) throw new RuntimeException('A canonical record ID is required.');
    $collection = process_mapping_canonical_collection($recordType);
    if ($collection === null) {
        throw new RuntimeException('Select a supported canonical record type before starting a live process.');
    }
    $record = data_find($collection, $recordId);
    if (!$record || !data_record_visible($record)) {
        throw new RuntimeException('The canonical record is missing or outside the active company scope.');
    }
    $companyId = (int)($entity['company_id'] ?? 0);
    if ($companyId > 0) {
        if (array_key_exists('company_id', $record) && (int)($record['company_id'] ?? 0) !== $companyId) {
            throw new RuntimeException('The canonical record does not belong to the selected operating entity.');
        }
        if (!empty($record['company_ids']) && is_array($record['company_ids'])
            && !in_array($companyId, array_map('intval', $record['company_ids']), true)) {
            throw new RuntimeException('The canonical record is not authorized for the selected operating entity.');
        }
    }
    return $record;
}

function process_mapping_with_instance_lock(string $key, callable $callback): mixed
{
    if (data_is_demo()) return $callback();
    $pdo = production_database_connection();
    if (!$pdo) throw new RuntimeException('Production database connection unavailable.');
    $lockName = 'gruber-process-'.substr(hash('sha256', $key), 0, 48);
    $statement = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $statement->execute([$lockName]);
    if ((int)$statement->fetchColumn() !== 1) {
        throw new RuntimeException('The canonical record is already being processed. Try again after the active request finishes.');
    }
    try {
        return $callback();
    } finally {
        try {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        } catch (Throwable) {
            // The connection lifecycle releases advisory locks as a final fallback.
        }
    }
}

function process_mapping_find_active_duplicate(int $processId, int $entityId, string $recordType, int $recordId): ?array
{
    foreach (process_mapping_raw_instances() as $instance) {
        if ((int)$instance['process_id'] !== $processId || (int)$instance['entity_id'] !== $entityId) continue;
        if ((string)$instance['canonical_record_type'] !== $recordType || (int)$instance['canonical_record_id'] !== $recordId) continue;
        if (!in_array((string)$instance['status'], ['completed','cancelled','closed'], true)) return $instance;
    }
    return null;
}

function process_mapping_lane_for_step(array $step): ?array
{
    return process_mapping_find(process_mapping_lanes((int)$step['version_id']), (int)$step['lane_id']);
}

function process_mapping_step_assignment_entity(array $step, int $initiatingEntityId): ?int
{
    $lane = process_mapping_lane_for_step($step);
    if (!$lane) throw new RuntimeException('The process step has no governed swimlane.');
    if (($lane['participant_type'] ?? '') === 'external') return null;
    $entityId = (int)($lane['entity_id'] ?? 0);
    if ($entityId <= 0) $entityId = $initiatingEntityId;
    entity_system_assert_entity_access($entityId, 'process_mapping', true);
    return $entityId;
}

function process_mapping_start_instance(int $processId, int $entityId, string $recordType, int $recordId, string $title, string $note): array
{
    process_mapping_require_capability('execute');
    $entity = entity_system_assert_entity_access($entityId, 'process_mapping', true);
    if (($entity['entity_type'] ?? '') !== 'operating_company') {
        throw new RuntimeException('New live process activity must start from an active operating company.');
    }
    $process = process_mapping_find_process($processId);
    if (!$process || $process['status'] !== 'published') throw new RuntimeException('Select a published process.');
    if (!process_mapping_process_assigned_to_entity($processId, $entityId)) {
        throw new RuntimeException('The selected process is not assigned to this operating entity.');
    }
    process_mapping_assert_canonical_record_scope($recordType, $recordId, $entity);
    if (trim($title) === '' || trim($note) === '') throw new RuntimeException('Instance title and start evidence are required.');
    $version = process_mapping_process_version($process);
    if (!$version || $version['status'] !== 'published' || empty($version['locked_at'])) {
        throw new RuntimeException('A locked published process version is required.');
    }
    $steps = process_mapping_steps((int)$version['id']);
    $start = null;
    foreach ($steps as $step) if ($step['step_type'] === 'start') { $start = $step; break; }
    if (!$start) throw new RuntimeException('The process has no start step.');

    $instance = process_mapping_with_instance_lock(
        $processId.'|'.$entityId.'|'.$recordType.'|'.$recordId,
        static function () use ($processId, $entityId, $recordType, $recordId, $title, $note, $version, $start): array {
            $duplicate = process_mapping_find_active_duplicate($processId, $entityId, $recordType, $recordId);
            if ($duplicate) throw new RuntimeException('An active process instance already exists for this canonical record.');
            $number = 'PROC-'.date('Ymd-His').'-'.strtoupper(substr(hash('sha256', $processId.'|'.$entityId.'|'.$recordType.'|'.$recordId.'|'.microtime(true)), 0, 8));
            return process_mapping_save_instance([
                'id'=>null,'process_id'=>$processId,'version_id'=>$version['id'],'instance_number'=>$number,
                'entity_id'=>$entityId,'canonical_record_type'=>$recordType,'canonical_record_id'=>$recordId,
                'instance_title'=>mb_substr($title, 0, 255),'status'=>'in_progress','current_step_id'=>$start['id'],
                'started_by'=>(int)current_user()['id'],'started_at'=>date('Y-m-d H:i:s'),
                'due_at'=>date('Y-m-d H:i:s', strtotime('+7 days')),'completed_at'=>null,'cycle_seconds'=>0,
                'process_data_json'=>json_encode(['start_evidence'=>mb_substr($note, 0, 5000)], JSON_UNESCAPED_SLASHES),
            ]);
        }
    );
    foreach ($steps as $step) {
        $active = (int)$step['id'] === (int)$start['id'];
        $assignedEntityId = process_mapping_step_assignment_entity($step, $entityId);
        process_mapping_save_step_instance([
            'id'=>null,'instance_id'=>$instance['id'],'step_id'=>$step['id'],'assigned_entity_id'=>$assignedEntityId,
            'assigned_user_id'=>null,'status'=>$active ? 'active' : 'pending',
            'started_at'=>$active ? date('Y-m-d H:i:s') : null,
            'due_at'=>$active && (int)$step['sla_minutes'] > 0
                ? date('Y-m-d H:i:s', strtotime('+'.(int)$step['sla_minutes'].' minutes')) : null,
            'completed_at'=>null,'elapsed_seconds'=>0,'evidence_note'=>'','output_json'=>'{}',
        ]);
    }
    process_mapping_add_event($processId, (int)$version['id'], (int)$instance['id'], (int)$start['id'], 'instance_started', null, 'in_progress', 'medium', $note);
    return $instance;
}
