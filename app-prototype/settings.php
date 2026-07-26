<?php
$nav = [
    ['id' => 'agent', 'label' => 'Agent Workspace', 'icon' => '◎'],
    ['id' => 'dashboard', 'label' => 'Executive Dashboard', 'icon' => '⌂'],
    ['id' => 'procurement', 'label' => 'Procurement', 'icon' => '↗'],
    ['id' => 'suppliers', 'label' => 'Suppliers', 'icon' => '◇'],
    ['id' => 'items', 'label' => 'Items & Inventory', 'icon' => '□'],
    ['id' => 'projects', 'label' => 'Projects & Work Orders', 'icon' => '▤'],
    ['id' => 'savings', 'label' => 'Savings Pipeline', 'icon' => '$'],
    ['id' => 'ai', 'label' => 'AI Briefing Room', 'icon' => '✦'],
    ['id' => 'reports', 'label' => 'Reports', 'icon' => '▥'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings · Gruber AI</title>
    <link rel="stylesheet" href="assets/css/app.css?v=20260726-sidebar-menu-plus2">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="logo-wrap">
            <a class="logo-home" href="../index.php" aria-label="Back to Gruber AI homepage">
                <img class="app-brand-logo" src="../assets/images/gruber-main.png" alt="Gruber">
            </a>
            <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close navigation">×</button>
        </div>
        <nav class="side-nav" aria-label="Application modules">
            <?php foreach ($nav as $item): ?>
                <a class="nav-item" href="index.php?view=<?= htmlspecialchars($item['id']) ?>">
                    <i><?= htmlspecialchars($item['icon']) ?></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <a class="nav-item active" href="settings.php" aria-current="page"><i>⚙</i><span>Settings</span></a>
        </nav>
        <div class="sidebar-foot">
            <a href="../index.php">Back to Home →</a>
            <div class="mobile-sidebar-extras">
                <div class="mobile-sidebar-links" aria-label="Public navigation">
                    <a href="../index.php">Home</a>
                    <a href="../resume.php">Resume</a>
                    <a href="../pitch/index.php">View Plan</a>
                    <a href="../ev-storage/index.php" target="_blank" rel="noopener noreferrer">EV Storage</a>
                </div>
                <div class="mobile-sidebar-contact">
                    <span>Program contact</span>
                    <strong>David Evans</strong>
                    <a href="tel:+14802697433">(480) 269-7433</a>
                </div>
            </div>
            <div class="pilot-badge"><span></span><div><strong>Gruber Power pilot</strong><small>Prototype environment</small></div></div>
        </div>
    </aside>
    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Close navigation" tabindex="-1"></button>

    <main class="main">
        <header class="topbar">
            <div class="topbar-start">
                <button class="menu-button" id="menuButton" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
                <a class="mobile-topbar-logo" href="../index.php" aria-label="Gruber home">
                    <img src="../assets/images/gruber-main.png" alt="Gruber">
                </a>
            </div>
            <div class="top-actions">
                <div class="notification-center" id="notificationCenter">
                    <button class="notification-button" id="notificationButton" type="button" aria-label="Open notifications" aria-expanded="false" aria-controls="notificationDropdown">
                        <span class="notification-bell" aria-hidden="true">♢</span>
                        <b aria-label="4 unread notifications">4</b>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown" hidden>
                        <div class="notification-head">
                            <div><span>Notifications</span><strong>4 items need review</strong></div>
                            <button type="button" id="markNotificationsRead">Mark all read</button>
                        </div>
                        <a class="notification-item urgent" href="index.php?view=procurement"><i>!</i><div><strong>Battery order is nine days late</strong><span>May affect two Gruber Power service commitments.</span><small>5 minutes ago</small></div></a>
                        <a class="notification-item" href="index.php?view=items"><i>↔</i><div><strong>Internal cable transfer identified</strong><span>Potentially avoids a $14,000 duplicate purchase.</span><small>18 minutes ago</small></div></a>
                        <a class="notification-item" href="index.php?view=suppliers"><i>◇</i><div><strong>Three supplier records may be duplicates</strong><span>Legal name, domain and billing address require review.</span><small>1 hour ago</small></div></a>
                        <a class="notification-item" href="index.php?view=savings"><i>$</i><div><strong>Savings opportunity ready for validation</strong><span>Inbound freight consolidation is modeled at $28,000 annually.</span><small>Today</small></div></a>
                    </div>
                </div>
                <div class="user-menu" id="userMenu">
                    <button class="user-menu-button" id="userMenuButton" type="button" aria-label="Open user menu" aria-expanded="false" aria-controls="userDropdown">
                        <span class="user-avatar">DE</span>
                        <span class="user-menu-copy"><strong>David Evans</strong><small>Program Lead</small></span>
                        <span class="user-menu-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown" hidden>
                        <div class="user-dropdown-head"><span>Signed in as</span><strong>David Evans</strong><small>Program Lead</small></div>
                        <a href="../app/profile.php"><i aria-hidden="true">◯</i><span><strong>Profile settings</strong><small>Manage your account information</small></span></a>
                        <a href="../demo.php"><i aria-hidden="true">◎</i><span><strong>Demo accounts</strong><small>Open the role-based demo account selector</small></span></a>
                        <a href="../app/logout.php"><i aria-hidden="true">↪</i><span><strong>Sign out</strong><small>End your current session</small></span></a>
                    </div>
                </div>
            </div>
        </header>

        <section class="settings-page">
            <div class="settings-shell">
                <div class="settings-hero">
                    <div><span class="kicker">Account and workspace controls</span><h1>Settings</h1><p>Manage your profile, notification preferences, Agent Workspace defaults, display behavior, and prototype security options.</p></div>
                    <span class="settings-save-state" id="settingsSaveState">Settings saved</span>
                </div>

                <div class="settings-layout">
                    <nav class="settings-nav panel" aria-label="Settings sections">
                        <a class="active" href="#account">Account</a>
                        <a href="#notifications">Notifications</a>
                        <a href="#workspace">Agent Workspace</a>
                        <a href="#display">Display</a>
                        <a href="#security">Security</a>
                    </nav>

                    <form class="settings-content" id="settingsForm">
                        <article class="settings-card panel" id="account">
                            <header><div><span>Account</span><h2>Profile information</h2><p>These details identify you inside the Gruber AI workspace and notification system.</p></div></header>
                            <div class="settings-fields">
                                <div class="settings-field"><label for="settingsName">Display name</label><input id="settingsName" name="displayName" value="David Evans" autocomplete="name"></div>
                                <div class="settings-field"><label for="settingsRole">Role</label><input id="settingsRole" name="role" value="Program Lead"></div>
                                <div class="settings-field"><label for="settingsPhone">Phone</label><input id="settingsPhone" name="phone" value="(480) 269-7433" autocomplete="tel"></div>
                                <div class="settings-field"><label for="settingsTimezone">Time zone</label><select id="settingsTimezone" name="timezone"><option selected>America/Phoenix</option><option>America/Los_Angeles</option><option>America/Denver</option><option>America/Chicago</option><option>America/New_York</option></select></div>
                                <div class="settings-field full"><label for="settingsTitle">Workspace title</label><input id="settingsTitle" name="workspaceTitle" value="Gruber AI Procurement Transformation"><small>Shown in generated briefs, reports, and supervised agent context.</small></div>
                            </div>
                        </article>

                        <article class="settings-card panel" id="notifications">
                            <header><div><span>Notifications</span><h2>Alerts and review queues</h2><p>Choose which prototype events should surface in the header notification center.</p></div></header>
                            <div class="settings-toggle-list">
                                <label class="settings-toggle"><span><strong>High-priority purchase order risks</strong><small>Late orders, missing acknowledgements, and required-date exposure.</small></span><input type="checkbox" name="notifyPoRisks" checked><span class="toggle-track"></span></label>
                                <label class="settings-toggle"><span><strong>Inventory transfer opportunities</strong><small>Cross-company stock that may avoid a duplicate purchase.</small></span><input type="checkbox" name="notifyTransfers" checked><span class="toggle-track"></span></label>
                                <label class="settings-toggle"><span><strong>Supplier data-quality findings</strong><small>Possible duplicates, missing fields, and performance exceptions.</small></span><input type="checkbox" name="notifySupplierData" checked><span class="toggle-track"></span></label>
                                <label class="settings-toggle"><span><strong>Weekly executive briefing reminder</strong><small>Reminder to review and publish the human-supervised weekly brief.</small></span><input type="checkbox" name="notifyWeeklyBrief" checked><span class="toggle-track"></span></label>
                            </div>
                        </article>

                        <article class="settings-card panel" id="workspace">
                            <header><div><span>Agent Workspace</span><h2>Default agent behavior</h2><p>Set the default entry point and evidence expectations for simulated AI conversations.</p></div></header>
                            <div class="settings-fields">
                                <div class="settings-field"><label for="defaultAgent">Default agent</label><select id="defaultAgent" name="defaultAgent"><option value="executive" selected>Executive Briefing</option><option value="supplier">Supplier Intelligence</option><option value="inventory">Inventory Intelligence</option><option value="procurement">Procurement Risk</option><option value="savings">Savings Opportunity</option><option value="data">Data Quality</option></select></div>
                                <div class="settings-field"><label for="defaultScope">Default data scope</label><select id="defaultScope" name="defaultScope"><option selected>Enterprise view</option><option>Gruber Communications</option><option>Gruber Power Services</option><option>Gruber Technical Services</option><option>Gruber Motor Company</option><option>EV Preserve</option><option>Gruber Commercial Properties</option></select></div>
                            </div>
                            <div class="settings-toggle-list">
                                <label class="settings-toggle"><span><strong>Require evidence tags</strong><small>Every simulated response should identify the records or assumptions used.</small></span><input type="checkbox" name="requireEvidence" checked><span class="toggle-track"></span></label>
                                <label class="settings-toggle"><span><strong>Human approval reminders</strong><small>Keep transaction, supplier, write-off, and purchasing decisions explicitly human-controlled.</small></span><input type="checkbox" name="humanApproval" checked><span class="toggle-track"></span></label>
                            </div>
                        </article>

                        <article class="settings-card panel" id="display">
                            <header><div><span>Display</span><h2>Workspace appearance</h2><p>Control interface density and chat readability for this browser.</p></div></header>
                            <div class="settings-fields">
                                <div class="settings-field"><label for="displayDensity">Interface density</label><select id="displayDensity" name="displayDensity"><option selected>Comfortable</option><option>Compact</option><option>Spacious</option></select></div>
                                <div class="settings-field"><label for="chatTextSize">Chat text size</label><select id="chatTextSize" name="chatTextSize"><option>17px</option><option selected>18px</option><option>19px</option><option>20px</option></select></div>
                            </div>
                            <div class="settings-toggle-list">
                                <label class="settings-toggle"><span><strong>Keep composer fixed</strong><small>Pin the Agent Workspace composer to the full bottom edge of the app.</small></span><input type="checkbox" name="fixedComposer" checked><span class="toggle-track"></span></label>
                                <label class="settings-toggle"><span><strong>Reduce motion</strong><small>Limit nonessential interface animation while preserving navigation feedback.</small></span><input type="checkbox" name="reduceMotion"><span class="toggle-track"></span></label>
                            </div>
                        </article>

                        <article class="settings-card panel" id="security">
                            <header><div><span>Security</span><h2>Prototype session</h2><p>This prototype stores preferences only in the current browser. No production identity or purchasing authority is connected.</p></div></header>
                            <div class="settings-toggle-list">
                                <label class="settings-toggle"><span><strong>Lock after 30 minutes of inactivity</strong><small>Require a new prototype session after an extended idle period.</small></span><input type="checkbox" name="idleLock" checked><span class="toggle-track"></span></label>
                                <label class="settings-toggle"><span><strong>Clear local chat history on logout</strong><small>Remove locally simulated Agent Workspace messages when the session ends.</small></span><input type="checkbox" name="clearChatOnLogout" checked><span class="toggle-track"></span></label>
                            </div>
                        </article>

                        <div class="settings-actions"><a class="secondary" href="index.php">Cancel</a><button class="primary" type="submit">Save Settings</button></div>
                    </form>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="assets/js/app.js?v=20260726-account-menu"></script>
</body>
</html>
