<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$root = __DIR__;
$configPath = $root . '/config.php';
$setupLock = $root . '/storage/manual-setup.lock';
$installedLock = $root . '/storage/installed.lock';
$error = '';
$success = false;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('gruber_manual_setup');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

if (empty($_SESSION['gruber_manual_setup_csrf'])) {
    $_SESSION['gruber_manual_setup_csrf'] = bin2hex(random_bytes(32));
}

function setup_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function setup_post(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function setup_valid_password(string $password): bool
{
    return strlen($password) >= 12
        && preg_match('/[a-z]/', $password) === 1
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/\d/', $password) === 1
        && preg_match('/[^A-Za-z0-9]/', $password) === 1;
}

if (is_file($setupLock)) {
    $success = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals((string)$_SESSION['gruber_manual_setup_csrf'], $token)) {
            throw new RuntimeException('The setup session expired. Reload the page and try again.');
        }
        if (!is_file($configPath)) {
            throw new RuntimeException('config.php is missing. Copy config-manual-example.php to config.php and enter the database credentials first.');
        }

        $config = require $configPath;
        if (!is_array($config) || empty($config['database']) || !is_array($config['database'])) {
            throw new RuntimeException('config.php does not contain a valid database configuration.');
        }

        $name = setup_post('admin_name');
        $email = strtolower(setup_post('admin_email'));
        $password = (string)($_POST['admin_password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !setup_valid_password($password)) {
            throw new RuntimeException('Enter a name, a valid email, and a password with at least 12 characters, mixed case, a number, and a symbol.');
        }

        $db = $config['database'];
        $host = (string)($db['host'] ?? 'localhost');
        $port = (int)($db['port'] ?? 3306);
        $database = (string)($db['name'] ?? '');
        $username = (string)($db['username'] ?? '');
        $dbPassword = (string)($db['password'] ?? '');
        $charset = (string)($db['charset'] ?? 'utf8mb4');
        if ($database === '' || $username === '') {
            throw new RuntimeException('The database name or username is missing from config.php.');
        }

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset),
            $username,
            $dbPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $requiredTables = ['companies', 'users', 'roles', 'user_roles', 'user_profiles', 'company_memberships'];
        $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ({$placeholders})"
        );
        $check->execute($requiredTables);
        if ((int)$check->fetchColumn() !== count($requiredTables)) {
            throw new RuntimeException('The imported database is incomplete. Required user, role, company, or membership tables are missing.');
        }

        $companyId = (int)$pdo->query('SELECT id FROM companies WHERE is_active = 1 ORDER BY id LIMIT 1')->fetchColumn();
        if ($companyId <= 0) {
            throw new RuntimeException('No active company record was found in the imported database.');
        }

        $roleStatement = $pdo->query("SELECT id FROM roles WHERE code = 'system_administrator' LIMIT 1");
        $roleId = (int)$roleStatement->fetchColumn();
        if ($roleId <= 0) {
            throw new RuntimeException('The system_administrator role is missing. Recheck the completed SQL import.');
        }

        $pdo->beginTransaction();
        try {
            $user = $pdo->prepare(
                "INSERT INTO users (primary_company_id, name, email, password_hash, job_title, status)
                 VALUES (:primary_company_id, :name, :email, :password_hash, :job_title, 'active')
                 ON DUPLICATE KEY UPDATE
                    primary_company_id = VALUES(primary_company_id),
                    name = VALUES(name),
                    password_hash = VALUES(password_hash),
                    job_title = VALUES(job_title),
                    status = 'active'"
            );
            $user->execute([
                'primary_company_id' => $companyId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'job_title' => 'System Administrator',
            ]);

            $findUser = $pdo->prepare('SELECT id FROM users WHERE email = :lookup_email LIMIT 1');
            $findUser->execute(['lookup_email' => $email]);
            $userId = (int)$findUser->fetchColumn();
            if ($userId <= 0) {
                throw new RuntimeException('The administrator user could not be read after creation.');
            }

            $roleLink = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, company_id)
                 SELECT :role_user_id, :role_id, NULL
                 WHERE NOT EXISTS (
                    SELECT 1 FROM user_roles
                    WHERE user_id = :existing_user_id AND role_id = :existing_role_id AND company_id IS NULL
                 )'
            );
            $roleLink->execute([
                'role_user_id' => $userId,
                'role_id' => $roleId,
                'existing_user_id' => $userId,
                'existing_role_id' => $roleId,
            ]);

            $parts = preg_split('/\s+/', $name, 2) ?: [$name, ''];
            $profile = $pdo->prepare(
                "INSERT INTO user_profiles (user_id, first_name, last_name, employment_status, password_reset_required)
                 VALUES (:profile_user_id, :first_name, :last_name, 'active', 0)
                 ON DUPLICATE KEY UPDATE
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    employment_status = 'active',
                    password_reset_required = 0"
            );
            $profile->execute([
                'profile_user_id' => $userId,
                'first_name' => (string)($parts[0] ?? $name),
                'last_name' => (string)($parts[1] ?? ''),
            ]);

            $membership = $pdo->prepare(
                "INSERT INTO company_memberships (user_id, company_id, is_primary, membership_status, assigned_by)
                 VALUES (:membership_user_id, :membership_company_id, 1, 'active', :membership_assigned_by)
                 ON DUPLICATE KEY UPDATE
                    is_primary = 1,
                    membership_status = 'active',
                    assigned_by = VALUES(assigned_by)"
            );
            $membership->execute([
                'membership_user_id' => $userId,
                'membership_company_id' => $companyId,
                'membership_assigned_by' => $userId,
            ]);

            $pdo->commit();
        } catch (Throwable $transactionError) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $transactionError;
        }

        if (!is_dir($root . '/storage') && !mkdir($root . '/storage', 0750, true) && !is_dir($root . '/storage')) {
            throw new RuntimeException('The storage directory could not be created.');
        }
        $stamp = 'Manual production setup completed ' . date(DATE_ATOM) . PHP_EOL;
        if (file_put_contents($setupLock, $stamp, LOCK_EX) === false) {
            throw new RuntimeException('The administrator was created, but storage/manual-setup.lock could not be written. Delete manual-admin.php manually after signing in.');
        }
        file_put_contents($installedLock, 'Installed manually ' . date(DATE_ATOM) . ' schema 3.0.0-phase2' . PHP_EOL, LOCK_EX);
        $_SESSION['gruber_manual_setup_csrf'] = bin2hex(random_bytes(32));
        $success = true;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manual administrator setup | Gruber Procurement Intelligence</title>
    <link rel="stylesheet" href="app/assets/css/app.css">
</head>
<body class="environment-gate-page">
<main class="environment-gate">
    <a class="gate-brand" href="index.php"><img src="app/assets/gruber-main.png" alt="Gruber"></a>
    <section class="gate-card login-card">
        <span class="eyebrow">Manual production setup</span>
        <?php if ($success): ?>
            <h1>Production access is ready</h1>
            <p>The first System Administrator is configured and the manual setup is locked.</p>
            <div class="gate-actions">
                <a class="button primary" href="app/login.php">Sign in</a>
                <a class="button secondary" href="index.php">Public website</a>
            </div>
            <p><small>Delete <code>manual-admin.php</code> from the server after confirming sign-in.</small></p>
        <?php else: ?>
            <h1>Create the first administrator</h1>
            <p>This page does not import or alter the schema. It uses the existing database configured in <code>config.php</code>.</p>
            <?php if ($error !== ''): ?><div class="flash flash-error"><?= setup_h($error) ?></div><?php endif; ?>
            <form method="post" class="stack-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= setup_h((string)$_SESSION['gruber_manual_setup_csrf']) ?>">
                <label><span>Full name</span><input name="admin_name" required></label>
                <label><span>Email</span><input type="email" name="admin_email" required autocomplete="email"></label>
                <label><span>Password</span><input type="password" name="admin_password" minlength="12" required autocomplete="new-password"><small>At least 12 characters with uppercase, lowercase, a number, and a symbol.</small></label>
                <button class="button primary full" type="submit">Create System Administrator</button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
