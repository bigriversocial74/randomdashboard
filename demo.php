<?php
declare(strict_types=1);
define('GRUBER_DEMO_SELECTOR', true);
require_once __DIR__ . '/includes/app/bootstrap.php';

// A production administrator may disable new Demo Mode entry. Existing
// database-free installations continue to expose Demo Mode through config.php.
if (production_database_available()) {
    try {
        if (empty(data_settings()['demo_mode_available'])) {
            http_response_code(403);
            echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Demo Mode unavailable</title><link rel="stylesheet" href="'.h(app_url('assets/css/app.css')).'"></head><body class="environment-gate-page"><main class="environment-gate"><section class="gate-card"><span class="eyebrow">Platform policy</span><h1>Demo Mode is unavailable</h1><p>A production administrator has disabled new Demo Mode sessions.</p><div class="gate-actions"><a class="button primary" href="'.h(app_url('login.php')).'">Production sign in</a><a class="button secondary" href="'.h(root_url('index.php')).'">Public website</a></div></section></main></body></html>';
            exit;
        }
    } catch (Throwable) {
        // Preserve database-free review access when production settings cannot load.
    }
}

// Prepare the isolated sample dataset for the account selector without enabling Demo Mode.
demo_initialize(true);

$accounts = [
    ['user_id'=>1,'role'=>'System Administrator','summary'=>'Full Admin Console, enterprise scope, security, settings, and all workflows.','icon'=>'⚙'],
    ['user_id'=>2,'role'=>'Executive','summary'=>'Enterprise dashboards, approved records, reports, and audit visibility.','icon'=>'⌂'],
    ['user_id'=>4,'role'=>'Company Administrator','summary'=>'Company-scoped users, access, records, and operational administration.','icon'=>'▦'],
    ['user_id'=>3,'role'=>'Procurement Manager','summary'=>'Supplier, purchasing, savings, imports, review, and approval leadership.','icon'=>'↗'],
    ['user_id'=>5,'role'=>'Data Contributor','summary'=>'Create and edit records, map imports, and submit work for review.','icon'=>'＋'],
    ['user_id'=>6,'role'=>'Reviewer','summary'=>'Validate evidence, request changes, approve records, and review audit history.','icon'=>'✓'],
    ['user_id'=>7,'role'=>'Read Only','summary'=>'View permitted company records without making changes.','icon'=>'◉'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Demo Mode | Gruber Procurement Intelligence</title>
    <meta name="description" content="Choose an isolated Gruber Procurement Intelligence demo role.">
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css?v=20260726-savings-tour')) ?>">
</head>
<body class="demo-entry-page">
<a class="app-skip-link" href="#mainContent">Skip to demo accounts</a>
<header class="demo-entry-header">
    <a href="<?= h(root_url('index.php')) ?>"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"></a>
</header>
<main class="demo-entry" id="mainContent" tabindex="-1">
    <section class="demo-entry-intro">
        <span class="demo-pill">DATABASE-FREE REVIEW ENVIRONMENT</span>
        <h1>Choose a Demo Mode role</h1>
        <p>Explore the complete application and Admin Console using fictional sample records. Each role has its own navigation, company scope, and permissions.</p>
        <div class="demo-entry-actions"><form action="<?= h(app_url('demo-login.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="1"><input type="hidden" name="return_to" value="<?= h(app_url('tour.php')) ?>"><button class="button primary" type="submit">Start guided tour</button></form><a class="button secondary" href="#demoAccounts">Choose a role</a></div>
        <div class="demo-safety-grid">
            <article><strong>Isolated</strong><span>Demo records remain in this PHP session.</span></article>
            <article><strong>Clearly labeled</strong><span>Every application page displays Demo Mode status.</span></article>
            <article><strong>Production-safe</strong><span>No sample record is written to MySQL.</span></article>
        </div>
        <?php if (demo_mode_active() && current_user()): ?>
            <div class="continue-session">
                <div><span>Current demo session</span><strong><?= h(current_user()['name']) ?> · <?= h(demo_role_name(current_role_codes()[0] ?? 'read_only')) ?></strong></div>
                <div class="continue-session-actions"><a class="button secondary" href="<?= h(app_url('tour.php')) ?>">Guided tour</a><a class="button primary" href="<?= h(app_url('dashboard.php')) ?>">Continue session</a></div>
            </div>
        <?php endif; ?>
    </section>

    <section class="demo-account-grid" id="demoAccounts" aria-label="Demo accounts">
        <?php foreach ($accounts as $account): $demoUser = demo_find('users', $account['user_id']); ?>
            <article class="demo-account-card">
                <div class="demo-account-icon"><?= h($account['icon']) ?></div>
                <span class="eyebrow"><?= h($account['role']) ?></span>
                <h2><?= h($demoUser['name']) ?></h2>
                <p><?= h($account['summary']) ?></p>
                <dl><div><dt>Email</dt><dd><?= h($demoUser['email']) ?></dd></div><div><dt>Primary company</dt><dd><?= h(demo_company_name($demoUser['primary_company_id'])) ?></dd></div></dl>
                <form action="<?= h(app_url('demo-login.php')) ?>" method="post">
                    <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $account['user_id'] ?>">
                    <button class="button secondary full" type="submit">Enter as <?= h($account['role']) ?> <span>→</span></button>
                </form>
            </article>
        <?php endforeach; ?>
    </section>
    <p class="demo-disclaimer">All company names are real Gruber business names supplied for the platform concept. Every person, supplier, transaction, amount, performance score, and operational event in Demo Mode is fictional sample data.</p>
</main>
</body>
</html>
