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
    <title>Installer disabled | Gruber Procurement Intelligence</title>
    <link rel="stylesheet" href="app/assets/css/app.css">
</head>
<body class="environment-gate-page">
<main class="environment-gate">
    <a class="gate-brand" href="index.php"><img src="app/assets/gruber-main.png" alt="Gruber"></a>
    <section class="gate-card login-card">
        <span class="eyebrow">Manual production setup</span>
        <h1>Browser installer disabled</h1>
        <p>This deployment uses a manual SQL import and a manually maintained <code>config.php</code>. No browser-based installer is available.</p>
        <div class="gate-actions">
            <a class="button primary" href="app/login.php">Production sign in</a>
            <a class="button secondary" href="index.php">Public website</a>
        </div>
    </section>
</main>
</body>
</html>
