<?php
declare(strict_types=1);

function app_guided_tour_steps(): array
{
    $steps = [
        ['page'=>'dashboard.php','selector'=>'.executive-briefing-notice','icon'=>'⌂','eyebrow'=>'Executive pulse','title'=>'Start with today’s priorities','description'=>'Show how leadership sees past-due commitments, approvals, exceptions, and opportunity value in one brief.'],
        ['page'=>'suppliers.php','selector'=>'.panel','icon'=>'◇','eyebrow'=>'Supplier intelligence','title'=>'Inspect supplier exposure','description'=>'Review supplier master data, ownership, risk, spend, and the evidence required for a trusted supplier decision.'],
        ['page'=>'purchase-orders.php','selector'=>'.table-wrap','icon'=>'↗','eyebrow'=>'Commitment control','title'=>'Trace purchase-order risk','description'=>'Move from executive risk into the purchase orders, dates, suppliers, buyers, and values driving the alert.'],
        ['page'=>'inventory.php','selector'=>'.metric-grid','icon'=>'▤','eyebrow'=>'Cross-company inventory','title'=>'Find transfer opportunities','description'=>'Compare inventory positions and show how available stock can prevent duplicate purchasing.'],
        ['page'=>'savings.php','selector'=>'.savings-portfolio-summary','icon'=>'$','eyebrow'=>'Value realization','title'=>'Manage the savings pipeline','description'=>'Follow opportunities from identification through business case, negotiation, approval, and accounting-validated realization.'],
        ['page'=>'imports.php','selector'=>'.import-job-grid, .panel','icon'=>'⇪','eyebrow'=>'Controlled onboarding','title'=>'Validate source data','description'=>'Demonstrate mapping, row validation, exception disposition, transaction commits, and immutable import receipts.'],
        ['page'=>'approvals.php','selector'=>'.panel','icon'=>'✓','eyebrow'=>'Decision governance','title'=>'Route human approvals','description'=>'Show ownership, comments, evidence, due dates, and the required human decision before operational change.'],
        ['page'=>'briefing.php','selector'=>'.briefing-hero, .briefing-priority-list','icon'=>'☀','eyebrow'=>'Daily operating cadence','title'=>'Review the daily briefing','description'=>'Turn the same records into a prioritized morning plan with links back to the source workflow.'],
        ['page'=>'agent.php','selector'=>'.production-agent-page','icon'=>'◎','eyebrow'=>'Supervised intelligence','title'=>'Finish in Agent Workspace','description'=>'Ask questions across the connected demo dataset, inspect evidence, and launch governed follow-up actions.'],
    ];
    foreach ($steps as $index => &$step) {
        $step['number'] = $index + 1;
        $step['href'] = app_url($step['page'] . '?tour_step=' . ($index + 1));
    }
    unset($step);
    return $steps;
}


function app_navigation(?array $user = null): array
{
    $user ??= current_user();
    $items = [
        ['key'=>'agent','label'=>'Agent Workspace','icon'=>'◎','href'=>'agent.php','permission'=>'agent.view'],
        ...(demo_mode_active() ? [['key'=>'tour','label'=>'Guided Demo Tour','icon'=>'▶','href'=>'tour.php','permission'=>'platform.view']] : []),
        ['key'=>'dashboard','label'=>'Executive Dashboard','icon'=>'⌂','href'=>'dashboard.php','permission'=>'platform.view'],
        ['key'=>'data','label'=>'Data Collection Hub','icon'=>'⇩','href'=>'data-collection.php','permission'=>'discovery.view'],
        ['key'=>'discovery','label'=>'Company Discovery','icon'=>'◫','href'=>'discovery.php','permission'=>'discovery.view'],
        ['key'=>'suppliers','label'=>'Supplier Master','icon'=>'◇','href'=>'suppliers.php','permission'=>'suppliers.view'],
        ['key'=>'items','label'=>'Item / SKU Master','icon'=>'□','href'=>'items.php','permission'=>'items.view'],
        ['key'=>'purchase-orders','label'=>'Purchase Orders','icon'=>'↗','href'=>'purchase-orders.php','permission'=>'purchase_orders.view'],
        ['key'=>'inventory','label'=>'Inventory','icon'=>'▤','href'=>'inventory.php','permission'=>'inventory.view'],
        ['key'=>'savings','label'=>'Savings Pipeline','icon'=>'$','href'=>'savings.php','permission'=>'savings.view'],
        ['key'=>'scorecards','label'=>'Supplier Scorecards','icon'=>'★','href'=>'scorecards.php','permission'=>'scorecards.view'],
        ['key'=>'performance','label'=>'Supplier Performance','icon'=>'↻','href'=>'performance.php','permission'=>'scorecards.view'],
        ['key'=>'imports','label'=>'Imports','icon'=>'⇪','href'=>'imports.php','permission'=>'imports.view'],
        ['key'=>'approvals','label'=>'Reviews & Approvals','icon'=>'✓','href'=>'approvals.php','permission'=>'approvals.view'],
        ['key'=>'reports','label'=>'Reports','icon'=>'▥','href'=>'reports.php','permission'=>'reports.view'],
    ];

    $admin = [
        ['key'=>'admin','label'=>'Admin Console','icon'=>'⚙','href'=>'admin/index.php','permission'=>'platform.administer'],
        ['key'=>'admin-users','label'=>'Users','icon'=>'◉','href'=>'admin/users.php','permission'=>'users.view'],
        ['key'=>'admin-roles','label'=>'Roles & Permissions','icon'=>'⌘','href'=>'admin/roles.php','permission'=>'roles.view'],
        ['key'=>'admin-companies','label'=>'Companies','icon'=>'▦','href'=>'admin/companies.php','permission'=>'companies.view'],
        ['key'=>'admin-sessions','label'=>'Sessions','icon'=>'◷','href'=>'admin/sessions.php','permission'=>'security.view'],
        ['key'=>'admin-access','label'=>'Access Requests','icon'=>'＋','href'=>'admin/access-requests.php','permission'=>'users.administer'],
        ['key'=>'admin-security','label'=>'Security','icon'=>'◆','href'=>'admin/security.php','permission'=>'security.administer'],
        ['key'=>'admin-audit','label'=>'Audit Log','icon'=>'≡','href'=>'admin/audit.php','permission'=>'audit.view'],
        ['key'=>'admin-settings','label'=>'Platform Settings','icon'=>'⚒','href'=>'admin/settings.php','permission'=>'settings.administer'],
        ['key'=>'admin-environment','label'=>'Environment Status','icon'=>'●','href'=>'admin/environment.php','permission'=>'platform.administer'],
    ];

    return [
        'main' => array_values(array_filter($items, static fn(array $item): bool => can($item['permission'], $user))),
        'admin' => array_values(array_filter($admin, static fn(array $item): bool => can($item['permission'], $user))),
    ];
}

function app_page_title(string $title): string
{
    return h($title) . ' | ' . h(app_config()['app']['name']);
}

function render_app_start(
    string $title,
    string $activeKey,
    string $eyebrow = '',
    string $description = '',
    string $actionsHtml = ''
): void {
    $user = require_app_user();
    $navigation = app_navigation($user);
    $companies = data_permitted_companies();
    $selectedCompany = current_company_id();
    $flashes = pull_flashes();
    $notifications = array_values(array_filter(data_collection('notifications'), static fn(array $n): bool => (int) $n['user_id'] === (int) $user['id']));
    $unread = count(array_filter($notifications, static fn(array $n): bool => empty($n['read'])));
    $adminSectionActive = str_starts_with($activeKey, 'admin');
    $workspaceSectionExpanded = !$adminSectionActive;
    $adminSectionExpanded = $adminSectionActive;
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= app_page_title($title) ?></title>
    <meta name="description" content="<?= h($description ?: 'Gruber Procurement Intelligence application') ?>">
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css?v=20260726-savings-tour')) ?>">
    <script src="<?= h(app_url('assets/js/app.js?v=20260726-savings-tour')) ?>" defer></script>
</head>
<body class="<?= demo_mode_active() ? 'is-demo-mode' : 'is-production-mode' ?> app-page-<?= h($activeKey) ?>">
<a class="app-skip-link" href="#appMainContent">Skip to main content</a>
<div class="app-shell">
    <aside class="app-sidebar" id="appSidebar" aria-label="Application navigation">
        <div class="sidebar-brand">
            <a href="<?= h(root_url('index.php')) ?>" aria-label="Gruber home"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"></a>
            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close navigation">×</button>
        </div>
        <nav class="sidebar-nav" aria-label="Application navigation" data-sidebar-accordion>
            <div class="nav-section" data-nav-section="workspace">
                <button class="nav-section-toggle" type="button" aria-expanded="<?= $workspaceSectionExpanded ? 'true' : 'false' ?>" aria-controls="workspaceNavPanel" data-nav-section-toggle>
                    <span>Workspace</span><i class="nav-section-chevron" aria-hidden="true">⌄</i>
                </button>
                <div class="nav-section-panel" id="workspaceNavPanel" <?= $workspaceSectionExpanded ? '' : 'hidden' ?>>
                    <?php foreach ($navigation['main'] as $item): ?>
                        <a class="nav-link <?= $activeKey === $item['key'] ? 'active' : '' ?>" <?= $activeKey === $item['key'] ? 'aria-current="page"' : '' ?> href="<?= h(app_url($item['href'])) ?>">
                            <i aria-hidden="true"><?= h($item['icon']) ?></i><span><?= h($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($navigation['admin']): ?>
                <div class="nav-section admin-nav-section" data-nav-section="administration">
                    <button class="nav-section-toggle" type="button" aria-expanded="<?= $adminSectionExpanded ? 'true' : 'false' ?>" aria-controls="administrationNavPanel" data-nav-section-toggle>
                        <span>Administration</span><i class="nav-section-chevron" aria-hidden="true">⌄</i>
                    </button>
                    <div class="nav-section-panel" id="administrationNavPanel" <?= $adminSectionExpanded ? '' : 'hidden' ?>>
                        <?php foreach ($navigation['admin'] as $item): ?>
                            <a class="nav-link <?= $activeKey === $item['key'] ? 'active' : '' ?>" <?= $activeKey === $item['key'] ? 'aria-current="page"' : '' ?> href="<?= h(app_url($item['href'])) ?>">
                                <i aria-hidden="true"><?= h($item['icon']) ?></i><span><?= h($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>