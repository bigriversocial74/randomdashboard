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
        </nav>
        <div class="sidebar-footer">
            <button class="history-button" type="button" data-history-toggle>
                <span class="history-icon">◫</span>
                <span><strong>Messages / History</strong><small>Open saved conversations</small></span>
                <b id="agentHistoryCount">0</b>
            </button>
            <a class="sidebar-home-link" href="<?= h(root_url('index.php')) ?>">Back to public site <span>→</span></a>
        </div>
    </aside>
    <button class="sidebar-backdrop" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>

    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-button" type="button" data-sidebar-open aria-label="Open navigation" aria-controls="appSidebar" aria-expanded="false">☰</button>
                <a class="mobile-brand" href="<?= h(root_url('index.php')) ?>"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"></a>
                <form class="company-switcher" action="<?= h(app_url('action.php')) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="switch_company">
                        <input type="hidden" name="return_to" value="<?= h($_SERVER['REQUEST_URI'] ?? app_url('dashboard.php')) ?>">
                        <label>
                            <span>Company scope</span>
                            <select name="company_id" onchange="this.form.submit()">
                                <?php if (can_use_enterprise_view($user)): ?>
                                    <option value="enterprise" <?= $selectedCompany === 'enterprise' ? 'selected' : '' ?>>Enterprise View</option>
                                <?php endif; ?>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= (int) $company['id'] ?>" <?= (string) $selectedCompany === (string) $company['id'] ? 'selected' : '' ?>><?= h($company['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </form>
            </div>
            <div class="topbar-actions">
                <div class="dropdown">
                    <button class="icon-button" type="button" data-dropdown-toggle="notificationsDropdown" aria-controls="notificationsDropdown" aria-expanded="false" aria-label="Open notifications">
                        ♢<?php if ($unread): ?><b><?= $unread ?></b><?php endif; ?>
                    </button>
                    <div class="dropdown-panel notifications-panel" id="notificationsDropdown" hidden>
                        <div class="dropdown-heading"><div><span>Notifications</span><strong><?= $unread ?> unread</strong></div>
                            <?php if ($unread): ?>
                                <form action="<?= h(app_url('action.php')) ?>" method="post">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="mark_notifications_read">
                                    <input type="hidden" name="return_to" value="<?= h($_SERVER['REQUEST_URI'] ?? app_url('dashboard.php')) ?>">
                                    <button type="submit" class="link-button">Mark all read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (!$notifications): ?>
                            <div class="dropdown-empty">No notifications are available.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                                <a class="notification-row <?= empty($notification['read']) ? 'unread' : '' ?>" href="<?= h(app_url('notifications.php')) ?>">
                                    <i class="<?= h(status_class($notification['severity'])) ?>">!</i>
                                    <span><strong><?= h($notification['title']) ?></strong><small><?= h($notification['message']) ?></small><em><?= h(date_us($notification['created_at'], true)) ?></em></span>
                                </a>
                            <?php endforeach; ?>
                            <a class="dropdown-footer-link" href="<?= h(app_url('notifications.php')) ?>">View all notifications →</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="user-button" type="button" data-dropdown-toggle="userDropdown" aria-controls="userDropdown" aria-expanded="false" aria-label="Open account menu">
                        <span class="avatar"><?= h(initials($user['name'])) ?></span>
                        <span class="user-copy"><strong><?= h($user['name']) ?></strong><small><?= h(data_role_name(current_role_codes($user)[0] ?? 'read_only')) ?></small></span>
                        <span>⌄</span>
                    </button>
                    <div class="dropdown-panel user-panel" id="userDropdown" hidden>
                        <div class="user-panel-head"><span>Signed in as</span><strong><?= h($user['name']) ?></strong><small><?= h($user['email']) ?></small></div>
                        <a href="<?= h(app_url('profile.php')) ?>">Profile settings</a>
                        <a href="<?= h(root_url('demo.php')) ?>">Demo accounts</a>
                        <a href="<?= h(app_url('logout.php')) ?>">Sign out</a>
                    </div>
                </div>
            </div>
        </header>

        <?php if (demo_mode_active()): ?>
            <div class="demo-status-bar">
                <span><b>DEMO MODE</b> Sample records only. No production database writes.</span>
                <div>
                    <a class="link-button" href="<?= h(app_url('tour.php')) ?>">Guided tour</a>
                    <button type="button" class="link-button" data-modal-open="demoControlsModal">Demo controls</button>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!demo_mode_active()): ?>
            <div class="production-status-bar">
                <span><b>PRODUCTION DATA</b> Changes are persisted to the configured MySQL database.</span>
                <a href="<?= h(app_url('admin/environment.php')) ?>">Environment controls</a>
            </div>
        <?php endif; ?>

        <main class="app-content" id="appMainContent" tabindex="-1">
            <?php foreach ($flashes as $flash): ?>
                <div class="flash flash-<?= h($flash['type']) ?>" role="status"><span><?= h($flash['message']) ?></span><button type="button" data-dismiss-flash aria-label="Dismiss message">×</button></div>
            <?php endforeach; ?>

            <section class="page-heading">
                <div>
                    <?php if ($eyebrow): ?><span class="eyebrow"><?= h($eyebrow) ?></span><?php endif; ?>
                    <h1><?= h($title) ?></h1>
                    <?php if ($description): ?><p><?= h($description) ?></p><?php endif; ?>
                </div>
                <?php if ($actionsHtml): ?><div class="page-actions"><?= $actionsHtml ?></div><?php endif; ?>
            </section>
    <?php
}

function render_app_end(): void
{
    if (demo_mode_active()): ?>
        <div class="modal" id="demoControlsModal" hidden>
            <div class="modal-backdrop" data-modal-close></div>
            <section class="modal-card modal-card-small" role="dialog" aria-modal="true" aria-labelledby="demoControlsTitle">
                <header><div><span class="eyebrow">Isolated review environment</span><h2 id="demoControlsTitle">Demo controls</h2></div><button type="button" data-modal-close aria-label="Close">×</button></header>
                <div class="modal-body">
                    <p>Reset actions affect only this browser session. They never write to the production database.</p>
                    <div class="control-list">
                        <form action="<?= h(app_url('action.php')) ?>" method="post" data-confirm="Reset all demo records while keeping this account and company scope?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="reset_demo_data"><button class="button secondary full" type="submit">Reset Demo Data</button>
                        </form>
                        <form action="<?= h(app_url('action.php')) ?>" method="post" data-confirm="Restore the complete default demo dataset?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="restore_demo_defaults"><button class="button secondary full" type="submit">Restore Default Demo Dataset</button>
                        </form>
                        <form action="<?= h(app_url('action.php')) ?>" method="post" data-confirm="Clear the demo session and return to the account selector?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="clear_demo_session"><button class="button danger full" type="submit">Clear Demo Session</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    <?php endif; ?>
        </main>
        <footer class="app-footer"><span>Gruber Procurement Intelligence</span><span><?= demo_mode_active() ? 'Fictional sample environment' : 'Production data environment' ?> · <?= h(date('Y')) ?></span></footer>
    </div>
</div>
<?php if (demo_mode_active()): ?><script type="application/json" id="guidedTourData"><?= json_encode(['steps'=>app_guided_tour_steps(),'tour_url'=>app_url('tour.php')], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script><?php endif; ?>
<div class="history-drawer" id="historyDrawer" hidden>
    <header><div><span>Agent Workspace</span><strong>Messages / History</strong></div><button type="button" data-history-close>×</button></header>
    <div class="history-list" id="agentHistoryList">
        <div class="dropdown-empty">No saved Agent conversations in this browser.</div>
    </div>
</div>
</body>
</html>
    <?php
}

function render_environment_gate(): void
{
    $snapshot = environment_snapshot();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Choose environment | Gruber Procurement Intelligence</title>
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css?v=20260726-savings-tour')) ?>">
</head>
<body class="environment-gate-page">
<main class="environment-gate">
    <a class="gate-brand" href="<?= h(root_url('index.php')) ?>"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"></a>
    <section class="gate-card">
        <span class="eyebrow">Environment selection</span>
        <h1>Procurement Intelligence Platform</h1>
        <p>The production database is not connected. Choose the isolated Demo Mode for review or complete the documented manual configuration process.</p>
        <div class="environment-summary">
            <article><span>Configuration</span><strong><?= h($snapshot['config']) ?></strong></article>
            <article><span>Database</span><strong><?= h($snapshot['database']) ?></strong></article>
            <article><span>Demo availability</span><strong><?= app_config()['app']['demo_enabled'] ? 'Enabled' : 'Disabled' ?></strong></article>
        </div>
        <div class="gate-actions">
            <?php if (app_config()['app']['demo_enabled']): ?><a class="button primary" href="<?= h(root_url('demo.php')) ?>">Enter Demo Mode</a><?php endif; ?>
            <a class="button secondary" href="<?= h(root_url('MANUAL_SETUP.txt')) ?>">View manual setup guide</a>
        </div>
        <p class="sample-disclaimer">Demo Mode is intentional, visibly labeled, session-isolated, and never writes sample records to MySQL.</p>
    </section>
</main>
</body>
</html>
    <?php
}

function render_access_denied(string $permission): void
{
    $user = current_user();
    ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Access denied</title><link rel="stylesheet" href="<?= h(app_url('assets/css/app.css')) ?>"></head>
<body class="environment-gate-page"><main class="environment-gate"><section class="gate-card"><span class="eyebrow">Permission required</span><h1>Access denied</h1><p><?= h($user['name'] ?? 'This account') ?> does not have <code><?= h($permission) ?></code>.</p><div class="gate-actions"><a class="button primary" href="<?= h(app_url('dashboard.php')) ?>">Return to dashboard</a><?php if (demo_mode_active()): ?><a class="button secondary" href="<?= h(root_url('demo.php')) ?>">Switch demo role</a><?php endif; ?></div></section></main></body></html>
    <?php
}

function render_empty_state(string $title, string $message): void
{
    ?><div class="empty-state"><span>◇</span><h3><?= h($title) ?></h3><p><?= h($message) ?></p></div><?php
}

function render_pagination(array $pagination, array $query = []): void
{
    if (($pagination['pages'] ?? 1) <= 1) {
        return;
    }
    ?>
<nav class="pagination" aria-label="Pagination">
    <?php for ($page = 1; $page <= $pagination['pages']; $page++): ?>
        <?php $params = array_merge($query, ['page' => $page]); ?>
        <a class="<?= $page === $pagination['page'] ? 'active' : '' ?>" <?= $page === $pagination['page'] ? 'aria-current="page"' : '' ?> href="?<?= h(http_build_query($params)) ?>"><?= $page ?></a>
    <?php endfor; ?>
</nav>
    <?php
}
