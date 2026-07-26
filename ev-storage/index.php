<?php
declare(strict_types=1);

$services = [
    [
        'icon' => 'snow',
        'title' => 'Climate Control',
        'body' => 'Precision environment maintained year-round for optimal battery and vehicle health.',
    ],
    [
        'icon' => 'battery',
        'title' => 'Battery Care',
        'body' => 'Smart maintenance cycles and charge management preserve longevity and readiness.',
    ],
    [
        'icon' => 'bolt',
        'title' => 'Charge Management',
        'body' => 'Managed charging windows reduce stress and ensure your EV is ready when you are.',
    ],
    [
        'icon' => 'car',
        'title' => 'Pickup & Delivery',
        'body' => 'Concierge pickup and return delivery, on your schedule, anywhere you are.',
    ],
];

$process = [
    ['icon' => 'calendar', 'number' => '1', 'title' => 'Schedule', 'body' => 'Book your storage or service online.'],
    ['icon' => 'car', 'number' => '2', 'title' => 'We Collect', 'body' => 'We pick up your EV with white-glove care.'],
    ['icon' => 'shield', 'number' => '3', 'title' => 'We Protect', 'body' => 'Stored in secure, climate-controlled vaults.'],
    ['icon' => 'tool', 'number' => '4', 'title' => 'We Deliver', 'body' => 'Return delivery when you are ready to drive.'],
];

function icon(string $name): string
{
    $icons = [
        'shield' => '<path d="M12 3 5 6v5c0 4.4 2.9 8 7 10 4.1-2 7-5.6 7-10V6l-7-3Z"/><path d="m9.5 12 1.7 1.7 3.6-4"/>',
        'snow' => '<path d="M12 2v20M4.2 6.5l15.6 9M4.2 17.5l15.6-9M7 3.8 12 7l5-3.2M7 20.2 12 17l5 3.2M3.6 9.5 7 12l-3.4 2.5M20.4 9.5 17 12l3.4 2.5"/>',
        'battery' => '<rect x="5" y="6" width="14" height="13" rx="2"/><path d="M9 3h6v3M9 11h6M12 8v6"/>',
        'bolt' => '<path d="m13 2-8 12h7l-1 8 8-12h-7l1-8Z"/>',
        'car' => '<path d="m5 11 1.7-4h10.6l1.7 4M4 11h16v7H4zM7 18v2M17 18v2M7 14h.01M17 14h.01"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>',
        'tool' => '<path d="M14.7 6.3a4 4 0 0 0-5-5L12 3.6 8.6 7 6.3 4.7a4 4 0 0 0 5 5L4 17l3 3 7.7-7.7a4 4 0 0 0 0-6Z"/>',
        'thermo' => '<path d="M14 14.8V5a4 4 0 0 0-8 0v9.8a6 6 0 1 0 8 0Z"/><path d="M10 6v10"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M15 8l2 2M18 5l2 2"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'arrow' => '<path d="M5 12h14M14 7l5 5-5 5"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
    ];

    $path = $icons[$name] ?? $icons['check'];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#02060b">
    <title>EV Storage — Protect Performance. Preserve Value.</title>
    <meta name="description" content="Ultra-secure, climate-controlled electric vehicle storage, battery care, connected monitoring, pickup and delivery.">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<div class="site-shell">
    <header class="site-header" id="top">
        <a class="brand" href="#top" aria-label="EV Storage home">
            <span>EV</span> STORAGE
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
            <span class="sr-only">Open navigation</span>
            <?= icon('menu') ?>
        </button>
        <nav class="primary-nav" id="primary-nav" aria-label="Primary navigation">
            <a href="#services">Services</a>
            <a href="#facilities">Facilities</a>
            <a href="#technology">Technology</a>
            <a href="#about">About</a>
            <a href="#membership">Membership</a>
        </nav>
        <a class="header-cta" href="#membership">Get Started</a>
    </header>

    <main>
        <section class="hero scroll-stage" id="home" data-scroll-stage>
            <div class="hero-media" aria-hidden="true">
                <img src="assets/images/hero-roadster.png" alt="" width="1672" height="941">
                <div class="hero-vignette"></div>
            </div>
            <div class="hero-content content-width">
                <div class="hero-copy" data-parallax="0.08">
                    <p class="eyebrow reveal-up"><span class="eyebrow-icon">P</span> Premium EV Storage</p>
                    <h1 class="reveal-up" style="--delay:.08s">Protect Performance.<br>Preserve Value.</h1>
                    <span class="heading-rule reveal-up" style="--delay:.16s"></span>
                    <p class="hero-lede reveal-up" style="--delay:.22s">Ultra-secure, climate-controlled storage for your EV. Engineered for battery health, protected with white-glove care and monitored 24/7.</p>
                    <div class="hero-actions reveal-up" style="--delay:.3s">
                        <a class="button button-primary" href="#membership">Explore Membership</a>
                        <a class="button button-link" href="#process">Schedule a Tour <?= icon('arrow') ?></a>
                    </div>
                </div>

                <div class="hero-specs reveal-up" style="--delay:.36s">
                    <article>
                        <span class="spec-icon"><?= icon('shield') ?></span>
                        <div><strong>Bank-Grade Security</strong><span>24/7 Protected</span></div>
                    </article>
                    <article>
                        <span class="spec-icon"><?= icon('thermo') ?></span>
                        <div><strong>Climate Controlled</strong><span>68°F / 20°C</span></div>
                    </article>
                    <article>
                        <span class="spec-icon"><?= icon('bolt') ?></span>
                        <div><strong>Battery Care</strong><span>Optimized Daily</span></div>
                    </article>
                </div>
            </div>
            <a class="scroll-cue" href="#services" aria-label="Continue to services"><span></span></a>
        </section>

        <section class="section services-section" id="services">
            <div class="section-number">02</div>
            <div class="content-width services-layout">
                <div class="section-intro reveal-left">
                    <p class="eyebrow">Built for Excellence</p>
                    <h2>Every detail<br>engineered around<br>your EV.</h2>
                    <p>From the environment to the electronics, we protect what drives you.</p>
                </div>
                <div class="service-grid">
                    <?php foreach ($services as $index => $service): ?>
                        <article class="service-card reveal-up" style="--delay:<?= number_format($index * .08, 2) ?>s">
                            <span class="card-icon"><?= icon($service['icon']) ?></span>
                            <h3><?= htmlspecialchars($service['title'], ENT_QUOTES) ?></h3>
                            <p><?= htmlspecialchars($service['body'], ENT_QUOTES) ?></p>
                            <a href="#membership">Learn more <?= icon('arrow') ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section technology-section scroll-stage" id="technology" data-scroll-stage>
            <div class="technology-media" aria-hidden="true">
                <img class="technology-wave" src="assets/images/digital-wave.png" alt="" width="1672" height="941">
            </div>
            <div class="content-width technology-layout">
                <div class="phone-wrap reveal-left" data-parallax="0.06">
                    <img src="assets/images/connected-care-app.png" alt="EV Storage connected care mobile application showing a stored roadster, battery level, range, temperature, location and service schedule" width="941" height="1672">
                </div>
                <div class="section-number technology-number">03</div>
                <div class="technology-copy reveal-right">
                    <p class="eyebrow">Connected Care</p>
                    <h2>Total visibility.<br>Complete peace of mind.</h2>
                    <span class="heading-rule"></span>
                    <p>Monitor your vehicle in real time with 24/7 remote access to status, location, and system alerts.</p>
                    <ul class="feature-list">
                        <li><?= icon('battery') ?><span>Live battery status &amp; range</span></li>
                        <li><?= icon('pin') ?><span>Real-time location &amp; facility access</span></li>
                        <li><?= icon('bell') ?><span>Alerts &amp; maintenance updates</span></li>
                        <li><?= icon('key') ?><span>Secure digital access</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section process-section" id="process">
            <div class="section-number">04</div>
            <div class="content-width process-layout">
                <div class="section-intro reveal-left">
                    <p class="eyebrow">Our Process</p>
                    <h2>Simple. Secure.<br>Seamless.</h2>
                    <p>A premium experience from drop-off to delivery.</p>
                </div>
                <div class="process-track" data-process-track>
                    <span class="process-line" aria-hidden="true"><span></span></span>
                    <?php foreach ($process as $index => $step): ?>
                        <article class="process-step reveal-up" style="--delay:<?= number_format($index * .1, 2) ?>s">
                            <span class="process-icon"><?= icon($step['icon']) ?></span>
                            <strong><?= htmlspecialchars($step['number'], ENT_QUOTES) ?>. <?= htmlspecialchars($step['title'], ENT_QUOTES) ?></strong>
                            <p><?= htmlspecialchars($step['body'], ENT_QUOTES) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="battery-section" aria-label="Battery stewardship">
            <div class="battery-image reveal-left">
                <img src="assets/images/battery-system.png" alt="Exploded view of a modern electric vehicle battery assembly" width="1448" height="1086">
            </div>
            <div class="battery-copy reveal-right">
                <p class="eyebrow">Battery Stewardship</p>
                <h2>Storage designed around the most valuable system in your EV.</h2>
                <p>Temperature stability, smart charging and documented care protocols help reduce long-term battery stress.</p>
                <div class="battery-points">
                    <span><?= icon('check') ?> Condition monitoring</span>
                    <span><?= icon('check') ?> Charge-cycle planning</span>
                    <span><?= icon('check') ?> Readiness reporting</span>
                </div>
            </div>
        </section>

        <section class="section facility-section" id="facilities">
            <div class="facility-image" aria-hidden="true">
                <img src="assets/images/storage-facility.png" alt="" width="1672" height="941">
            </div>
            <div class="facility-overlay"></div>
            <div class="section-number">05</div>
            <div class="content-width facility-layout">
                <div class="section-intro reveal-left">
                    <p class="eyebrow">Built Different</p>
                    <h2>Facilities as advanced<br>as the vehicles<br>we protect.</h2>
                </div>
                <div class="facility-stats reveal-up">
                    <article><strong>100%</strong><span>Climate Controlled</span></article>
                    <article><strong>24/7</strong><span>Security Monitoring</span></article>
                    <article><strong>99.9%</strong><span>Power Reliability</span></article>
                    <article><strong>&lt; 2 min</strong><span>Response Time</span></article>
                </div>
            </div>
        </section>

        <section class="about-section" id="about">
            <div class="about-media" aria-hidden="true">
                <img src="assets/images/roadster-drive.png" alt="" width="1672" height="941">
            </div>
            <div class="about-overlay"></div>
            <div class="content-width about-copy reveal-up">
                <p class="eyebrow">Ready When You Are</p>
                <h2>Stored with precision.<br>Delivered for the drive.</h2>
                <p>Every handoff is documented, inspected and coordinated around your schedule.</p>
            </div>
        </section>

        <section class="membership-section" id="membership">
            <div class="membership-image" aria-hidden="true">
                <img src="assets/images/roadster-detail.png" alt="" width="1122" height="1402">
            </div>
            <div class="content-width membership-layout">
                <div class="membership-copy reveal-left">
                    <p class="eyebrow">Private Membership</p>
                    <h2>Protection without compromise.</h2>
                    <p>Request a private consultation to discuss vehicle requirements, preferred access, transportation and care services.</p>
                </div>
                <form class="membership-form reveal-right" action="#" method="post" onsubmit="return false">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" autocomplete="name" placeholder="Your name">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" autocomplete="email" placeholder="you@example.com">
                    </label>
                    <label>
                        <span>Vehicle</span>
                        <input type="text" name="vehicle" placeholder="Year, make and model">
                    </label>
                    <button class="button button-primary" type="submit">Request Membership</button>
                    <p class="form-note">Prototype form — no information is transmitted.</p>
                </form>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <a class="brand" href="#top"><span>EV</span> STORAGE</a>
        <p>Premium electric vehicle storage and connected care.</p>
        <a href="#top">Back to top <?= icon('arrow') ?></a>
    </footer>
</div>
<script src="assets/js/site.js"></script>
</body>
</html>
