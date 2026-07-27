<?php
declare(strict_types=1);

function process_mapping_tables(): array
{
    return [
        'business_processes','business_process_versions','business_process_lanes','business_process_steps',
        'business_process_transitions','business_process_controls','business_process_integrations',
        'business_process_assignments','business_process_instances','business_process_step_instances',
        'business_process_exceptions','business_process_events',
    ];
}

function process_mapping_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo = production_database_connection();
    if (!$pdo) return false;
    try {
        $names = process_mapping_tables();
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('
            .implode(',', array_fill(0, count($names), '?')).')'
        );
        $statement->execute($names);
        return (int)$statement->fetchColumn() === count($names);
    } catch (Throwable) {
        return false;
    }
}

function process_mapping_require_tables(): void
{
    if (!process_mapping_tables_ready()) {
        throw new RuntimeException('Import the Section 25 migration before using Production Data process-mapping writes.');
    }
}

function process_mapping_demo_collection(string $key, callable $seed): array
{
    if (!isset($_SESSION['gruber_demo_state'][$key])) {
        $_SESSION['gruber_demo_state'][$key] = $seed();
    }
    return array_values($_SESSION['gruber_demo_state'][$key]);
}

function process_mapping_demo_save(string $key, array $record, callable $seed): array
{
    $rows = process_mapping_demo_collection($key, $seed);
    $id = (int)($record['id'] ?? 0);
    if ($id <= 0) {
        $id = max([0, ...array_map('intval', array_column($rows, 'id'))]) + 1;
        $record['id'] = $id;
        $rows[] = $record;
    } else {
        $found = false;
        foreach ($rows as $index => $row) {
            if ((int)$row['id'] !== $id) continue;
            $rows[$index] = array_replace($row, $record);
            $record = $rows[$index];
            $found = true;
            break;
        }
        if (!$found) $rows[] = $record;
    }
    $_SESSION['gruber_demo_state'][$key] = array_values($rows);
    return $record;
}

function process_mapping_rows(string $table, string $key, callable $seed, string $order = 'id'): array
{
    if (data_is_demo()) return process_mapping_demo_collection($key, $seed);
    if (!process_mapping_tables_ready()) return [];
    return production_database_connection()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();
}

function process_mapping_raw_processes(): array
{
    return process_mapping_rows('business_processes', 'business_processes', 'process_mapping_demo_processes', 'process_name,id');
}

function process_mapping_raw_versions(): array
{
    return process_mapping_rows('business_process_versions', 'business_process_versions', 'process_mapping_demo_versions', 'process_id,id DESC');
}

function process_mapping_raw_lanes(): array
{
    return process_mapping_rows('business_process_lanes', 'business_process_lanes', 'process_mapping_demo_lanes', 'version_id,sort_order,id');
}

function process_mapping_raw_steps(): array
{
    return process_mapping_rows('business_process_steps', 'business_process_steps', 'process_mapping_demo_steps', 'version_id,sort_order,id');
}

function process_mapping_raw_transitions(): array
{
    return process_mapping_rows('business_process_transitions', 'business_process_transitions', 'process_mapping_demo_transitions', 'version_id,sort_order,id');
}

function process_mapping_raw_controls(): array
{
    return process_mapping_rows('business_process_controls', 'business_process_controls', 'process_mapping_demo_controls', 'step_id,id');
}

function process_mapping_raw_integrations(): array
{
    return process_mapping_rows('business_process_integrations', 'business_process_integrations', 'process_mapping_demo_integrations', 'step_id,id');
}

function process_mapping_raw_assignments(): array
{
    return process_mapping_rows('business_process_assignments', 'business_process_assignments', 'process_mapping_demo_assignments', 'process_id,entity_id');
}

function process_mapping_raw_instances(): array
{
    return process_mapping_rows('business_process_instances', 'business_process_instances', 'process_mapping_demo_instances', 'created_at DESC,id DESC');
}

function process_mapping_raw_step_instances(): array
{
    return process_mapping_rows('business_process_step_instances', 'business_process_step_instances', 'process_mapping_demo_step_instances', 'instance_id,id');
}

function process_mapping_raw_exceptions(): array
{
    return process_mapping_rows('business_process_exceptions', 'business_process_exceptions', 'process_mapping_demo_exceptions', 'opened_at DESC,id DESC');
}

function process_mapping_raw_events(): array
{
    return process_mapping_rows('business_process_events', 'business_process_events', 'process_mapping_demo_events', 'created_at DESC,id DESC');
}

function process_mapping_row_effective(array $row): bool
{
    if (($row['status'] ?? 'active') !== 'active') return false;
    $today = date('Y-m-d');
    if (!empty($row['effective_from']) && (string)$row['effective_from'] > $today) return false;
    if (!empty($row['effective_to']) && (string)$row['effective_to'] < $today) return false;
    return true;
}
