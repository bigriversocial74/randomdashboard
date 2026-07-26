<?php require_once __DIR__ . '/includes/public/header.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Gruber Intelligence Initiative</title>
    <meta name="description" content="The public vision for shared intelligence, workflows and AI capabilities across the six Gruber businesses.">
    <link rel="stylesheet" href="assets/css/site.css?v=20260726-account-state-menu">
    <script src="assets/js/site.js?v=20260726-account-state-menu" defer></script>
</head>
<body>
<?php render_public_header(''); ?>

<main id="mainContent" tabindex="-1"><span id="top" aria-hidden="true"></span>
    <section class="hero-scroll" id="initiative" aria-label="The Gruber Intelligence Initiative">
        <div class="hero-sticky">
            <div class="blueprint" aria-hidden="true">
                <span class="tower tower-1"></span><span class="tower tower-2"></span><span class="tower tower-3"></span>
                <span class="tower tower-4"></span><span class="tower tower-5"></span><span class="tower tower-6"></span>
                <i class="flow flow-1"></i><i class="flow flow-2"></i><i class="flow flow-3"></i>
            </div>

            <div class="hero-content">
                <p class="eyebrow">A public vision for the next Gruber operating advantage</p>
                <h1>The Gruber<br>Intelligence<br>Initiative</h1>
                <p class="lead">Building the shared intelligence, workflows, and AI capabilities that will help six Gruber businesses operate more efficiently, make better decisions, and grow together.</p>
                <div class="actions hero-actions">
                    <a class="button primary" href="<?= public_dashboard_href() ?>">View Dashboard <span>→</span></a>
                    <a class="button secondary" href="resume.php">View David's Resume <span>→</span></a>
                </div>
            </div>

            <div class="network-stage" aria-label="Six Gruber businesses connecting through shared intelligence">
                <svg class="network-lines" viewBox="0 0 760 650" preserveAspectRatio="none" aria-hidden="true">
                    <line x1="380" y1="325" x2="158" y2="120" />
                    <line x1="380" y1="325" x2="380" y2="72" />
                    <line x1="380" y1="325" x2="604" y2="120" />
                    <line x1="380" y1="325" x2="625" y2="505" />
                    <line x1="380" y1="325" x2="380" y2="582" />
                    <line x1="380" y1="325" x2="135" y2="505" />
                    <path d="M158 120 C270 180, 500 180, 604 120" />
                    <path d="M135 505 C280 455, 480 455, 625 505" />
                </svg>

                <div class="intelligence-core">
                    <span>Shared</span>
                    <strong>INTELLIGENCE</strong>
                    <small>Data · Workflow · AI</small>
                </div>

                <article class="business-node node-gc"><b>GC</b><span>Communications</span><small>Products & manufacturing</small></article>
                <article class="business-node node-gps"><b>GPS</b><span>Power Services</span><small>Critical power & field service</small></article>
                <article class="business-node node-gts"><b>GTS</b><span>Technical Services</span><small>Projects & infrastructure</small></article>
                <article class="business-node node-gmc"><b>GMC</b><span>Motor Company</span><small>EV engineering & restoration</small></article>
                <article class="business-node node-evp"><b>EVP</b><span>EV Preserve</span><small>Storage & vehicle care</small></article>
                <article class="business-node node-gcp"><b>GCP</b><span>Commercial Properties</span><small>Facilities & shared assets</small></article>

                <div class="opportunity-stack" id="opportunities">
                    <article><span>01</span><div><strong>Enterprise buying power</strong><small>Combine common demand, contracts, freight and supplier relationships.</small></div></article>
                    <article><span>02</span><div><strong>Shared inventory intelligence</strong><small>Find usable stock across companies before purchasing duplicate inventory.</small></div></article>
                    <article><span>03</span><div><strong>Connected customer delivery</strong><small>Coordinate products, power, technical service, facilities and EV expertise.</small></div></article>
                    <article><span>04</span><div><strong>One executive operating view</strong><small>Connect spend, inventory, projects, service, risk and opportunity.</small></div></article>
                </div>
            </div>

            <div class="hero-chapter" aria-live="polite">
                <span>Scroll to connect the businesses</span>
                <div><i></i></div>
            </div>
        </div>
    </section>

    <section class="story-scroll data-story" id="data-foundation" aria-labelledby="dataTitle">
        <div class="story-sticky">
            <div class="story-copy">
                <p class="eyebrow">The foundation</p>
                <h2 id="dataTitle">One trusted data layer across six businesses.</h2>
                <p>Before automation can improve decisions, the organization needs a shared language for suppliers, items, inventory, projects, work orders, customers, locations and financial outcomes.</p>
                <div class="story-status"><span>Scroll to unify the data model</span><i></i></div>
            </div>

            <div class="data-visual" aria-label="Six operating systems flowing into a shared enterprise data layer">
                <div class="source-stack">
                    <article><b>GC</b><span>Manufacturing & products</span></article>
                    <article><b>GPS</b><span>Power & field service</span></article>
                    <article><b>GTS</b><span>Projects & infrastructure</span></article>
                    <article><b>GMC</b><span>Repairs & restoration</span></article>
                    <article><b>EVP</b><span>Storage & vehicle care</span></article>
                    <article><b>GCP</b><span>Properties & facilities</span></article>
                </div>
                <div class="data-streams" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
                <div class="enterprise-layer">
                    <small>Shared enterprise model</small>
                    <strong>GRUBER DATA FOUNDATION</strong>
                    <div><span>Suppliers</span><span>Items</span><span>Inventory</span><span>Projects</span><span>Work Orders</span><span>Financials</span></div>
                </div>
                <div class="data-outcomes">
                    <article><b>Reliable reporting</b><span>One definition for every executive metric.</span></article>
                    <article><b>Cross-company visibility</b><span>Find shared demand, inventory and supplier leverage.</span></article>
                    <article><b>AI-ready operations</b><span>Structured context that agents can analyze safely.</span></article>
                </div>
            </div>
        </div>
    </section>

    <section class="story-scroll ai-story" id="self-hosted-ai" aria-labelledby="aiTitle">
        <div class="story-sticky">
            <div class="story-copy ai-copy">
                <p class="eyebrow">Private intelligence</p>
                <h2 id="aiTitle">Self-hosted AI, governed inside the Gruber environment.</h2>
                <p>A private AI layer can analyze approved operational data without turning sensitive supplier, pricing, inventory, customer or service information into uncontrolled public data.</p>
                <div class="story-status"><span>Scroll to activate the private AI layer</span><i></i></div>
            </div>

            <div class="ai-visual" aria-label="Self-hosted AI architecture and supervised agents">
                <div class="secure-boundary">
                    <span class="boundary-label">Gruber-controlled environment</span>
                    <div class="ai-core">
                        <small>Self-hosted</small>
                        <strong>GRUBER AI</strong>
                        <span>Private models · Approved data · Audited actions</span>
                    </div>
                    <div class="agent-orbit">
                        <article class="agent agent-1"><b>Spend</b><span>Detect price variance</span></article>
                        <article class="agent agent-2"><b>Supplier</b><span>Monitor risk & performance</span></article>
                        <article class="agent agent-3"><b>Inventory</b><span>Recommend transfers</span></article>
                        <article class="agent agent-4"><b>Operations</b><span>Flag delays & shortages</span></article>
                        <article class="agent agent-5"><b>Executive</b><span>Prepare decision briefs</span></article>
                    </div>
                </div>
                <div class="governance-stack">
                    <article><span>01</span><div><b>Human approval</b><small>AI recommends. Authorized people decide.</small></div></article>
                    <article><span>02</span><div><b>Role-based access</b><small>Each user and agent sees only approved data.</small></div></article>
                    <article><span>03</span><div><b>Auditability</b><small>Prompts, evidence, decisions and actions remain traceable.</small></div></article>
                    <article><span>04</span><div><b>Model flexibility</b><small>Use local models first and approved external models when appropriate.</small></div></article>
                </div>
            </div>
        </div>
    </section>


    <section class="transformation-story" id="transformation" aria-labelledby="transformationTitle">
        <div class="transformation-sticky">
            <div class="transformation-heading">
                <p class="eyebrow">The transformation</p>
                <h2 id="transformationTitle">Move from fragmented operations to one intelligent enterprise.</h2>
                <p>Scroll to see how disconnected purchasing, inventory and reporting become shared workflows, governed data and decision-ready intelligence.</p>
            </div>
            <div class="state-comparison">
                <article class="state-panel current-state">
                    <small>Current state</small>
                    <h3>Knowledge is separated.</h3>
                    <ul>
                        <li>Different supplier and item records</li>
                        <li>Inventory hidden across locations</li>
                        <li>Manual, delayed reporting</li>
                        <li>Reactive purchasing and expedites</li>
                        <li>Institutional knowledge held by individuals</li>
                    </ul>
                </article>
                <div class="state-bridge" aria-hidden="true"><span></span><b>Shared intelligence</b><i>→</i></div>
                <article class="state-panel future-state">
                    <small>Future state</small>
                    <h3>Intelligence is shared.</h3>
                    <ul>
                        <li>Common supplier and item standards</li>
                        <li>Enterprise inventory visibility</li>
                        <li>Live executive reporting</li>
                        <li>Predictive planning and exception management</li>
                        <li>Knowledge retained through governed AI</li>
                    </ul>
                </article>
            </div>
            <div class="section-scroll-note">Scroll to complete the operating model <i></i></div>
        </div>
    </section>

    <section class="value-map-story" id="value-map" aria-labelledby="valueMapTitle">
        <div class="value-map-sticky">
            <div class="value-map-heading">
                <p class="eyebrow">Compounding value</p>
                <h2 id="valueMapTitle">Six businesses. Shared capabilities. More ways to create value.</h2>
                <p>The initiative makes existing relationships visible so procurement, inventory, technical expertise and customer opportunities can move across the organization deliberately.</p>
            </div>
            <div class="value-map" aria-label="Cross-business synergy map">
                <svg viewBox="0 0 900 560" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M170 115 C330 65, 560 65, 730 115" />
                    <path d="M170 115 C310 210, 320 350, 175 445" />
                    <path d="M730 115 C590 210, 580 350, 725 445" />
                    <path d="M175 445 C335 500, 560 500, 725 445" />
                    <path d="M450 85 L450 470" />
                    <path d="M170 115 C300 280, 600 280, 725 445" />
                    <path d="M730 115 C600 280, 300 280, 175 445" />
                </svg>
                <article class="map-node map-gc"><b>GC</b><span>Products & manufacturing</span></article>
                <article class="map-node map-gps"><b>GPS</b><span>Power systems & service</span></article>
                <article class="map-node map-gts"><b>GTS</b><span>Installation & projects</span></article>
                <article class="map-node map-gmc"><b>GMC</b><span>EV engineering & restoration</span></article>
                <article class="map-node map-evp"><b>EVP</b><span>EV preservation & care</span></article>
                <article class="map-node map-gcp"><b>GCP</b><span>Properties & facilities</span></article>
                <div class="map-core"><small>Shared enterprise layer</small><strong>GRUBER INTELLIGENCE</strong><span>Procurement · Inventory · Operations · AI</span></div>
                <div class="value-flows">
                    <article><b>Products → Projects</b><span>Communications inventory can support Technical installations.</span></article>
                    <article><b>Power → Infrastructure</b><span>Power and Technical can coordinate complete critical-facility solutions.</span></article>
                    <article><b>Engineering → Preservation</b><span>Motor and Power expertise strengthen long-term EV care.</span></article>
                    <article><b>Properties → Enterprise</b><span>Facilities create shared operating, storage and expansion opportunities.</span></article>
                </div>
            </div>
        </div>
    </section>

    <section class="agent-story" id="agents" aria-labelledby="agentsTitle">
        <div class="agent-sticky">
            <div class="agent-heading">
                <p class="eyebrow">AI in operation</p>
                <h2 id="agentsTitle">Every report can become a supervised agent workflow.</h2>
                <p>Agents continuously analyze approved data, explain their evidence and recommend action. People remain responsible for approvals and execution.</p>
            </div>
            <div class="agent-columns">
                <article><span>01</span><b>Executive Briefing</b><small>Summarizes risks, savings, service impact and decisions required.</small><em>Output: weekly leadership brief</em></article>
                <article><span>02</span><b>Supplier Intelligence</b><small>Detects duplicates, price movement, contract gaps and performance risks.</small><em>Output: supplier action queue</em></article>
                <article><span>03</span><b>Inventory Transfer</b><small>Finds compatible inventory across companies before a new purchase is placed.</small><em>Output: transfer recommendation</em></article>
                <article><span>04</span><b>PO Risk</b><small>Ranks late, unconfirmed and long-lead orders by customer and operational impact.</small><em>Output: escalation priorities</em></article>
                <article><span>05</span><b>Savings Opportunity</b><small>Surfaces pricing, freight, terms, inventory and process improvements.</small><em>Output: validated opportunity draft</em></article>
                <article><span>06</span><b>Data Quality</b><small>Flags missing categories, invalid units, duplicate records and incomplete transactions.</small><em>Output: correction workflow</em></article>
                <article><span>07</span><b>Agent Workspace</b><small>Gives teams one supervised chat interface for system prompts, evidence-backed queries and role-aware responses.</small><em>Output: guided analyst conversation</em></article>
            </div>
            <div class="agent-governance"><b>Human-supervised by design</b><span>Evidence attached · Access controlled · Actions audited · Approvals required</span></div>
        </div>
    </section>

    <section class="launch-section" id="launch" aria-labelledby="launchTitle">
        <div class="launch-wrap">
            <div class="launch-heading">
                <p class="eyebrow">A practical start</p>
                <h2 id="launchTitle">Establish the baseline in 30 days. Prove the model through one focused pilot.</h2>
            </div>
            <div class="launch-grid">
                <article><span>Week 1</span><b>Discover</b><p>Confirm governance, interview each function, map processes and identify systems and data owners.</p></article>
                <article><span>Week 2</span><b>Collect & standardize</b><p>Export purchasing and inventory data, normalize suppliers, items, categories and locations.</p></article>
                <article><span>Week 3</span><b>Report & analyze</b><p>Validate totals with Accounting and identify spend, inventory, supplier and delivery opportunities.</p></article>
                <article><span>Week 4</span><b>Pilot & decide</b><p>Launch the initial category pilot, approve interim policies and present the 90-day roadmap.</p></article>
            </div>
            <div class="pilot-case">
                <div><small>Recommended first pilot</small><h3>Gruber Power Services</h3><p>Begin with batteries, UPS service parts or refurbished-equipment components—categories where availability, working capital, legacy support and supplier performance are all measurable.</p></div>
                <div class="pilot-metrics"><article><span>Baseline</span><b>Spend · inventory · lead time · stockouts</b></article><article><span>Supplier strategy</span><b>Price · terms · freight · service levels</b></article><article><span>Success measures</span><b>Savings · availability · turns · delivery</b></article></div>
            </div>
        </div>
    </section>

    <section class="synergy-summary" aria-labelledby="synergyTitle">
        <div class="synergy-sticky">
            <div class="synergy-heading">
                <p class="eyebrow">The strategic opportunity</p>
                <h2 id="synergyTitle">Six specialized companies already share the ingredients of one stronger enterprise.</h2>
                <p>Products, infrastructure, power, technical service, EV engineering, preservation and property operations create natural points of connection. The initiative makes those connections visible, measurable and repeatable.</p>
            </div>
            <div class="synergy-grid">
                <article><small>01</small><b>Source together</b><span>Use combined spend to improve pricing, terms, availability and supplier accountability.</span></article>
                <article><small>02</small><b>Move inventory intelligently</b><span>Transfer compatible materials, parts and equipment before creating new demand.</span></article>
                <article><small>03</small><b>Deliver together</b><span>Build coordinated solutions across communications, critical power, projects, facilities and EV operations.</span></article>
                <article><small>04</small><b>Learn together</b><span>Turn operational activity into shared data, institutional knowledge and AI-assisted decisions.</span></article>
            </div>
            <div class="synergy-progress" aria-hidden="true"><i></i></div>
        </div>
    </section>

    <section class="final-cta-scroll" id="next-step" aria-labelledby="nextStepTitle">
        <div class="final-cta-sticky">
            <div class="final-cta-grid" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
            <div class="final-cta-content">
                <p class="eyebrow">Continue the conversation</p>
                <h2 id="nextStepTitle">See the operating vision in action—and meet the builder behind it.</h2>
                <p>Explore the interactive procurement dashboard or review David Evans's background, experience, and approach to the Gruber Intelligence Initiative.</p>
                <div class="final-cta-actions">
                    <a class="button primary" href="<?= public_dashboard_href() ?>">View Dashboard <span>→</span></a>
                    <a class="button secondary" href="resume.php">View David's Resume <span>→</span></a>
                </div>
            </div>
            <div class="final-cta-progress" aria-hidden="true"><i></i></div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-brand">
        <img src="assets/images/gruber-main.png" alt="Gruber">
        <p>Shared visibility, stronger workflows, and better decisions across the Gruber companies.</p>
    </div>
    <div class="footer-section">
        <span>Explore</span>
        <a href="<?= public_dashboard_href() ?>">Dashboard</a>
        <a href="resume.php">David's Resume</a>
        <a href="ev-storage/index.php" target="_blank" rel="noopener noreferrer">EV Storage</a>
    </div>
    <div class="footer-section">
        <span>Account</span>
        <a href="signin.php">Sign in</a>
        <a href="signup.php">Create account</a>
        <a href="lost-password.php">Forgot password</a>
    </div>
    <div class="footer-section footer-contact">
        <span>Program contact</span>
        <strong>David Evans</strong>
        <a href="tel:+14802697433">(480) 269-7433</a>
    </div>
    <div class="footer-bottom">
        <span>Gruber Intelligence Initiative</span>
        <span>Concept platform · Phoenix, Arizona</span>
    </div>
</footer>
</body>
</html>
