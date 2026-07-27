<?php
declare(strict_types=1);

function process_mapping_visible_process_ids(): array
{
    $entityIds = array_fill_keys(entity_system_data_scope_entity_ids(null, 'process_mapping'), true);
    $visible = [];
    foreach (process_mapping_raw_assignments() as $assignment) {
        if (!process_mapping_row_effective($assignment)) continue;
        if (isset($entityIds[(int)$assignment['entity_id']])) $visible[(int)$assignment['process_id']] = true;
    }

    // Enterprise operators may inspect enterprise-owned templates before a local
    // assignment exists. Concrete company contexts never gain visibility merely
    // because the hierarchy parent is visible for navigation.
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) {
        foreach (process_mapping_raw_processes() as $process) $visible[(int)$process['id']] = true;
    }

    return array_map('intval', array_keys($visible));
}

function process_mapping_processes(): array
{
    $allowed = array_fill_keys(process_mapping_visible_process_ids(), true);
    return array_values(array_filter(
        process_mapping_raw_processes(),
        static fn(array $row): bool => isset($allowed[(int)$row['id']])
    ));
}

function process_mapping_versions(?int $processId = null): array
{
    $allowed = array_fill_keys(process_mapping_visible_process_ids(), true);
    $rows = array_values(array_filter(
        process_mapping_raw_versions(),
        static fn(array $row): bool => isset($allowed[(int)$row['process_id']])
    ));
    return $processId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['process_id'] === $processId));
}

function process_mapping_visible_version_ids(): array
{
    return array_values(array_unique(array_map('intval', array_column(process_mapping_versions(), 'id'))));
}

function process_mapping_lanes(?int $versionId = null): array
{
    $allowed = array_fill_keys(process_mapping_visible_version_ids(), true);
    $rows = array_values(array_filter(
        process_mapping_raw_lanes(),
        static fn(array $row): bool => isset($allowed[(int)$row['version_id']])
    ));
    return $versionId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['version_id'] === $versionId));
}

function process_mapping_steps(?int $versionId = null): array
{
    $allowed = array_fill_keys(process_mapping_visible_version_ids(), true);
    $rows = array_values(array_filter(
        process_mapping_raw_steps(),
        static fn(array $row): bool => isset($allowed[(int)$row['version_id']])
    ));
    return $versionId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['version_id'] === $versionId));
}

function process_mapping_visible_step_ids(): array
{
    return array_values(array_unique(array_map('intval', array_column(process_mapping_steps(), 'id'))));
}

function process_mapping_transitions(?int $versionId = null): array
{
    $allowed = array_fill_keys(process_mapping_visible_version_ids(), true);
    $rows = array_values(array_filter(
        process_mapping_raw_transitions(),
        static fn(array $row): bool => isset($allowed[(int)$row['version_id']])
    ));
    return $versionId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['version_id'] === $versionId));
}

function process_mapping_controls(): array
{
    $allowed = array_fill_keys(process_mapping_visible_step_ids(), true);
    return array_values(array_filter(
        process_mapping_raw_controls(),
        static fn(array $row): bool => isset($allowed[(int)$row['step_id']])
    ));
}

function process_mapping_integrations(): array
{
    $allowed = array_fill_keys(process_mapping_visible_step_ids(), true);
    return array_values(array_filter(
        process_mapping_raw_integrations(),
        static fn(array $row): bool => isset($allowed[(int)$row['step_id']])
    ));
}

function process_mapping_assignments(): array
{
    $processIds = array_fill_keys(process_mapping_visible_process_ids(), true);
    $entityIds = array_fill_keys(entity_system_data_scope_entity_ids(null, 'process_mapping'), true);
    return array_values(array_filter(
        process_mapping_raw_assignments(),
        static fn(array $row): bool => isset($processIds[(int)$row['process_id']])
            && isset($entityIds[(int)$row['entity_id']])
    ));
}

function process_mapping_instances(?int $processId = null): array
{
    $processIds = array_fill_keys(process_mapping_visible_process_ids(), true);
    $entityIds = array_fill_keys(entity_system_data_scope_entity_ids(null, 'process_mapping'), true);
    $rows = array_values(array_filter(
        process_mapping_raw_instances(),
        static fn(array $row): bool => isset($processIds[(int)$row['process_id']])
            && isset($entityIds[(int)$row['entity_id']])
    ));
    return $processId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['process_id'] === $processId));
}

function process_mapping_visible_instance_ids(): array
{
    return array_values(array_unique(array_map('intval', array_column(process_mapping_instances(), 'id'))));
}

function process_mapping_step_instances(?int $instanceId = null): array
{
    $allowed = array_fill_keys(process_mapping_visible_instance_ids(), true);
    $rows = array_values(array_filter(
        process_mapping_raw_step_instances(),
        static fn(array $row): bool => isset($allowed[(int)$row['instance_id']])
    ));
    return $instanceId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['instance_id'] === $instanceId));
}

function process_mapping_exceptions(?int $instanceId = null): array
{
    $allowed = array_fill_keys(process_mapping_visible_instance_ids(), true);
    $rows = array_values(array_filter(
        process_mapping_raw_exceptions(),
        static fn(array $row): bool => isset($allowed[(int)$row['instance_id']])
    ));
    return $instanceId === null
        ? $rows
        : array_values(array_filter($rows, static fn(array $row): bool => (int)$row['instance_id'] === $instanceId));
}

function process_mapping_events(): array
{
    $processIds = array_fill_keys(process_mapping_visible_process_ids(), true);
    $instanceIds = array_fill_keys(process_mapping_visible_instance_ids(), true);
    return array_values(array_filter(process_mapping_raw_events(), static function (array $row) use ($processIds, $instanceIds): bool {
        if (!empty($row['instance_id'])) return isset($instanceIds[(int)$row['instance_id']]);
        return !empty($row['process_id']) && isset($processIds[(int)$row['process_id']]);
    }));
}

function process_mapping_find(array $rows, int $id): ?array
{
    foreach ($rows as $row) if ((int)$row['id'] === $id) return $row;
    return null;
}

function process_mapping_find_process(int $id): ?array { return process_mapping_find(process_mapping_processes(), $id); }
function process_mapping_find_version(int $id): ?array { return process_mapping_find(process_mapping_versions(), $id); }
function process_mapping_find_step(int $id): ?array { return process_mapping_find(process_mapping_steps(), $id); }
function process_mapping_find_instance(int $id): ?array { return process_mapping_find(process_mapping_instances(), $id); }
function process_mapping_find_step_instance(int $id): ?array { return process_mapping_find(process_mapping_step_instances(), $id); }

function process_mapping_raw_find_process(int $id): ?array { return process_mapping_find(process_mapping_raw_processes(), $id); }
function process_mapping_raw_find_version(int $id): ?array { return process_mapping_find(process_mapping_raw_versions(), $id); }
function process_mapping_raw_find_step(int $id): ?array { return process_mapping_find(process_mapping_raw_steps(), $id); }
function process_mapping_raw_find_instance(int $id): ?array { return process_mapping_find(process_mapping_raw_instances(), $id); }
function process_mapping_raw_find_step_instance(int $id): ?array { return process_mapping_find(process_mapping_raw_step_instances(), $id); }

function process_mapping_assert_version_mutable(int $versionId): array
{
    $version = process_mapping_raw_find_version($versionId);
    if (!$version) throw new RuntimeException('Process version not found.');
    if (($version['status'] ?? '') === 'published' || !empty($version['locked_at'])) {
        throw new RuntimeException('Published process versions are permanently locked; create a new draft version.');
    }
    return $version;
}
