<?php
declare(strict_types=1);

function entity_system_can(string $capability, ?array $user = null): bool
{
    $user ??= current_user();
    if (!$user) return false;
    if (can('entities.'.$capability, $user)) return true;
    if (demo_mode_active()) {
        $roles = current_role_codes($user);
        $demoCapabilities = [
            'system_administrator'=>['view','create','edit','apply','review','administer','export'],
            'executive'=>['view','review','export'],
            'company_administrator'=>['view','edit','review','export'],
            'procurement_manager'=>['view','review','export'],
            'data_contributor'=>['view'],
            'reviewer'=>['view','review'],
            'read_only'=>['view'],
        ];
        foreach ($roles as $role) if (in_array($capability, $demoCapabilities[$role] ?? [], true)) return true;
    }
    $fallback = match ($capability) {
        'view' => 'companies.view',
        'create', 'edit', 'apply', 'review', 'administer' => 'companies.administer',
        'export' => 'reports.export',
        default => '',
    };
    return $fallback !== '' && can($fallback, $user);
}

function entity_system_require_capability(string $capability): void
{
    if (!entity_system_can($capability)) {
        throw new RuntimeException('You do not have permission to '.$capability.' business entities.');
    }
}

function entity_system_require_enterprise_context(): void
{
    if (current_company_id() !== 'enterprise' || !can_use_enterprise_view()) {
        throw new RuntimeException('Enterprise organization templates can only be governed from Enterprise View.');
    }
}

function entity_system_active_user(int $userId): ?array
{
    foreach (data_collection('users') as $user) {
        if ((int)$user['id'] === $userId && ($user['status'] ?? '') === 'active') return $user;
    }
    return null;
}

function entity_system_assert_three_person_governance(int $preparedBy, int $reviewedBy, int $approvedBy): void
{
    if (count(array_unique([$preparedBy, $reviewedBy, $approvedBy])) !== 3) {
        throw new RuntimeException('Entity-template preparation, review, and approval require three different active users.');
    }
    foreach ([$preparedBy, $reviewedBy, $approvedBy] as $userId) {
        if (!entity_system_active_user($userId)) {
            throw new RuntimeException('Every entity-template governance participant must be an active internal user.');
        }
    }
}

function entity_system_carryover_snapshot(): array
{
    $companies = data_collection('companies');
    $companyIds = array_map('intval', array_column($companies, 'id'));
    $users = data_collection('users');
    $suppliers = data_collection('suppliers');
    $items = data_collection('items');
    $contracts = data_collection('contracts');
    $locations = data_collection('locations');
    $departments = data_collection('departments');
    $purchaseOrders = data_collection('purchase_orders');
    return [
        'companies'=>count($companies),
        'company_ids'=>$companyIds,
        'locations'=>count($locations),
        'departments'=>count($departments),
        'purchase_orders'=>count($purchaseOrders),
        'user_memberships'=>array_sum(array_map(
            static fn(array $user): int => count(array_unique(array_map('intval', $user['company_ids'] ?? []))),
            $users
        )),
        'supplier_company_links'=>array_sum(array_map(
            static fn(array $supplier): int => count(array_unique(array_map('intval', $supplier['company_ids'] ?? []))),
            $suppliers
        )),
        'item_company_links'=>array_sum(array_map(static function (array $item): int {
            $ids = $item['company_ids'] ?? (($item['company_id'] ?? null) !== null ? [(int)$item['company_id']] : []);
            return count(array_unique(array_map('intval', $ids)));
        }, $items)),
        'enterprise_contracts'=>count(array_filter($contracts, static fn(array $contract): bool => ($contract['company_id'] ?? null) === null)),
        'company_contracts'=>count(array_filter($contracts, static fn(array $contract): bool => ($contract['company_id'] ?? null) !== null)),
    ];
}

function entity_system_validate_current_relationships(?array $scopeCompanyIds = null): array
{
    $allCompanies = data_collection('companies');
    $valid = array_fill_keys(array_map('intval', array_column($allCompanies, 'id')), true);
    $scopeCompanyIds ??= entity_system_context_company_ids();
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) {
        $scopeCompanyIds = array_map('intval', array_column($allCompanies, 'id'));
    }
    $scope = array_fill_keys(array_map('intval', $scopeCompanyIds), true);
    $companies = array_values(array_filter($allCompanies, static fn(array $company): bool => isset($scope[(int)$company['id']])));
    $errors = [];
    $warnings = [];
    $codes = [];
    foreach ($companies as $company) {
        $id = (int)$company['id'];
        $code = strtoupper(trim((string)($company['code'] ?? '')));
        if ($id <= 0) $errors[] = 'A company has an invalid canonical ID.';
        if ($code === '') $errors[] = 'Company '.$id.' has no stable code.';
        if (isset($codes[$code])) $errors[] = 'Company code '.$code.' is duplicated.';
        $codes[$code] = true;
    }
    foreach (data_collection('users') as $user) {
        foreach (array_map('intval', $user['company_ids'] ?? []) as $companyId) {
            if (!isset($valid[$companyId])) $errors[] = 'User '.($user['name'] ?? $user['id']).' references missing company '.$companyId.'.';
        }
    }
    foreach (data_collection('suppliers') as $supplier) {
        $supplierCompanyIds = array_map('intval', $supplier['company_ids'] ?? []);
        if ($scope && !array_intersect($supplierCompanyIds, array_keys($scope))) continue;
        foreach ($supplierCompanyIds as $companyId) {
            if (!isset($valid[$companyId])) $errors[] = 'Supplier '.($supplier['name'] ?? $supplier['id']).' references missing company '.$companyId.'.';
        }
    }
    foreach (data_collection('items') as $item) {
        $ids = $item['company_ids'] ?? (($item['company_id'] ?? null) !== null ? [(int)$item['company_id']] : []);
        $ids = array_map('intval', $ids);
        if ($ids && $scope && !array_intersect($ids, array_keys($scope))) continue;
        if (!$ids) $warnings[] = 'Item '.($item['item_number'] ?? $item['id']).' is enterprise shared and requires an authority policy.';
        foreach ($ids as $companyId) {
            if (!isset($valid[$companyId])) $errors[] = 'Item '.($item['item_number'] ?? $item['id']).' references missing company '.$companyId.'.';
        }
    }
    foreach (data_collection('contracts') as $contract) {
        $companyId = $contract['company_id'] ?? null;
        if ($companyId !== null && $scope && !isset($scope[(int)$companyId])) continue;
        if ($companyId === null && current_company_id() === 'enterprise') {
            $warnings[] = 'Contract '.($contract['contract_number'] ?? $contract['id']).' is enterprise scoped and will remain enterprise governed.';
        }
    }
    return [
        'valid'=>$errors === [],
        'errors'=>array_values(array_unique($errors)),
        'warnings'=>array_values(array_unique($warnings)),
    ];
}

function entity_system_metrics(): array
{
    $entities = entity_system_entities();
    $connections = entity_system_connections();
    $mappings = entity_system_external_mappings();
    $conflicts = entity_system_conflicts();
    $carryover = entity_system_carryover_snapshot();
    return [
        'entities'=>count($entities),
        'operating_entities'=>count(array_filter($entities, static fn(array $entity): bool => $entity['entity_type'] === 'operating_company')),
        'shared_services'=>count(array_filter($entities, static fn(array $entity): bool => $entity['entity_type'] === 'shared_service')),
        'relationships'=>count(entity_system_relationships()),
        'applications'=>count(entity_system_applications()),
        'active_connections'=>count(array_filter($connections, static fn(array $connection): bool => $connection['status'] === 'active')),
        'pending_mappings'=>count(array_filter($mappings, static fn(array $mapping): bool => $mapping['mapping_status'] !== 'validated')),
        'open_conflicts'=>count(array_filter($conflicts, static fn(array $conflict): bool => !in_array($conflict['status'], ['resolved','accepted'], true))),
        'preserved_company_ids'=>count($carryover['company_ids']) === count(array_unique($carryover['company_ids']))
            && count($carryover['company_ids']) === 6,
    ];
}

function entity_system_find_by_code(string $code): ?array
{
    foreach (entity_system_all_entities() as $entity) {
        if (strcasecmp((string)$entity['entity_code'], $code) === 0) return $entity;
    }
    return null;
}

function entity_system_service_name(string $service): string
{
    return match ($service) {
        'procurement' => 'Shared Procurement',
        'accounts_payable' => 'Shared Finance & Accounts Payable',
        'inventory_network' => 'Shared Inventory Network',
        'integration_hub' => 'Integration Hub',
        'governance' => 'Enterprise Governance',
        default => status_label($service),
    };
}

function entity_system_authority_entity(string $mode, array $entityMap, int $companyEntityId): int
{
    return match ($mode) {
        'enterprise','external_governed','internal_governed','bidirectional' => $entityMap['enterprise'],
        'procurement' => $entityMap['procurement'] ?? $entityMap['enterprise'],
        'finance' => $entityMap['accounts_payable'] ?? $entityMap['enterprise'],
        default => $companyEntityId,
    };
}
