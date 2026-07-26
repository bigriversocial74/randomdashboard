<?php
declare(strict_types=1);
http_response_code(410);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manual setup disabled | Gruber Procurement Intelligence</title>
    <link rel="stylesheet" href="app/assets/css/app.css">
</head>
<body class="environment-gate-page">
<main class="environment-gate">
    <a class="gate-brand" href="index.php"><img src="app/assets/gruber-main.png" alt="Gruber"></a>
    <section class="gate-card login-card">
        <span class="eyebrow">Production security</span>
        <h1>Manual setup is disabled</h1>
        <p>The active deployment does not expose an administrator-creation endpoint. For a brand-new environment, copy <code>manual-admin-example.php</code> to a temporary private filename, complete setup once, then delete it immediately.</p>
        <div class="gate-actions">
            <a class="button primary" href="app/login.php">Production sign in</a>
            <a class="button secondary" href="index.php">Public website</a>
        </div>
    </section>
</main>
</body>
</html>
