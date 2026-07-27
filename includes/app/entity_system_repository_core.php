<?php
declare(strict_types=1);

function entity_system_tables(): array
{
    return [
        'business_entities',
        'business_entity_relationships',
        'business_entity_company_bindings',
        'business_entity_module_profiles',
        'business_entity_data_authorities',
        'business_entity_access_scopes',
        'business_entity_templates',
        'business_entity_template_applications',
        'integration_connections',
        'integration_entity_bindings',
        'external_id_mappings',
        'integration_sync_runs',
        'integration_events',
        'integration_conflicts',
        'business_entity_events',
    ];
}

function entity_system_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo = production_database_connection();
    if (!$pdo) return false;
    try {
        $names = entity_system_tables();
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables'
            .' WHERE table_schema=DATABASE() AND table_name IN ('
            .implode(',', array_fill(0, count($names), '?')).')'
        );
        $statement->execute($names);
        return (int)$statement->fetchColumn() === count($names);
    } catch (Throwable) {
        return false;
    }
}

function entity_system_require_tables(): void
{
    if (!entity_system_tables_ready()) {
        throw new RuntimeException('Import the Section 24 migration before using Production Data entity or integration writes.');
    }
}

function entity_system_demo_collection(string $key, callable $seed): array
{
    if (!isset($_SESSION['gruber_demo_state'][$key])) {
        $_SESSION['gruber_demo_state'][$key] = $seed();
    }
    return array_values($_SESSION['gruber_demo_state'][$key]);
}

function entity_system_demo_save(string $key, array $record, callable $seed): array
{
    $rows = entity_system_demo_collection($key, $seed);
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

function entity_system_rows(string $table, string $key, callable $seed, string $order = 'id'): array
{
    if (data_is_demo()) return entity_system_demo_collection($key, $seed);
    if (!entity_system_tables_ready()) return [];
    return production_database_connection()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();
}

function entity_system_row_effective(array $row): bool
{
    if (($row['status'] ?? 'active') !== 'active') return false;
    $today = date('Y-m-d');
    if (!empty($row['effective_from']) && (string)$row['effective_from'] > $today) return false;
    if (!empty($row['effective_to']) && (string)$row['effective_to'] < $today) return false;
    return true;
}

function entity_system_all_entities(): array
{
    return entity_system_rows('business_entities', 'business_entities', 'entity_system_demo_entities', 'parent_entity_id,id');
}

function entity_system_raw_bindings(): array
{
    return entity_system_rows(
        'business_entity_company_bindings',
        'business_entity_company_bindings',
        'entity_system_demo_bindings'
    );
}

function entity_system_raw_relationships(): array
{
    return entity_system_rows(
        'business_entity_relationships',
        'business_entity_relationships',
        'entity_system_demo_relationships'
    );
}

function entity_system_raw_module_profiles(): array
{
    return entity_system_rows(
        'business_entity_module_profiles',
        'business_entity_module_profiles',
        'entity_system_demo_module_profiles'
    );
}

function entity_system_raw_authorities(): array
{
    return entity_system_rows(
        'business_entity_data_authorities',
        'business_entity_data_authorities',
        'entity_system_demo_authorities'
    );
}

function entity_system_raw_access_scopes(): array
{
    return entity_system_rows(
        'business_entity_access_scopes',
        'business_entity_access_scopes',
        'entity_system_demo_access_scopes'
    );
}
