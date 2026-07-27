<?php
declare(strict_types=1);

function permission_catalog(): array
{
    return [
        'platform' => ['view', 'administer'],
        'users' => ['view', 'create', 'edit', 'delete', 'assign', 'administer'],
        'roles' => ['view', 'create', 'edit', 'delete', 'assign', 'administer'],
        'companies' => ['view', 'create', 'edit', 'delete', 'assign', 'administer'],
        'discovery' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'suppliers' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'supplier_portal' => ['view', 'invite', 'review', 'export', 'administer'],
        'accounts_payable' => ['view', 'create', 'edit', 'submit', 'review', 'approve', 'execute', 'reconcile', 'close', 'export'],
        'items' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'purchase_orders' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'inventory' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'savings' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'strategy' => ['view', 'create', 'edit', 'assign', 'submit', 'review', 'approve', 'export'],
        'scorecards' => ['view', 'create', 'edit', 'delete', 'assign', 'submit', 'review', 'approve', 'export'],
        'imports' => ['view', 'create', 'edit', 'delete', 'submit', 'review', 'approve', 'export', 'administer'],
        'approvals' => ['view', 'submit', 'review', 'approve', 'administer'],
        'reports' => ['view', 'export'],
        'audit' => ['view', 'export', 'administer'],
        'agent' => ['view', 'administer'],
        'settings' => ['view', 'edit', 'administer'],
        'security' => ['view', 'administer'],
    ];
}

function all_permissions(): array
{
    $out = [];
    foreach (permission_catalog() as $area => $actions) {
        foreach ($actions as $action) {
            $out[] = $area . '.' . $action;
        }
    }
    return $out;
}

function role_permission_defaults(): array
{
    $all = all_permissions();

    return [
        'system_administrator' => $all,
        'executive' => [
            'platform.view', 'companies.view', 'discovery.view', 'suppliers.view',
            'supplier_portal.view', 'supplier_portal.export', 'accounts_payable.view',
            'accounts_payable.approve', 'accounts_payable.close', 'accounts_payable.export', 'items.view',
            'purchase_orders.view', 'inventory.view', 'savings.view', 'strategy.view',
            'strategy.export', 'strategy.approve', 'scorecards.view', 'imports.view',
            'approvals.view', 'reports.view', 'reports.export', 'audit.view', 'agent.view',
            'settings.view',
        ],
        'company_administrator' => [
            'platform.view', 'users.view', 'users.create', 'users.edit', 'users.assign',
            'roles.view', 'roles.assign', 'companies.view', 'companies.edit',
            'discovery.view', 'discovery.create', 'discovery.edit', 'discovery.assign',
            'discovery.submit', 'discovery.review', 'discovery.approve', 'discovery.export',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.assign',
            'suppliers.submit', 'suppliers.review', 'suppliers.approve', 'suppliers.export',
            'supplier_portal.view', 'supplier_portal.invite', 'supplier_portal.review',
            'supplier_portal.export', 'supplier_portal.administer',
            'accounts_payable.view', 'accounts_payable.create', 'accounts_payable.edit',
            'accounts_payable.submit', 'accounts_payable.review', 'accounts_payable.approve',
            'accounts_payable.execute', 'accounts_payable.reconcile', 'accounts_payable.close',
            'accounts_payable.export',
            'items.view', 'items.create', 'items.edit', 'items.assign', 'items.submit',
            'items.review', 'items.approve', 'items.export',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit',
            'purchase_orders.assign', 'purchase_orders.submit', 'purchase_orders.review',
            'purchase_orders.approve', 'purchase_orders.export',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.assign',
            'inventory.submit', 'inventory.review', 'inventory.approve', 'inventory.export',
            'savings.view', 'savings.create', 'savings.edit', 'savings.assign',
            'savings.submit', 'savings.review', 'savings.approve', 'savings.export',
            'strategy.view', 'strategy.create', 'strategy.edit', 'strategy.assign',
            'strategy.submit', 'strategy.review', 'strategy.approve', 'strategy.export',
            'scorecards.view', 'scorecards.create', 'scorecards.edit', 'scorecards.submit',
            'scorecards.review', 'scorecards.approve', 'scorecards.export',
            'imports.view', 'imports.create', 'imports.edit', 'imports.submit',
            'imports.review', 'imports.approve', 'imports.export',
            'approvals.view', 'approvals.submit', 'approvals.review', 'approvals.approve',
            'reports.view', 'reports.export', 'audit.view', 'agent.view', 'settings.view',
        ],
        'procurement_manager' => [
            'platform.view', 'companies.view', 'discovery.view', 'discovery.edit',
            'discovery.assign', 'discovery.submit', 'discovery.review', 'discovery.approve',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.assign',
            'suppliers.submit', 'suppliers.review', 'suppliers.approve', 'suppliers.export',
            'supplier_portal.view', 'supplier_portal.invite', 'supplier_portal.review',
            'supplier_portal.export', 'supplier_portal.administer',
            'accounts_payable.view', 'accounts_payable.create', 'accounts_payable.edit',
            'accounts_payable.submit', 'accounts_payable.review', 'accounts_payable.export',
            'items.view', 'items.create', 'items.edit', 'items.assign', 'items.submit',
            'items.review', 'items.approve', 'items.export',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit',
            'purchase_orders.assign', 'purchase_orders.submit', 'purchase_orders.review',
            'purchase_orders.approve', 'purchase_orders.export',
            'inventory.view', 'inventory.edit', 'inventory.assign', 'inventory.review',
            'inventory.approve', 'inventory.export',
            'savings.view', 'savings.create', 'savings.edit', 'savings.assign',
            'savings.submit', 'savings.review', 'savings.approve', 'savings.export',
            'strategy.view', 'strategy.create', 'strategy.edit', 'strategy.assign',
            'strategy.submit', 'strategy.review', 'strategy.approve', 'strategy.export',
            'scorecards.view', 'scorecards.create', 'scorecards.edit', 'scorecards.submit',
            'scorecards.review', 'scorecards.approve', 'scorecards.export',
            'imports.view', 'imports.create', 'imports.edit', 'imports.submit',
            'imports.review', 'imports.approve', 'approvals.view', 'approvals.submit',
            'approvals.review', 'approvals.approve', 'reports.view', 'reports.export',
            'audit.view', 'agent.view',
        ],
        'data_contributor' => [
            'platform.view', 'companies.view', 'discovery.view', 'discovery.create',
            'discovery.edit', 'discovery.submit', 'suppliers.view', 'suppliers.create',
            'suppliers.edit', 'suppliers.submit', 'supplier_portal.view',
            'accounts_payable.view', 'accounts_payable.create', 'accounts_payable.edit',
            'accounts_payable.submit',
            'items.view', 'items.create', 'items.edit', 'items.submit',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit',
            'purchase_orders.submit', 'inventory.view', 'inventory.create', 'inventory.edit',
            'inventory.submit', 'savings.view', 'savings.create', 'savings.edit',
            'savings.submit', 'strategy.view', 'strategy.create', 'strategy.edit',
            'strategy.submit', 'scorecards.view', 'scorecards.edit', 'scorecards.submit',
            'imports.view', 'imports.create', 'imports.edit', 'imports.submit',
            'approvals.view', 'reports.view', 'agent.view',
        ],
        'reviewer' => [
            'platform.view', 'companies.view', 'discovery.view', 'discovery.review',
            'discovery.approve', 'suppliers.view', 'suppliers.review', 'suppliers.approve',
            'supplier_portal.view', 'supplier_portal.review', 'supplier_portal.export',
            'accounts_payable.view', 'accounts_payable.review', 'accounts_payable.approve',
            'accounts_payable.execute', 'accounts_payable.reconcile', 'accounts_payable.close',
            'accounts_payable.export',
            'items.view', 'items.review', 'items.approve', 'purchase_orders.view',
            'purchase_orders.review', 'purchase_orders.approve', 'inventory.view',
            'inventory.review', 'inventory.approve', 'savings.view', 'savings.review',
            'savings.approve', 'strategy.view', 'strategy.review', 'strategy.approve',
            'strategy.export', 'scorecards.view', 'scorecards.review',
            'scorecards.approve', 'imports.view', 'imports.review', 'imports.approve',
            'approvals.view', 'approvals.review', 'approvals.approve', 'reports.view',
            'audit.view', 'agent.view',
        ],
        'read_only' => [
            'platform.view', 'companies.view', 'discovery.view', 'suppliers.view',
            'supplier_portal.view', 'accounts_payable.view', 'items.view', 'purchase_orders.view',
            'inventory.view', 'savings.view', 'strategy.view', 'scorecards.view', 'imports.view',
            'approvals.view', 'reports.view', 'agent.view', 'settings.view',
        ],
    ];
}

function admin_allowed_company_modules(): array
{
    return ['discovery','suppliers','supplier_portal','accounts_payable','items','purchase_orders','inventory','savings','strategy','scorecards','imports'];
}

function admin_normalize_permissions(array $permissions): array
{
    $allowed = array_fill_keys(all_permissions(), true);
    $normalized = [];
    foreach ($permissions as $permission) {
        $permission = trim((string) $permission);
        if ($permission !== '' && isset($allowed[$permission])) {
            $normalized[$permission] = true;
        }
    }
    return array_keys($normalized);
}

function admin_normalize_modules(array $modules): array
{
    $allowed = array_fill_keys(admin_allowed_company_modules(), true);
    $normalized = [];
    foreach ($modules as $module) {
        $module = trim((string) $module);
        if ($module !== '' && isset($allowed[$module])) {
            $normalized[$module] = true;
        }
    }
    return array_keys($normalized);
}

function admin_email_valid(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
