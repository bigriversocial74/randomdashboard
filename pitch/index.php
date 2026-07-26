<?php
$companies = [
    ['code' => 'GC', 'name' => 'Gruber Communications', 'focus' => 'Manufacturing, cabling, racks, connectors and custom assemblies.'],
    ['code' => 'GPS', 'name' => 'Gruber Power Services', 'focus' => 'UPS systems, batteries, service parts and emergency readiness.'],
    ['code' => 'GTS', 'name' => 'Gruber Technical Services', 'focus' => 'Electrical, structured cabling, data-center projects and flooring.'],
    ['code' => 'GMC', 'name' => 'Gruber Motor Company', 'focus' => 'Tesla service, EV engineering, restoration and legacy components.'],
    ['code' => 'EVP', 'name' => 'EV Preserve', 'focus' => 'EV storage, monitoring, vehicle intake and preservation.'],
    ['code' => 'GCP', 'name' => 'Gruber Commercial Properties', 'focus' => 'Commercial property operations and shared infrastructure support.'],
];

$modules = [
    ['title' => 'Procurement Control Center', 'text' => 'Requests, approvals, quotes, purchase orders, receiving and exception controls.'],
    ['title' => 'Supplier Command', 'text' => 'Contracts, pricing, scorecards, quality, risk and negotiation strategy.'],
    ['title' => 'Inventory Intelligence', 'text' => 'Shared visibility across warehouses, vehicles, projects, benches and strategic legacy stock.'],
    ['title' => 'Project & Work Order Materials', 'text' => 'Tie every purchase and inventory movement to the operational reason it exists.'],
    ['title' => 'Savings Pipeline', 'text' => 'Track opportunities from discovery through Accounting-validated results.'],
    ['title' => 'Agent Workspace', 'text' => 'Supervised agents answer questions, surface evidence and prepare draft actions.'],
];

$agents = [
    'Executive Briefing',
    'Supplier Intelligence',
    'Inventory Transfer',
    'Purchase Order Risk',
    'Savings Opportunity',
    'Data Quality',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Gruber Intelligence Initiative — Public Plan</title>
    <meta name="description" content="The public plan for the Gruber Intelligence Initiative: shared data, connected workflows and supervised AI across six businesses.">
    <link rel="stylesheet" href="assets/css/app.css?v=20260725-mobile-nav">
</head>
<body>
<div class="page-progress" aria-hidden="true"><span id="progressBar"></span></div>
<header class="site-header" id="siteHeader">
    <a class="brand" href="../index.php" aria-label="Gruber Companies home"><img class="header-brand-logo" src="../assets/images/gruber-main.png" alt="Gruber"></a>
    <nav aria-label="Public navigation">
        <a href="../app-prototype/index.php">Dashboard</a>
        <a href="../resume.php">Resume</a>
        <a class="active" href="#top">View Plan</a>
    </nav>
    <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-controls="mobileNav" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</header>
<aside class="mobile-nav-drawer" id="mobileNav" aria-hidden="true">
        <div class="mobile-nav-head">
            <img src="../assets/images/gruber-main.png" alt="Gruber">
            <button type="button" data-mobile-menu-close aria-label="Close navigation">×</button>
        </div>
        <div class="mobile-nav-links" role="navigation" aria-label="Mobile navigation">
            <a href="../app-prototype/index.php">Dashboard <span>→</span></a>
            <a href="../resume.php">Resume <span>→</span></a>
            <a class="active" href="#top">View Plan <span>→</span></a>
        </div>
        <div class="mobile-nav-contact">
            <span>Program contact</span>
            <strong>David Evans</strong>
            <a href="tel:+14802697433">(480) 269-7433</a>
        </div>
    </aside>
<button class="mobile-nav-backdrop" type="button" data-mobile-menu-close aria-label="Close navigation" tabindex="-1"></button>

<main id="top">
    <section class="scroll-chapter chapter-hero" data-chapter="hero">
        <div class="sticky-stage">
            <div class="ambient-grid"></div>
            <div class="stage-shell hero-layout">
                <div class="chapter-copy" data-step="0">
                    <span class="eyebrow">Public transformation plan</span>
                    <h1>The Gruber Intelligence Initiative</h1>
                    <p class="lead">Building the shared intelligence, workflows, and AI capabilities that will help six Gruber businesses operate more efficiently, make better decisions, and grow together.</p>
                    <div class="hero-actions">
                        <a class="button primary" href="#operating-system">Explore the system</a>
                        <a class="button secondary" href="#roadmap">Review the roadmap</a>
                    </div>
                </div>

                <div class="network-board" data-step="1" aria-label="Six businesses connecting to a shared intelligence layer">
                    <div class="network-core"><small>Shared</small><strong>INTELLIGENCE</strong><span>Data · Workflow · AI</span></div>
                    <?php foreach ($companies as $index => $company): ?>
                        <article class="business-node node-<?= $index + 1 ?>" data-sequence="<?= $index ?>">
                            <span><?= htmlspecialchars($company['code']) ?></span>
                            <strong><?= htmlspecialchars($company['name']) ?></strong>
                        </article>
                    <?php endforeach; ?>
                    <svg class="network-lines" viewBox="0 0 900 620" aria-hidden="true">
                        <path d="M170 125 C300 210 360 260 450 310"/>
                        <path d="M450 80 C450 190 450 235 450 310"/>
                        <path d="M730 125 C600 210 540 260 450 310"/>
                        <path d="M170 500 C300 420 360 365 450 310"/>
                        <path d="M450 545 C450 430 450 375 450 310"/>
                        <path d="M730 500 C600 420 540 365 450 310"/>
                    </svg>
                </div>

                <div class="chapter-hold" data-step="2">
                    <span>One group</span><b>Six businesses</b><em>One increasingly intelligent operating model</em>
                </div>
            </div>
            <div class="chapter-marker">01 <span>Initiative</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-light" data-chapter="challenge">
        <div class="sticky-stage">
            <div class="stage-shell two-column">
                <div class="chapter-copy" data-step="0">
                    <span class="eyebrow">The opportunity</span>
                    <h2>Shared challenges can become shared advantages.</h2>
                    <p>Purchasing, inventory and supplier decisions touch every Gruber company. The opportunity is to connect visibility without flattening the specialized workflows that make each business effective.</p>
                </div>
                <div class="state-panel">
                    <div class="state-column current" data-step="1">
                        <span>Current state</span>
                        <ul>
                            <li>Separate supplier records and pricing</li>
                            <li>Inventory distributed across locations</li>
                            <li>Manual reporting and late exceptions</li>
                            <li>Knowledge held by individuals</li>
                        </ul>
                    </div>
                    <div class="state-arrow" data-step="2">→</div>
                    <div class="state-column future" data-step="3">
                        <span>Future state</span>
                        <ul>
                            <li>Enterprise supplier intelligence</li>
                            <li>Cross-company inventory visibility</li>
                            <li>Live workflows and ranked risks</li>
                            <li>Institutional knowledge retained by AI</li>
                        </ul>
                    </div>
                </div>
                <div class="read-hold" data-step="4">The completed transformation remains visible here while the chapter stays pinned.</div>
            </div>
            <div class="chapter-marker">02 <span>Opportunity</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-dark" data-chapter="companies">
        <div class="sticky-stage">
            <div class="stage-shell">
                <div class="chapter-heading centered" data-step="0">
                    <span class="eyebrow light">Six operating companies</span>
                    <h2>Centralized intelligence. Local execution.</h2>
                    <p>Shared standards, contracts, reporting and AI support each company without removing operational ownership.</p>
                </div>
                <div class="company-grid">
                    <?php foreach ($companies as $index => $company): ?>
                        <article class="company-card" data-sequence="<?= $index ?>">
                            <span><?= htmlspecialchars($company['code']) ?></span>
                            <h3><?= htmlspecialchars($company['name']) ?></h3>
                            <p><?= htmlspecialchars($company['focus']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="read-hold light" data-step="3">Six specialized operating models become more valuable when the shared layer can see how they intersect.</div>
            </div>
            <div class="chapter-marker">03 <span>Businesses</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-light" id="operating-system" data-chapter="platform">
        <div class="sticky-stage">
            <div class="stage-shell">
                <div class="chapter-heading" data-step="0">
                    <span class="eyebrow">The operating system</span>
                    <h2>Reports become workflows. Workflows become supervised AI decisions.</h2>
                </div>
                <div class="module-grid">
                    <?php foreach ($modules as $index => $module): ?>
                        <article class="module-card" data-sequence="<?= $index ?>">
                            <span><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <h3><?= htmlspecialchars($module['title']) ?></h3>
                            <p><?= htmlspecialchars($module['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="process-line" data-step="3"><span>Report</span><b>→</b><span>Workflow</span><b>→</b><span>Agent</span><b>→</b><span>Human decision</span></div>
            </div>
            <div class="chapter-marker">04 <span>Platform</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-blue" data-chapter="data">
        <div class="sticky-stage">
            <div class="stage-shell data-layout">
                <div class="chapter-copy" data-step="0">
                    <span class="eyebrow light">Unified data foundation</span>
                    <h2>AI becomes useful only after the organization can trust the data beneath it.</h2>
                    <p>Common supplier, item, company, location and business-purpose standards create one dependable operating view.</p>
                </div>
                <div class="data-stack">
                    <div class="data-source source-1" data-step="1">Supplier records</div>
                    <div class="data-source source-2" data-step="1">Item and SKU data</div>
                    <div class="data-source source-3" data-step="2">Purchase orders</div>
                    <div class="data-source source-4" data-step="2">Inventory positions</div>
                    <div class="data-source source-5" data-step="3">Projects and work orders</div>
                    <div class="data-source source-6" data-step="3">Financial validation</div>
                    <div class="data-foundation" data-step="4"><strong>GRUBER DATA FOUNDATION</strong><span>Reliable · Comparable · Auditable · AI-ready</span></div>
                </div>
                <div class="read-hold light" data-step="5">The complete data foundation remains on screen for the final portion of the chapter.</div>
            </div>
            <div class="chapter-marker">05 <span>Data</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-dark" id="ai" data-chapter="ai">
        <div class="sticky-stage">
            <div class="stage-shell ai-layout">
                <div class="chapter-copy" data-step="0">
                    <span class="eyebrow light">Self-hosted AI integration</span>
                    <h2>Private by design. Governed by people.</h2>
                    <p>Approved operational data can be analyzed inside a Gruber-controlled environment, with external models used only when policy allows.</p>
                    <ul class="governance-list" data-step="2">
                        <li>Role-based data access</li>
                        <li>Human approval before transactions</li>
                        <li>Full evidence and audit history</li>
                        <li>No autonomous purchasing authority</li>
                    </ul>
                </div>
                <div class="agent-orbit" data-step="1">
                    <div class="ai-core"><small>Self-hosted</small><strong>GRUBER AI</strong><span>Human supervised</span></div>
                    <?php foreach ($agents as $index => $agent): ?>
                        <div class="agent-node agent-<?= $index + 1 ?>" data-sequence="<?= $index ?>"><b><?= $index + 1 ?></b><span><?= htmlspecialchars($agent) ?></span></div>
                    <?php endforeach; ?>
                </div>
                <div class="read-hold light" data-step="4">Agents prepare findings and actions. People retain authority, accountability and final judgment.</div>
            </div>
            <div class="chapter-marker">06 <span>AI Layer</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-light" id="roadmap" data-chapter="roadmap">
        <div class="sticky-stage">
            <div class="stage-shell roadmap-layout">
                <div class="chapter-heading" data-step="0">
                    <span class="eyebrow">Transformation roadmap</span>
                    <h2>Build the system in layers.</h2>
                    <p>Start with visibility. Standardize workflows. Add AI after the foundation is dependable.</p>
                </div>
                <div class="roadmap-track">
                    <article class="roadmap-card" data-sequence="0"><span>Phase 1 · Days 1–30</span><h3>Baseline</h3><p>Discovery, data standards, imports, initial reporting and pilot selection.</p></article>
                    <article class="roadmap-card" data-sequence="1"><span>Phase 2 · Days 31–90</span><h3>Workflow</h3><p>Supplier, item, PO, inventory, action and savings management.</p></article>
                    <article class="roadmap-card" data-sequence="2"><span>Phase 3 · 90–180 Days</span><h3>AI Copilot</h3><p>Briefings, consolidation, risk detection and recommended actions.</p></article>
                    <article class="roadmap-card" data-sequence="3"><span>Phase 4 · 180+ Days</span><h3>Enterprise Operating System</h3><p>Deeper integrations, predictive planning and organization-wide intelligence.</p></article>
                </div>
                <div class="read-hold" data-step="4">The roadmap stays pinned after all four phases are visible so leadership can review the complete sequence.</div>
            </div>
            <div class="chapter-marker">07 <span>Roadmap</span></div>
        </div>
    </section>

    <section class="scroll-chapter chapter-dark" data-chapter="outcomes">
        <div class="sticky-stage">
            <div class="stage-shell">
                <div class="chapter-heading centered" data-step="0">
                    <span class="eyebrow light">Strategic outcome</span>
                    <h2>Better purchasing strengthens every department.</h2>
                </div>
                <div class="outcome-grid">
                    <article data-sequence="0"><strong>Better</strong><span>supplier pricing, terms and accountability</span></article>
                    <article data-sequence="1"><strong>Less</strong><span>waste, duplicate buying and emergency freight</span></article>
                    <article data-sequence="2"><strong>Faster</strong><span>project, service, manufacturing and repair decisions</span></article>
                    <article data-sequence="3"><strong>More</strong><span>working capital, visibility and measurable profitability</span></article>
                </div>
                <div class="decision-panel" data-step="3">
                    <div><span>Recommended next decision</span><h3>Authorize the 30-day baseline and Gruber Power pilot.</h3><p>Assign the transformation team, approve access to operational data and select the first pilot category.</p></div>
                    <div class="decision-actions"><a class="button light" href="../app-prototype/index.php">Open dashboard</a><a class="button outline-light" href="../docs/ARCHITECTURE_AND_BUILD_PLAN.md">Review implementation plan</a></div>
                </div>
            </div>
            <div class="chapter-marker">08 <span>Decision</span></div>
        </div>
    </section>
</main>

<script src="assets/js/app.js?v=20260725-mobile-nav"></script>
</body>
</html>
