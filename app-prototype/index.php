<?php
$companies = [
    'enterprise' => 'Enterprise View',
    'gc' => 'Gruber Communications',
    'gps' => 'Gruber Power Services',
    'gts' => 'Gruber Technical Services',
    'gmc' => 'Gruber Motor Company',
    'evp' => 'EV Preserve',
    'gcp' => 'Gruber Commercial Properties',
];

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

$suppliers = [
    ['name' => 'Critical Power Battery Supply', 'category' => 'Batteries', 'spend' => '$1,284,400', 'delivery' => '94.2%', 'quality' => '98.1%', 'grade' => 'Preferred'],
    ['name' => 'National Electrical Distribution', 'category' => 'Electrical Materials', 'spend' => '$842,760', 'delivery' => '88.4%', 'quality' => '96.7%', 'grade' => 'Approved'],
    ['name' => 'Fiber & Copper Manufacturing', 'category' => 'Cable and Connectivity', 'spend' => '$619,320', 'delivery' => '91.1%', 'quality' => '97.4%', 'grade' => 'Approved'],
    ['name' => 'Legacy Electronics Components', 'category' => 'Legacy Service Parts', 'spend' => '$318,950', 'delivery' => '72.6%', 'quality' => '92.8%', 'grade' => 'Conditional'],
];

$findings = [
    ['severity' => 'high', 'title' => 'Past-due battery order may affect two service commitments', 'detail' => 'PO GPS-10428 is 9 days past expected delivery. No supplier acknowledgement update has been received.', 'action' => 'Escalate supplier'],
    ['severity' => 'medium', 'title' => 'Internal transfer may avoid a duplicate cable purchase', 'detail' => 'GC Phoenix has 1,800 feet available while GTS has an open request for 1,200 feet with a 6-day required date.', 'action' => 'Review transfer'],
    ['severity' => 'medium', 'title' => 'Three supplier records appear to be duplicates', 'detail' => 'Names, website domain and billing address suggest the records belong to the same legal supplier.', 'action' => 'Merge review'],
    ['severity' => 'low', 'title' => 'Expedited freight increased in UPS service parts', 'detail' => 'The current month is 21% above the trailing six-month average, concentrated in four SKUs.', 'action' => 'Analyze stocking'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gruber AI App Prototype</title>
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
                <button class="nav-item <?= $item['id'] === 'agent' ? 'active' : '' ?>" data-page="<?= htmlspecialchars($item['id']) ?>">
                    <i><?= htmlspecialchars($item['icon']) ?></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </button>
            <?php endforeach; ?>
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
            <button class="sidebar-history-button" type="button" data-page="agent" aria-label="Open messages and history">
                <span class="sidebar-history-icon" aria-hidden="true">◫</span>
                <span class="sidebar-history-copy"><strong>Messages / History</strong><small>Open agent conversations</small></span>
                <span class="sidebar-history-count" aria-label="3 saved conversations">3</span>
            </button>
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
                        <button class="notification-item urgent" type="button" data-jump="procurement">
                            <i>!</i><div><strong>Battery order is nine days late</strong><span>May affect two Gruber Power service commitments.</span><small>5 minutes ago</small></div>
                        </button>
                        <button class="notification-item" type="button" data-jump="items">
                            <i>↔</i><div><strong>Internal cable transfer identified</strong><span>Potentially avoids a $14,000 duplicate purchase.</span><small>18 minutes ago</small></div>
                        </button>
                        <button class="notification-item" type="button" data-jump="suppliers">
                            <i>◇</i><div><strong>Three supplier records may be duplicates</strong><span>Legal name, domain and billing address require review.</span><small>1 hour ago</small></div>
                        </button>
                        <button class="notification-item" type="button" data-jump="savings">
                            <i>$</i><div><strong>Savings opportunity ready for validation</strong><span>Inbound freight consolidation is modeled at $28,000 annually.</span><small>Today</small></div>
                        </button>
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

        <section class="page" id="page-dashboard">
            <div class="page-head">
                <div><span class="kicker">Enterprise command center</span><h1>Executive Dashboard</h1><p>Purchasing, inventory, supplier and savings performance across all six businesses.</p></div>
                <div class="head-actions"><button class="secondary">Import Data</button><button class="primary">Generate AI Brief</button></div>
            </div>

            <div class="alert-bar"><span class="pulse"></span><strong>4 findings need review</strong><p>AI has identified one high-priority PO risk and three cross-company opportunities.</p><button data-jump="ai">Review findings →</button></div>

            <div class="metric-grid">
                <article><span>Total Purchasing Spend</span><strong>$4.86M</strong><small>Baseline example · trailing 12 months</small><b class="up">↑ 4.8%</b></article>
                <article><span>Open PO Commitments</span><strong>$724K</strong><small>Includes $118K past due</small><b class="warning">17 exceptions</b></article>
                <article><span>Inventory Value</span><strong>$2.14M</strong><small>$302K needs aging review</small><b class="neutral">5 businesses</b></article>
                <article><span>Savings Pipeline</span><strong>$286K</strong><small>$64K accounting validated</small><b class="good">12 active</b></article>
            </div>

            <div class="dashboard-layout">
                <article class="panel chart-panel">
                    <div class="panel-head"><div><span>Company comparison</span><h2>Purchasing spend and inventory</h2></div><button>Trailing 12 months⌄</button></div>
                    <div class="bars">
                        <div class="bar-row"><span>Gruber Power</span><div><i style="--w:92%"></i><em style="--w:58%"></em></div><b>$1.82M</b></div>
                        <div class="bar-row"><span>Communications</span><div><i style="--w:76%"></i><em style="--w:47%"></em></div><b>$1.31M</b></div>
                        <div class="bar-row"><span>Technical</span><div><i style="--w:63%"></i><em style="--w:29%"></em></div><b>$982K</b></div>
                        <div class="bar-row"><span>Motor Company</span><div><i style="--w:42%"></i><em style="--w:38%"></em></div><b>$624K</b></div>
                        <div class="bar-row"><span>EV Preserve</span><div><i style="--w:13%"></i><em style="--w:9%"></em></div><b>$123K</b></div>
                        <div class="bar-row"><span>Commercial Properties</span><div><i style="--w:18%"></i><em style="--w:12%"></em></div><b>$176K</b></div>
                    </div>
                    <div class="legend"><span><i></i>Purchasing spend</span><span><em></em>Inventory value</span></div>
                </article>

                <article class="panel exception-panel">
                    <div class="panel-head"><div><span>Priority queue</span><h2>Operational exceptions</h2></div><button data-jump="ai">Open all</button></div>
                    <div class="exception-list">
                        <div><i class="critical">!</i><p><strong>PO GPS-10428</strong><span>9 days late · service impact</span></p><b>High</b></div>
                        <div><i>↔</i><p><strong>Enterprise transfer</strong><span>1,200 ft cable available</span></p><b>Medium</b></div>
                        <div><i>◇</i><p><strong>Supplier duplicates</strong><span>3 records suggested</span></p><b>Medium</b></div>
                        <div><i>↗</i><p><strong>Expedite cost</strong><span>UPS parts above trend</span></p><b>Low</b></div>
                    </div>
                </article>
            </div>

            <div class="company-strip">
                <?php foreach (array_slice($companies, 1) as $key => $company): ?>
                    <article><span><?= strtoupper(htmlspecialchars($key)) ?></span><strong><?= htmlspecialchars($company) ?></strong><small>Open company dashboard →</small></article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="page" id="page-procurement">
            <div class="page-head"><div><span class="kicker">Requests, approvals and orders</span><h1>Procurement</h1><p>Control commitments from request through supplier acknowledgement and receiving.</p></div><button class="primary">New Purchase Request</button></div>
            <div class="tab-row"><button class="active">Open POs <b>38</b></button><button>Past Due <b>7</b></button><button>Approvals <b>4</b></button><button>Receiving</button><button>Imports</button></div>
            <article class="panel data-panel"><div class="panel-head"><div><span>Open commitments</span><h2>Purchase Orders</h2></div><div><button>Filter</button><button>Export</button></div></div>
                <table><thead><tr><th>PO</th><th>Company</th><th>Supplier</th><th>Purpose</th><th>Expected</th><th>Value</th><th>Status</th></tr></thead><tbody>
                    <tr><td><strong>GPS-10428</strong></td><td>Gruber Power</td><td>Critical Power Battery Supply</td><td>Service work order</td><td class="danger">9 days late</td><td>$48,620</td><td><span class="status red">Past due</span></td></tr>
                    <tr><td><strong>GC-8821</strong></td><td>Communications</td><td>Fiber & Copper Manufacturing</td><td>Inventory replenishment</td><td>Jul 29</td><td>$31,480</td><td><span class="status blue">Open</span></td></tr>
                    <tr><td><strong>GTS-3390</strong></td><td>Technical Services</td><td>National Electrical Distribution</td><td>Construction project</td><td>Aug 2</td><td>$76,210</td><td><span class="status amber">Partial</span></td></tr>
                    <tr><td><strong>GMC-1226</strong></td><td>Motor Company</td><td>Legacy Electronics Components</td><td>Vehicle restoration</td><td>Aug 8</td><td>$18,960</td><td><span class="status blue">Open</span></td></tr>
                </tbody></table>
            </article>
        </section>

        <section class="page" id="page-suppliers">
            <div class="page-head"><div><span class="kicker">Enterprise supplier command</span><h1>Suppliers</h1><p>Normalize relationships, consolidate demand and manage performance.</p></div><button class="primary">Add Supplier</button></div>
            <div class="summary-row"><article><strong>126</strong><span>Active suppliers</span></article><article><strong>18</strong><span>Preferred</span></article><article><strong>7</strong><span>Contracts expiring</span></article><article><strong>3</strong><span>Duplicate candidates</span></article></div>
            <article class="panel data-panel"><div class="panel-head"><div><span>Performance</span><h2>Strategic Supplier Scorecards</h2></div><button>Run scorecards</button></div>
                <table><thead><tr><th>Supplier</th><th>Category</th><th>Annual Spend</th><th>On Time</th><th>Quality</th><th>Grade</th></tr></thead><tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr><td><strong><?= htmlspecialchars($supplier['name']) ?></strong></td><td><?= htmlspecialchars($supplier['category']) ?></td><td><?= htmlspecialchars($supplier['spend']) ?></td><td><?= htmlspecialchars($supplier['delivery']) ?></td><td><?= htmlspecialchars($supplier['quality']) ?></td><td><span class="status <?= $supplier['grade'] === 'Conditional' ? 'amber' : 'green' ?>"><?= htmlspecialchars($supplier['grade']) ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </article>
        </section>

        <section class="page" id="page-items">
            <div class="page-head"><div><span class="kicker">Enterprise availability</span><h1>Items & Inventory</h1><p>See what exists, where it is stored, how old it is and whether it can be transferred.</p></div><button class="primary">Import Snapshot</button></div>
            <div class="metric-grid compact-metrics"><article><span>Inventory Value</span><strong>$2.14M</strong><small>Across 18 locations</small></article><article><span>Excess / Obsolete</span><strong>$302K</strong><small>Requires disposition review</small></article><article><span>Strategic Legacy</span><strong>$418K</strong><small>Protected service inventory</small></article><article><span>Transfer Candidates</span><strong>23</strong><small>Potential new buys avoided</small></article></div>
            <div class="inventory-grid"><article class="panel inventory-card"><span>Active</span><strong>$1.21M</strong><div><i style="width:76%"></i></div><small>0–90 days</small></article><article class="panel inventory-card"><span>Monitor</span><strong>$209K</strong><div><i style="width:43%"></i></div><small>91–180 days</small></article><article class="panel inventory-card"><span>Slow-Moving</span><strong>$167K</strong><div><i style="width:34%"></i></div><small>181–365 days</small></article><article class="panel inventory-card"><span>Excess / Obsolete</span><strong>$302K</strong><div><i class="redbar" style="width:61%"></i></div><small>366+ days</small></article></div>
        </section>

        <section class="page" id="page-projects">
            <div class="page-head"><div><span class="kicker">Material accountability</span><h1>Projects & Work Orders</h1><p>Connect purchasing and inventory to the work that creates revenue.</p></div><button class="primary">Add Work Order</button></div>
            <div class="project-grid"><article class="project-card"><span>Construction Project</span><h2>Data Center Electrical Expansion</h2><p>GTS-PRJ-2026-041</p><div><strong>$186K</strong><small>Material budget</small><strong>$172K</strong><small>Committed</small></div><b>92% committed</b></article><article class="project-card"><span>Service Work Order</span><h2>UPS Battery Replacement Program</h2><p>GPS-SVC-2026-118</p><div><strong>$74K</strong><small>Material budget</small><strong>$48K</strong><small>Committed</small></div><b>1 supplier risk</b></article><article class="project-card"><span>Vehicle Restoration</span><h2>Roadster Preservation Build</h2><p>GMC-RST-2026-022</p><div><strong>$39K</strong><small>Material budget</small><strong>$31K</strong><small>Committed</small></div><b>3 legacy parts</b></article></div>
        </section>

        <section class="page" id="page-savings">
            <div class="page-head"><div><span class="kicker">Financial improvement</span><h1>Savings Pipeline</h1><p>Move opportunities from evidence through implementation and Accounting validation.</p></div><button class="primary">New Opportunity</button></div>
            <div class="kanban"><div class="kanban-col"><header><strong>Identified</strong><span>4</span></header><article><b>Supplier consolidation</b><h3>Combine battery demand</h3><p>GPS + GMC purchasing overlap</p><strong>$72K expected</strong></article><article><b>Inventory</b><h3>Transfer cable inventory</h3><p>GC stock available for GTS project</p><strong>$14K avoided purchase</strong></article></div><div class="kanban-col"><header><strong>Negotiating</strong><span>2</span></header><article><b>Pricing</b><h3>Critical power service parts</h3><p>Volume and lead-time agreement</p><strong>$46K expected</strong></article></div><div class="kanban-col"><header><strong>Implementing</strong><span>3</span></header><article><b>Freight</b><h3>Consolidated inbound shipments</h3><p>New weekly delivery schedule</p><strong>$28K expected</strong></article></div><div class="kanban-col"><header><strong>Validated</strong><span>3</span></header><article class="validated"><b>Contract terms</b><h3>Payment term extension</h3><p>Accounting validation complete</p><strong>$64K realized</strong></article></div></div>
        </section>


        <section class="page agent-page active" id="page-agent">
            <article class="chat-stage panel" id="agentChatStage">
                <div class="chat-day"><span>Prototype session · sample enterprise data</span></div>
                <div class="chat-message assistant-message">
                    <div class="message-avatar">AI</div>
                    <div class="message-body"><span>Executive Briefing Agent</span><p>Ready. I can summarize current risks, identify cross-company opportunities, explain supporting records and prepare a draft action plan for human review.</p><div class="evidence-row"><b>Context loaded</b><span>38 open POs</span><span>126 suppliers</span><span>$2.14M inventory</span><span>12 savings opportunities</span></div></div>
                </div>
                <div class="prompt-suggestions" id="promptSuggestions">
                    <button data-agent-prompt="What are the top three issues leadership should review today?">Top leadership issues</button>
                    <button data-agent-prompt="Where can inventory be transferred before we create new purchase orders?">Find inventory transfers</button>
                    <button data-agent-prompt="Which suppliers need immediate attention and why?">Supplier attention</button>
                    <button data-agent-prompt="Draft a weekly executive procurement brief.">Draft executive brief</button>
                    <button data-agent-prompt="Summarize my résumé and explain how my background supports the Gruber AI Procurement initiative.">Review my résumé</button>
                </div>
            </article>

            <div class="agent-composer-wrap" id="agentComposerWrap">
                <form class="agent-composer" id="agentChatForm">
                    <button type="button" class="composer-tool" id="workspaceMenuButton" aria-label="Open workspace menu" aria-expanded="false">＋</button>
                    <textarea id="agentChatInput" rows="1" placeholder="Ask the active Gruber agent about purchasing, inventory, suppliers or operations…"></textarea>
                    <button type="submit" class="send-agent-message" aria-label="Send message">↑</button>
                </form>
            </div>
        </section>

        <section class="page" id="page-ai">
            <div class="page-head"><div><span class="kicker">Human-supervised intelligence</span><h1>AI Briefing Room</h1><p>Review evidence-based findings, generate leadership summaries and assign action.</p></div><button class="primary">Generate Weekly Brief</button></div>
            <div class="ai-layout"><article class="panel finding-panel"><div class="panel-head"><div><span>Findings queue</span><h2>Needs Human Review</h2></div><button>Filter</button></div>
                <?php foreach ($findings as $finding): ?>
                    <div class="finding"><i class="<?= htmlspecialchars($finding['severity']) ?>">!</i><div><span><?= ucfirst(htmlspecialchars($finding['severity'])) ?> priority</span><h3><?= htmlspecialchars($finding['title']) ?></h3><p><?= htmlspecialchars($finding['detail']) ?></p></div><button><?= htmlspecialchars($finding['action']) ?></button></div>
                <?php endforeach; ?>
            </article><aside class="panel briefing-panel"><span>Draft briefing</span><h2>Weekly Executive Brief</h2><p class="date">July 20–24, 2026</p><div class="brief-block"><strong>Top risk</strong><p>A battery delivery delay may affect two service commitments. Procurement should escalate and confirm an alternative source today.</p></div><div class="brief-block"><strong>Top opportunity</strong><p>Cross-company inventory visibility suggests an internal cable transfer could avoid a $14,000 purchase.</p></div><div class="brief-block"><strong>Decision required</strong><p>Approve the Gruber Power battery category as the first 60–90 day pilot.</p></div><button class="primary full">Review and Publish</button></aside></div>
        </section>

        <section class="page" id="page-reports">
            <div class="page-head"><div><span class="kicker">Operational and financial reporting</span><h1>Reports</h1><p>Saved, scheduled and drillable reporting across each business.</p></div><button class="primary">Create Report</button></div>
            <div class="report-grid"><?php foreach (['Executive Procurement Scorecard','Consolidated Spend Analysis','Open and Past-Due Purchase Orders','Supplier Performance Scorecard','Inventory Accuracy','Inventory Turns and Days on Hand','Inventory Aging and Disposition','Stockout and Expedite Costs','Project Material Variance','Savings and Improvement Pipeline'] as $report): ?><article><span>Live report</span><h2><?= htmlspecialchars($report) ?></h2><p>Filter, drill down, assign action, schedule delivery and export.</p><button>Open report →</button></article><?php endforeach; ?></div>
        </section>

        <div class="workspace-menu-modal" id="workspaceMenuModal" aria-hidden="true">
            <button class="workspace-menu-backdrop" type="button" data-close-workspace-menu aria-label="Close menu"></button>
            <section class="workspace-menu-panel" role="dialog" aria-modal="true" aria-labelledby="workspaceMenuTitle">
                <header><div><span>Gruber AI workspace</span><h2 id="workspaceMenuTitle">Main navigation</h2></div><button type="button" data-close-workspace-menu aria-label="Close">×</button></header>
                <div class="workspace-menu-grid">
                    <?php foreach ($nav as $item): ?>
                        <button class="workspace-menu-item" data-page="<?= htmlspecialchars($item['id']) ?>">
                            <i><?= htmlspecialchars($item['icon']) ?></i><span><strong><?= htmlspecialchars($item['label']) ?></strong><small>Open workspace module</small></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <footer><a href="../index.php">Back to Home</a><button type="button" id="clearAgentChat">Clear chat</button><button type="button" id="newAgentThread">New thread</button></footer>
            </section>
        </div>
    </main>
</div>
<script src="assets/js/app.js?v=20260726-account-menu"></script>
</body>
</html>
