<?php
declare(strict_types=1);

function demo_empty_state(): array
{
    return [
        'meta' => ['version' => '2.0-demo-admin', 'sample_notice' => 'Demo Mode is not active.'],
        'companies' => [], 'roles' => [], 'users' => [], 'categories' => [],
        'discovery_assignments' => [], 'discovery_responses' => [], 'suppliers' => [],
        'supplier_contacts' => [], 'supplier_company_relationships' => [], 'contracts' => [],
        'items' => [], 'purchase_orders' => [], 'purchase_order_lines' => [],
        'open_commitments' => [], 'inventory_locations' => [], 'inventory_snapshots' => [],
        'inventory_aging' => [], 'savings_opportunities' => [], 'supplier_scorecards' => [],
        'data_quality_exceptions' => [], 'notifications' => [], 'workflow_approvals' => [],
        'comments' => [], 'audit_events' => [], 'import_jobs' => [],
        'import_validation_errors' => [], 'import_receipts' => [], 'sessions' => [],
        'access_requests' => [], 'security_events' => [],
        'settings' => [
            'platform_name' => app_config()['app']['name'] ?? 'Gruber Procurement Intelligence',
            'support_contact' => app_config()['app']['support_email'] ?? 'support@example.com',
            'default_timezone' => app_config()['app']['timezone'] ?? 'America/Phoenix',
            'date_format' => 'M j, Y', 'currency' => 'USD',
            'email_sender' => app_config()['app']['support_email'] ?? 'support@example.com',
            'session_lifetime' => (int) (app_config()['security']['session_lifetime_minutes'] ?? 120),
            'password_reset_lifetime' => (int) (app_config()['security']['password_reset_lifetime_minutes'] ?? 60),
            'import_row_limit' => 25000, 'upload_limit_mb' => 25,
            'allowed_file_types' => ['csv', 'xlsx'],
            'notification_defaults' => ['email' => true, 'in_app' => true],
            'approval_requirements' => ['savings_over' => 25000, 'po_over' => 100000],
            'data_retention_days' => 2555,
            'demo_mode_available' => (bool) (app_config()['app']['demo_enabled'] ?? true),
            'maintenance_mode' => false, 'agent_workspace_access' => true,
            'approved_data_only_agent' => true, 'password_min_length' => 12,
            'password_require_mixed_case' => true, 'password_require_number' => true,
            'password_require_symbol' => true,
            'login_attempt_limit' => (int) (app_config()['security']['max_login_attempts'] ?? 5),
            'lockout_minutes' => (int) (app_config()['security']['lockout_minutes'] ?? 15),
        ],
    ];
}

function demo_initialize(bool $force = false): void
{
    if (!$force && !demo_mode_active() && !defined('GRUBER_DEMO_SELECTOR')) {
        return;
    }
    if ($force || empty($_SESSION['gruber_demo_state']) || !is_array($_SESSION['gruber_demo_state'])) {
        $_SESSION['gruber_demo_state'] = demo_default_state();
    }
}

function &demo_state(): array
{
    if (!demo_mode_active() && !defined('GRUBER_DEMO_SELECTOR')) {
        static $emptyState;
        $emptyState = demo_empty_state();
        return $emptyState;
    }
    demo_initialize();
    return $_SESSION['gruber_demo_state'];
}

function demo_collection(string $name): array
{
    $state = demo_state();
    $records = $state[$name] ?? [];
    return is_array($records) ? $records : [];
}

function demo_replace_collection(string $name, array $records): void
{
    $state =& demo_state();
    $state[$name] = array_values($records);
}

function demo_find(string $collection, int|string $id): ?array
{
    return array_find_by_id(demo_collection($collection), $id);
}

function demo_find_by(string $collection, string $field, mixed $value): ?array
{
    foreach (demo_collection($collection) as $record) {
        if ((string) ($record[$field] ?? '') === (string) $value) {
            return $record;
        }
    }
    return null;
}

function demo_next_id(string $collection): int
{
    $ids = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), demo_collection($collection));
    return ($ids ? max($ids) : 0) + 1;
}

function demo_upsert(string $collection, array $record): array
{
    $records = demo_collection($collection);
    if (empty($record['id'])) {
        $record['id'] = demo_next_id($collection);
        $records[] = $record;
    } else {
        $found = false;
        foreach ($records as $index => $existing) {
            if ((int) ($existing['id'] ?? 0) === (int) $record['id']) {
                $records[$index] = array_replace($existing, $record);
                $record = $records[$index];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $records[] = $record;
        }
    }
    demo_replace_collection($collection, $records);
    return $record;
}

function demo_delete(string $collection, int $id): bool
{
    $records = demo_collection($collection);
    $before = count($records);
    $records = array_values(array_filter(
        $records,
        static fn(array $row): bool => (int) ($row['id'] ?? 0) !== $id
    ));
    demo_replace_collection($collection, $records);
    return count($records) < $before;
}

function demo_company_name(int|string|null $id): string
{
    if ($id === null || $id === '' || $id === 'enterprise') {
        return 'Enterprise View';
    }
    return demo_find('companies', (int) $id)['name'] ?? 'Unknown company';
}

function demo_user_name(int|string|null $id): string
{
    if (!$id) {
        return 'Unassigned';
    }
    return demo_find('users', (int) $id)['name'] ?? 'Unknown user';
}

function demo_supplier_name(int|string|null $id): string
{
    if (!$id) {
        return 'Unknown supplier';
    }
    return demo_find('suppliers', (int) $id)['name'] ?? 'Unknown supplier';
}

function demo_item_name(int|string|null $id): string
{
    if (!$id) {
        return 'Unknown item';
    }
    $item = demo_find('items', (int) $id);
    return $item ? ($item['item_number'] . ' · ' . $item['description']) : 'Unknown item';
}

function demo_role_name(string $code): string
{
    return demo_find_by('roles', 'code', $code)['name'] ?? status_label($code);
}

function demo_record_visible(array $record, ?array $user = null): bool
{
    $user ??= current_user();
    if (!$user) {
        return false;
    }

    $selected = current_company_id();
    $permitted = permitted_company_ids($user);
    $recordCompany = $record['company_id'] ?? null;
    $recordCompanies = $record['company_ids'] ?? [];

    if ($selected === 'enterprise') {
        if (can_use_enterprise_view($user)) {
            return true;
        }
        if ($recordCompany !== null) {
            return in_array((int) $recordCompany, $permitted, true);
        }
        if ($recordCompanies) {
            return (bool) array_intersect(array_map('intval', $recordCompanies), $permitted);
        }
        return true;
    }

    $selectedId = (int) $selected;
    if (!in_array($selectedId, $permitted, true)) {
        return false;
    }
    if ($recordCompany !== null) {
        return (int) $recordCompany === $selectedId;
    }
    if ($recordCompanies) {
        return in_array($selectedId, array_map('intval', $recordCompanies), true);
    }
    return true;
}

function demo_visible_collection(string $collection, ?array $user = null): array
{
    return array_values(array_filter(
        demo_collection($collection),
        static fn(array $record): bool => demo_record_visible($record, $user)
    ));
}

function demo_add_audit(
    string $module,
    string $action,
    string $entityType,
    int|string|null $entityId,
    mixed $before = null,
    mixed $after = null,
    int|string|null $companyId = null
): void {
    $user = current_user();
    $record = [
        'id' => demo_next_id('audit_events'),
        'user_id' => $user['id'] ?? null,
        'company_id' => $companyId === 'enterprise' ? null : $companyId,
        'module' => $module,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'before' => $before,
        'after' => $after,
        'ip_address' => current_ip(),
        'created_at' => date('Y-m-d H:i:s'),
        'immutable' => true,
    ];
    $records = demo_collection('audit_events');
    array_unshift($records, $record);
    demo_replace_collection('audit_events', $records);
}

function demo_start_session(int $userId): bool
{
    // Enable the isolated environment before reading its session dataset. This
    // does not authenticate against or write to the production database.
    $_SESSION['gruber_demo_mode'] = true;
    demo_initialize();
    $user = demo_find('users', $userId);
    if (!$user || ($user['status'] ?? '') !== 'active') {
        unset($_SESSION['gruber_demo_mode']);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['gruber_demo_user_id'] = $userId;
    $_SESSION['gruber_demo_company_id'] = can_use_enterprise_view($user)
        ? 'enterprise'
        : (int) ($user['primary_company_id'] ?? 1);

    $sessions = demo_collection('sessions');
    foreach ($sessions as &$session) {
        $session['current'] = false;
    }
    unset($session);
    $sessions[] = [
        'id' => demo_next_id('sessions'),
        'user_id' => $userId,
        'ip_address' => current_ip(),
        'device' => current_user_agent(),
        'started_at' => date('Y-m-d H:i:s'),
        'last_activity' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', time() + 7200),
        'current' => true,
        'status' => 'active',
    ];
    demo_replace_collection('sessions', $sessions);
    demo_add_audit('Demo', 'demo_login', 'user', $userId, null, ['role_codes' => $user['role_codes']], current_company_id());
    return true;
}

function demo_restore_defaults(bool $preserveIdentity = true): void
{
    $userId = (int) ($_SESSION['gruber_demo_user_id'] ?? 1);
    $companyId = $_SESSION['gruber_demo_company_id'] ?? 'enterprise';
    $_SESSION['gruber_demo_state'] = demo_default_state();
    if ($preserveIdentity) {
        $_SESSION['gruber_demo_user_id'] = $userId;
        $_SESSION['gruber_demo_company_id'] = $companyId;
        demo_add_audit('Demo', 'dataset_restored', 'environment', null, null, ['version' => '2.0-demo-admin'], $companyId);
    }
}

function demo_dashboard_metrics(): array
{
    $users = demo_collection('users');
    $visiblePos = demo_visible_collection('purchase_orders');
    $visibleInventory = demo_visible_collection('inventory_snapshots');
    $visibleSavings = demo_visible_collection('savings_opportunities');
    $visibleApprovals = demo_visible_collection('workflow_approvals');

    return [
        'spend' => array_sum(array_column($visiblePos, 'total_amount')),
        'open_commitments' => array_sum(array_map(
            static fn(array $po): float => in_array($po['status'], ['open','past_due','partially_received'], true) ? (float) $po['total_amount'] : 0,
            $visiblePos
        )),
        'inventory_value' => array_sum(array_column($visibleInventory, 'value')),
        'savings_pipeline' => array_sum(array_column($visibleSavings, 'annualized_value')),
        'pending_approvals' => count(array_filter($visibleApprovals, static fn(array $a): bool => $a['status'] === 'pending')),
        'data_exceptions' => count(array_filter(demo_visible_collection('data_quality_exceptions'), static fn(array $e): bool => $e['status'] !== 'resolved')),
        'active_users' => count(array_filter($users, static fn(array $u): bool => $u['status'] === 'active')),
    ];
}

function demo_admin_metrics(): array
{
    $users = demo_collection('users');
    return [
        'total_users' => count($users),
        'active_users' => count(array_filter($users, static fn(array $u): bool => $u['status'] === 'active')),
        'suspended_users' => count(array_filter($users, static fn(array $u): bool => $u['status'] === 'suspended')),
        'pending_access_requests' => count(array_filter(demo_collection('access_requests'), static fn(array $r): bool => $r['status'] === 'pending')),
        'active_sessions' => count(array_filter(demo_collection('sessions'), static fn(array $s): bool => $s['status'] === 'active')),
        'companies' => count(demo_collection('companies')),
        'roles' => count(array_filter(demo_collection('roles'), static fn(array $r): bool => $r['status'] === 'active')),
        'failed_sign_ins' => count(array_filter(demo_collection('security_events'), static fn(array $e): bool => $e['event'] === 'failed_sign_in')),
        'password_resets' => count(array_filter(demo_collection('security_events'), static fn(array $e): bool => $e['event'] === 'password_reset_requested')),
        'data_completeness' => (int) round(array_sum(array_column(demo_collection('companies'), 'completion')) / max(1, count(demo_collection('companies')))),
        'pending_approvals' => count(array_filter(demo_collection('workflow_approvals'), static fn(array $a): bool => $a['status'] === 'pending')),
        'audit_events' => count(demo_collection('audit_events')),
    ];
}
