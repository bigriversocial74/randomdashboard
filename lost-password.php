<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/public/account-menu.php';

$error = '';
$submitted = false;
if (request_method() === 'POST') {
    verify_csrf();
    try {
        $pdo = production_database_connection();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Password assistance is temporarily unavailable. Contact the program administrator.');
        }
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            throw new RuntimeException('The request could not be submitted.');
        }
        $email = strtolower(post_string('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid work email address.');
        }

        $statement = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=:email LIMIT 1');
        $statement->execute(['email' => $email]);
        $userId = (int) ($statement->fetchColumn() ?: 0);

        $recent = $pdo->prepare(
            "SELECT COUNT(*) FROM security_events
             WHERE event_type='password_assistance_requested'
               AND LOWER(email)=:email
               AND occurred_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );
        $recent->execute(['email' => $email]);
        if ((int) $recent->fetchColumn() === 0) {
            record_security_event(
                $pdo,
                'password_assistance_requested',
                'info',
                $email,
                $userId > 0 ? $userId : null,
                ['workflow' => 'administrator_review']
            );
        }

        unset($_SESSION['gruber_csrf']);
        $submitted = true;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
$supportEmail = (string) (app_config()['app']['support_email'] ?? 'support@example.com');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password assistance | Gruber</title>
    <meta name="description" content="Request administrator assistance with a Gruber production account password.">
    <link rel="stylesheet" href="assets/css/site.css?v=20260726-section1-quality">
    <script src="assets/js/site.js?v=20260726-section1-quality" defer></script>
</head>
<body class="auth-page">
<header class="site-header auth-site-header" id="siteHeader">
    <a class="wordmark" href="index.php" aria-label="Gruber Companies home"><img class="header-brand-logo" src="assets/images/gruber-main.png" alt="Gruber"></a>
    <nav aria-label="Primary navigation"><a href="<?= h(public_dashboard_href()) ?>">Dashboard</a><a href="resume.php">Resume</a><a href="ev-storage/index.php" target="_blank" rel="noopener noreferrer">EV Storage</a></nav>
    <?php render_public_account_dropdown(); ?>
    <button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-controls="mobileNav" aria-expanded="false"><span></span><span></span><span></span></button>
</header>
<aside class="mobile-nav-drawer" id="mobileNav" aria-hidden="true">
    <div class="mobile-nav-head"><img src="assets/images/gruber-main.png" alt="Gruber"><button type="button" data-mobile-menu-close aria-label="Close navigation">×</button></div>
    <div class="mobile-nav-links" role="navigation" aria-label="Mobile navigation"><a href="index.php">Home <span>→</span></a><a href="<?= h(public_dashboard_href()) ?>">Dashboard <span>→</span></a><a href="resume.php">Resume <span>→</span></a></div>
    <?php render_public_mobile_account_links(); ?>
</aside>
<button class="mobile-nav-backdrop" type="button" data-mobile-menu-close aria-label="Close navigation" tabindex="-1"></button>

<main class="auth-main">
    <section class="auth-shell">
        <div class="auth-context">
            <p class="eyebrow">Account security</p>
            <h1>Request password assistance</h1>
            <p>For the current manual production environment, password assistance is reviewed by a system administrator. The response does not reveal whether an address exists.</p>
            <div class="auth-context-grid">
                <article><span>01</span><div><strong>Request recorded</strong><small>A security event is added to the administrator review queue.</small></div></article>
                <article><span>02</span><div><strong>Identity reviewed</strong><small>An administrator confirms the account and company assignment.</small></div></article>
                <article><span>03</span><div><strong>Access restored</strong><small>The administrator provides a controlled recovery path.</small></div></article>
            </div>
        </div>
        <div class="auth-card">
            <div class="auth-card-head"><span class="auth-card-icon">⌁</span><div><small>Production account</small><h2>Password assistance</h2></div></div>
            <?php if ($submitted): ?>
                <div class="flash flash-success" role="status">If the email is connected to an account, the request is now available for administrator review.</div>
                <div class="auth-card-foot"><span>Need direct help?</span><a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a></div>
            <?php else: ?>
                <?php if ($error): ?><div class="flash flash-error" role="alert"><?= h($error) ?></div><?php endif; ?>
                <form class="auth-form" method="post">
                    <?= csrf_field() ?>
                    <label class="auth-field" style="position:absolute;left:-9999px" aria-hidden="true"><span>Website</span><input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    <label class="auth-field"><span>Work email</span><input type="email" name="email" autocomplete="email" value="<?= h(post_string('email')) ?>" required></label>
                    <button class="auth-submit" type="submit">Request assistance <span>→</span></button>
                </form>
                <div class="auth-card-foot"><span>Remembered your password?</span><a href="<?= h(app_url('login.php')) ?>">Return to sign in</a></div>
            <?php endif; ?>
        </div>
    </section>
</main>
<footer class="site-footer auth-footer">
    <div class="footer-brand"><img src="assets/images/gruber-main.png" alt="Gruber"><p>Shared visibility, stronger workflows, and better decisions across the Gruber companies.</p></div>
    <div class="footer-section"><span>Account</span><a href="<?= h(app_url('login.php')) ?>">Sign in</a><a href="signup.php">Request account</a></div>
    <div class="footer-section footer-contact"><span>Program contact</span><strong>David Evans</strong><a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a></div>
</footer>
</body>
</html>
