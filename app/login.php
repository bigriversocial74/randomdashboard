<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

if (current_user()) {
    redirect_to(app_url('dashboard.php'));
}
if (!database_available()) {
    render_environment_gate();
    exit;
}

$error = '';
if (request_method() === 'POST') {
    verify_csrf();
    if (production_login(post_string('email'), post_string('password'))) {
        $authenticated = current_user();
        if ($authenticated && !empty($authenticated['require_password_reset'])) {
            redirect_to(app_url('change-password.php'));
        }
        redirect_to(app_url('dashboard.php'));
    }
    $error = production_login_error();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Production sign in | Gruber</title>
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css')) ?>">
</head>
<body class="environment-gate-page">
<main class="environment-gate">
    <a class="gate-brand" href="<?= h(root_url('index.php')) ?>"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"></a>
    <section class="gate-card login-card">
        <span class="eyebrow">Production environment</span>
        <h1>Sign in</h1>
        <p>Use an active account stored in the configured production database.</p>
        <?php if ($error): ?><div class="flash flash-error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <label><span>Email</span><input type="email" name="email" required autocomplete="email" value="<?= h(post_string('email')) ?>"></label>
            <label><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label>
            <button class="button primary full" type="submit">Sign in</button>
        </form>
        <div class="gate-actions">
            <a class="button secondary" href="<?= h(root_url('signup.php')) ?>">Request Account</a>
            <a class="button ghost" href="<?= h(root_url('lost-password.php')) ?>">Password assistance</a>
        </div>
    </section>
</main>
</body>
</html>
