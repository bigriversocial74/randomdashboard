<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/public/account-menu.php';

$error = '';
$submitted = false;
$companies = [];
$pdo = production_database_connection();
if ($pdo instanceof PDO) {
    try {
        $companies = $pdo->query('SELECT id,name FROM companies WHERE is_active=1 ORDER BY name')->fetchAll();
    } catch (Throwable $exception) {
        $_SESSION['gruber_db_error'] = $exception->getMessage();
    }
}

if (request_method() === 'POST') {
    verify_csrf();
    try {
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Account requests are temporarily unavailable. Contact the program administrator.');
        }
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            throw new RuntimeException('The request could not be submitted.');
        }

        $firstName = post_string('first_name');
        $lastName = post_string('last_name');
        $email = strtolower(post_string('email'));
        $companyId = post_int('company_id');
        $jobTitle = post_string('job_title');
        $reason = post_string('reason');

        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter your name and a valid work email address.');
        }
        if ($companyId <= 0 || !array_filter($companies, static fn(array $company): bool => (int) $company['id'] === $companyId)) {
            throw new RuntimeException('Select the Gruber business connected to your request.');
        }
        if (mb_strlen($reason) < 10) {
            throw new RuntimeException('Briefly explain the access you need.');
        }

        $existingUser = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=:email LIMIT 1');
        $existingUser->execute(['email' => $email]);
        $existingRequest = $pdo->prepare(
            "SELECT id FROM access_requests
             WHERE LOWER(requester_email)=:email
               AND status='pending'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             LIMIT 1"
        );
        $existingRequest->execute(['email' => $email]);

        if (!$existingUser->fetchColumn() && !$existingRequest->fetchColumn()) {
            $statement = $pdo->prepare(
                "INSERT INTO access_requests(
                    requester_name,requester_email,requested_company_id,requested_role_code,request_reason,status
                 ) VALUES(:name,:email,:company_id,'read_only',:reason,'pending')"
            );
            $statement->execute([
                'name' => trim($firstName . ' ' . $lastName),
                'email' => $email,
                'company_id' => $companyId,
                'reason' => trim(($jobTitle !== '' ? "Job title: {$jobTitle}\n" : '') . $reason),
            ]);
            record_security_event($pdo, 'access_request_submitted', 'info', $email, null, [
                'company_id' => $companyId,
            ]);
        }

        unset($_SESSION['gruber_csrf']);
        $submitted = true;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request a Gruber account | Gruber</title>
    <meta name="description" content="Request access to the Gruber shared operating environment.">
    <link rel="stylesheet" href="assets/css/site.css?v=20260726-section1-quality">
    <script src="assets/js/site.js?v=20260726-section1-quality" defer></script>
</head>
<body class="auth-page">
<header class="site-header auth-site-header" id="siteHeader">
    <a class="wordmark" href="index.php" aria-label="Gruber Companies home"><img class="header-brand-logo" src="assets/images/gruber-main.png" alt="Gruber"></a>
    <nav aria-label="Primary navigation">
        <a href="<?= h(public_dashboard_href()) ?>">Dashboard</a>
        <a href="resume.php">Resume</a>
        <a href="ev-storage/index.php" target="_blank" rel="noopener noreferrer">EV Storage</a>
    </nav>
    <?php render_public_account_dropdown(); ?>
    <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-controls="mobileNav" aria-expanded="false"><span></span><span></span><span></span></button>
</header>
<aside class="mobile-nav-drawer" id="mobileNav" aria-hidden="true">
    <div class="mobile-nav-head"><img src="assets/images/gruber-main.png" alt="Gruber"><button type="button" data-mobile-menu-close aria-label="Close navigation">×</button></div>
    <div class="mobile-nav-links" role="navigation" aria-label="Mobile navigation">
        <a href="index.php">Home <span>→</span></a>
        <a href="<?= h(public_dashboard_href()) ?>">Dashboard <span>→</span></a>
        <a href="resume.php">Resume <span>→</span></a>
        <a href="ev-storage/index.php" target="_blank" rel="noopener noreferrer">EV Storage <span>→</span></a>
    </div>
    <?php render_public_mobile_account_links(); ?>
</aside>
<button class="mobile-nav-backdrop" type="button" data-mobile-menu-close aria-label="Close navigation" tabindex="-1"></button>

<main class="auth-main">
    <section class="auth-shell">
        <div class="auth-context">
            <p class="eyebrow">Gruber Intelligence Initiative</p>
            <h1>Request account access</h1>
            <p>Submit a production access request for review by a Gruber system administrator. Passwords are created only after an account is approved.</p>
            <div class="auth-context-grid">
                <article><span>01</span><div><strong>Verified request</strong><small>Your request enters the production access-review queue.</small></div></article>
                <article><span>02</span><div><strong>Role assignment</strong><small>An administrator assigns the correct company and permissions.</small></div></article>
                <article><span>03</span><div><strong>Secure activation</strong><small>Approved users receive controlled account-activation instructions.</small></div></article>
            </div>
        </div>
        <div class="auth-card">
            <div class="auth-card-head"><span class="auth-card-icon">＋</span><div><small>Production access</small><h2>Account request</h2></div></div>
            <?php if ($submitted): ?>
                <div class="flash flash-success" role="status">Your request has been received. An administrator will review the requested company and access level.</div>
                <div class="gate-actions"><a class="auth-submit" href="<?= h(app_url('login.php')) ?>">Return to sign in <span>→</span></a></div>
            <?php else: ?>
                <?php if ($error): ?><div class="flash flash-error" role="alert"><?= h($error) ?></div><?php endif; ?>
                <form class="auth-form" method="post">
                    <?= csrf_field() ?>
                    <label class="auth-field" style="position:absolute;left:-9999px" aria-hidden="true"><span>Website</span><input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    <div class="auth-field-grid">
                        <label class="auth-field"><span>First name</span><input type="text" name="first_name" autocomplete="given-name" value="<?= h(post_string('first_name')) ?>" required></label>
                        <label class="auth-field"><span>Last name</span><input type="text" name="last_name" autocomplete="family-name" value="<?= h(post_string('last_name')) ?>" required></label>
                    </div>
                    <label class="auth-field"><span>Work email</span><input type="email" name="email" autocomplete="email" value="<?= h(post_string('email')) ?>" required></label>
                    <label class="auth-field"><span>Gruber business</span><select name="company_id" required><option value="">Select a business</option><?php foreach ($companies as $company): ?><option value="<?= (int) $company['id'] ?>" <?= post_int('company_id') === (int) $company['id'] ? 'selected' : '' ?>><?= h($company['name']) ?></option><?php endforeach; ?></select></label>
                    <label class="auth-field"><span>Job title</span><input type="text" name="job_title" value="<?= h(post_string('job_title')) ?>"></label>
                    <label class="auth-field"><span>Access needed</span><textarea name="reason" rows="4" required><?= h(post_string('reason')) ?></textarea></label>
                    <button class="auth-submit" type="submit" <?= !$pdo instanceof PDO ? 'disabled' : '' ?>>Submit request <span>→</span></button>
                </form>
                <div class="auth-card-foot"><span>Already approved?</span><a href="<?= h(app_url('login.php')) ?>">Sign in</a></div>
            <?php endif; ?>
        </div>
    </section>
</main>
<footer class="site-footer auth-footer">
    <div class="footer-brand"><img src="assets/images/gruber-main.png" alt="Gruber"><p>Shared visibility, stronger workflows, and better decisions across the Gruber companies.</p></div>
    <div class="footer-section"><span>Explore</span><a href="index.php">Initiative</a><a href="<?= h(public_dashboard_href()) ?>">Dashboard</a><a href="resume.php">David's Resume</a></div>
    <div class="footer-section"><span>Account</span><a href="<?= h(app_url('login.php')) ?>">Sign in</a><a href="signup.php">Request account</a><a href="lost-password.php">Password assistance</a></div>
    <div class="footer-section footer-contact"><span>Program contact</span><strong>David Evans</strong><a href="tel:+14802697433">(480) 269-7433</a></div>
</footer>
</body>
</html>
