<?php
declare(strict_types=1);

function entity_system_context_company_ids(?array $user = null): array
{
    $user ??= current_user();
    if (!$user) return [];
    $selected = current_company_id();
    if ($selected === 'enterprise') return can_use_enterprise_view($user) ? permitted_company_ids($user) : [];
    $selectedId = (int)$selected;
    return $selectedId > 0 && in_array($selectedId, permitted_company_ids($user), true) ? [$selectedId] : [];
}

function entity_system_effective_entity_ids(?array $user = null, string $moduleCode = '*'): array
{
    $user ??= current_user();
    if (!$user) return [];
    $entities = entity_system_all_entities();
    if (current_company_id() === 'enterprise' && can_use_enterprise_view($user)) return array_values(array_unique(array_map('intval', array_column($entities, 'id'))));
    $companyIds = entity_system_context_company_ids($user);
    if (!$companyIds) return [];
    $allowed = [];
    foreach (entity_system_raw_bindings() as $binding) {
        if (entity_system_row_effective($binding) && in_array((int)$binding['company_id'], $companyIds, true)) $allowed[(int)$binding['entity_id']] = true;
    }
    foreach (entity_system_raw_access_scopes() as $scope) {
        if (!entity_system_row_effective($scope) || (int)$scope['user_id'] !== (int)$user['id']) continue;
        if (!in_array((string)$scope['module_code'], ['*', $moduleCode], true)) continue;
        $entityId = (int)$scope['entity_id'];
        foreach ($entities as $entity) {
            if ((int)$entity['id'] !== $entityId) continue;
            $companyId = (int)($entity['company_id'] ?? 0);
            if ($companyId === 0 || in_array($companyId, $companyIds, true)) $allowed[$entityId] = true;
            break;
        }
    }
    foreach (entity_system_raw_relationships() as $relationship) {
        if (!entity_system_row_effective($relationship) || !isset($allowed[(int)$relationship['to_entity_id']])) continue;
        if (in_array((string)$relationship['relationship_type'], ['provides_service','governs'], true)) $allowed[(int)$relationship['from_entity_id']] = true;
    }
    foreach (entity_system_raw_authorities() as $authority) {
        if (entity_system_row_effective($authority) && isset($allowed[(int)$authority['entity_id']])) $allowed[(int)$authority['authority_entity_id']] = true;
    }
    foreach (entity_system_raw_module_profiles() as $profile) {
        if (entity_system_row_effective($profile) && isset($allowed[(int)$profile['entity_id']]) && !empty($profile['source_entity_id'])) $allowed[(int)$profile['source_entity_id']] = true;
    }
    $byId = [];
    foreach ($entities as $entity) $byId[(int)$entity['id']] = $entity;
    foreach (array_keys($allowed) as $entityId) {
        $cursor = $entityId;
        $guard = 0;
        while (isset($byId[$cursor]) && ++$guard < 20) {
            $parentId = (int)($byId[$cursor]['parent_entity_id'] ?? 0);
            if ($parentId <= 0) break;
            $allowed[$parentId] = true;
            $cursor = $parentId;
        }
    }
    return array_map('intval', array_keys($allowed));
}

function entity_system_data_scope_entity_ids(?array $user = null, string $moduleCode = '*'): array
{
    $ids = entity_system_effective_entity_ids($user, $moduleCode);
    if (current_company_id() === 'enterprise' && can_use_enterprise_view($user ?? current_user())) return $ids;
    $allowed = array_fill_keys($ids, true);
    $out = [];
    foreach (entity_system_all_entities() as $entity) {
        $id = (int)$entity['id'];
        if (!isset($allowed[$id]) || ($entity['entity_type'] ?? '') === 'enterprise_group') continue;
        $out[] = $id;
    }
    return $out;
}

function entity_system_entity_visible(array $entity): bool { return in_array((int)($entity['id'] ?? 0), entity_system_effective_entity_ids(), true); }
function entity_system_user_can_access_entity(int $entityId, string $moduleCode = '*'): bool { return $entityId > 0 && in_array($entityId, entity_system_effective_entity_ids(null, $moduleCode), true); }

function entity_system_assert_entity_access(int $entityId, string $moduleCode = '*', bool $requireActive = false): array
{
    $entity = null;
    foreach (entity_system_all_entities() as $candidate) if ((int)$candidate['id'] === $entityId) { $entity = $candidate; break; }
    if (!$entity || !entity_system_user_can_access_entity($entityId, $moduleCode)) throw new RuntimeException('The selected business entity is outside the active company scope.');
    if ($requireActive && !entity_system_row_effective($entity)) throw new RuntimeException('Disabled, archived, or future-dated entities cannot receive new process activity.');
    return $entity;
}

function entity_system_entities(): array { return array_values(array_filter(entity_system_all_entities(), 'entity_system_entity_visible')); }
function entity_system_relationships(): array
{
    $visible = array_fill_keys(entity_system_effective_entity_ids(), true);
    return array_values(array_filter(entity_system_raw_relationships(), static fn(array $row): bool => isset($visible[(int)$row['from_entity_id']]) && isset($visible[(int)$row['to_entity_id']])));
}
function entity_system_bindings(): array
{
    $companyIds = entity_system_context_company_ids();
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) $companyIds = permitted_company_ids();
    return array_values(array_filter(entity_system_raw_bindings(), static fn(array $row): bool => entity_system_row_effective($row) && in_array((int)$row['company_id'], $companyIds, true)));
}
function entity_system_filter_by_entity(array $rows, string $field = 'entity_id', bool $dataScope = false): array
{
    $allowed = array_fill_keys($dataScope ? entity_system_data_scope_entity_ids() : entity_system_effective_entity_ids(), true);
    return array_values(array_filter($rows, static fn(array $row): bool => isset($allowed[(int)($row[$field] ?? 0)])));
}
function entity_system_module_profiles(): array { return entity_system_filter_by_entity(entity_system_raw_module_profiles()); }
function entity_system_authorities(): array { return entity_system_filter_by_entity(entity_system_raw_authorities()); }
function entity_system_access_scopes(): array
{
    $rows = entity_system_filter_by_entity(entity_system_raw_access_scopes());
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) return $rows;
    $visibleUsers = array_fill_keys(array_map('intval', array_column(data_admin_visible_users(), 'id')), true);
    return array_values(array_filter($rows, static fn(array $row): bool => isset($visibleUsers[(int)$row['user_id']])));
}
function entity_system_applications(): array
{
    $visibleApplicationIds = [];
    foreach (entity_system_entities() as $entity) if (!empty($entity['application_id'])) $visibleApplicationIds[(int)$entity['application_id']] = true;
    $rows = entity_system_rows('business_entity_template_applications','business_entity_template_applications','entity_system_demo_applications','created_at DESC,id DESC');
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) return $rows;
    return array_values(array_filter($rows, static fn(array $row): bool => isset($visibleApplicationIds[(int)$row['id']])));
}
function entity_system_integration_bindings(): array { return entity_system_filter_by_entity(entity_system_rows('integration_entity_bindings','integration_entity_bindings','entity_system_demo_integration_bindings','id DESC'),'entity_id',true); }
function entity_system_connections(): array
{
    $connectionIds = array_fill_keys(array_map('intval', array_column(entity_system_integration_bindings(), 'connection_id')), true);
    $rows = entity_system_rows('integration_connections','integration_connections','entity_system_demo_connections','id DESC');
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) return $rows;
    return array_values(array_filter($rows, static fn(array $row): bool => isset($connectionIds[(int)$row['id']])));
}
function entity_system_external_mappings(): array { return entity_system_filter_by_entity(entity_system_rows('external_id_mappings','external_id_mappings','entity_system_demo_external_mappings','id DESC'),'entity_id',true); }
function entity_system_sync_runs(): array
{
    $rows = entity_system_rows('integration_sync_runs','integration_sync_runs','entity_system_demo_sync_runs','id DESC');
    $ids = array_fill_keys(entity_system_data_scope_entity_ids(), true);
    return array_values(array_filter($rows, static function(array $row) use($ids): bool { if (empty($row['entity_id'])) return current_company_id() === 'enterprise' && can_use_enterprise_view(); return isset($ids[(int)$row['entity_id']]); }));
}
function entity_system_integration_events(): array { return entity_system_filter_by_entity(entity_system_rows('integration_events','integration_events','entity_system_demo_integration_events','occurred_at DESC,id DESC'),'entity_id',true); }
function entity_system_conflicts(): array { return entity_system_filter_by_entity(entity_system_rows('integration_conflicts','integration_conflicts','entity_system_demo_conflicts','id DESC'),'entity_id',true); }
function entity_system_events(): array { return entity_system_filter_by_entity(entity_system_rows('business_entity_events','business_entity_events','entity_system_demo_events','created_at DESC,id DESC')); }
function entity_system_find(array $rows, int $id): ?array { foreach ($rows as $row) if ((int)$row['id'] === $id) return $row; return null; }
function entity_system_find_entity(int $id): ?array { return entity_system_find(entity_system_entities(), $id); }
function entity_system_find_application(int $id): ?array { return entity_system_find(entity_system_applications(), $id); }
