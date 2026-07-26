<?php
declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = '/gruber/tests/auth_integration.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Gruber Quality Test';
require dirname(__DIR__) . '/includes/app/bootstrap.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(config_valid(), 'Integration config is invalid: ' . implode(' ', config_validation_errors()));
$pdo = production_database_connection();
$assert($pdo instanceof PDO, 'Production database connection failed.');

$email = 'quality-' . bin2hex(random_bytes(5)) . '@gruber.test';
$password = 'Section1!Quality2026';
$userId = 0;

try {
    $companyId = (int) $pdo->query('SELECT id FROM companies WHERE is_active=1 ORDER BY id LIMIT 1')->fetchColumn();
    $roleId = (int) $pdo->query("SELECT id FROM roles WHERE code='system_administrator' LIMIT 1")->fetchColumn();
    $assert($companyId > 0, 'No active company seed was found.');
    $assert($roleId > 0, 'The system_administrator role seed was not found.');

    $statement = $pdo->prepare(
        "INSERT INTO users(primary_company_id,name,email,password_hash,job_title,status)
         VALUES(:company_id,'Quality Test User',:email,:password_hash,'Automated Test','active')"
    );
    $statement->execute([
        'company_id' => $companyId,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();
    $pdo->prepare(
        "INSERT INTO user_profiles(user_id,first_name,last_name,employment_status,password_reset_required)
         VALUES(:user_id,'Quality','Test','active',0)"
    )->execute(['user_id' => $userId]);
    $pdo->prepare(
        "INSERT INTO company_memberships(user_id,company_id,is_primary,membership_status)
         VALUES(:user_id,:company_id,1,'active')"
    )->execute(['user_id' => $userId, 'company_id' => $companyId]);
    $pdo->prepare('INSERT INTO user_roles(user_id,role_id,company_id) VALUES(:user_id,:role_id,NULL)')
        ->execute(['user_id' => $userId, 'role_id' => $roleId]);

    $assert(!production_login($email, 'WrongPassword!1'), 'Invalid credentials were accepted.');
    $failedEvents = $pdo->prepare("SELECT COUNT(*) FROM security_events WHERE event_type='failed_sign_in' AND email=:email");
    $failedEvents->execute(['email' => $email]);
    $assert((int) $failedEvents->fetchColumn() === 1, 'Failed sign-in was not recorded.');

    $assert(production_login($email, $password), 'Valid credentials were rejected.');
    $user = current_user();
    $assert(is_array($user) && (int) $user['id'] === $userId, 'Authenticated user could not be resolved.');
    $assert(!empty($_SESSION['gruber_production_session_tracked']), 'Production session was not tracked.');
    $sessionId = hash('sha256', session_id());
    $sessionCheck = $pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE id=:id AND user_id=:user_id AND revoked_at IS NULL');
    $sessionCheck->execute(['id' => $sessionId, 'user_id' => $userId]);
    $assert((int) $sessionCheck->fetchColumn() === 1, 'Active session row was not created.');

    app_logout();
    $assert(empty($_SESSION['gruber_production_user_id']), 'Logout did not clear the production identity.');

    $pdo->prepare("UPDATE users SET status='locked' WHERE id=:id")->execute(['id' => $userId]);
    $assert(!production_login($email, $password), 'A locked account was allowed to sign in.');

    $pdo->prepare("UPDATE users SET status='active' WHERE id=:id")->execute(['id' => $userId]);
    $pdo->prepare('UPDATE user_profiles SET password_reset_required=1 WHERE user_id=:id')->execute(['id' => $userId]);
    $assert(production_login($email, $password), 'Reset-required account could not authenticate.');
    $resetUser = current_user();
    $assert(!empty($resetUser['require_password_reset']), 'Password-reset requirement was not exposed to the application.');
    $assert(password_meets_runtime_policy('ValidPolicy!2026'), 'Valid password policy sample was rejected.');
    $assert(!password_meets_runtime_policy('weak'), 'Weak password policy sample was accepted.');

    app_logout();
    fwrite(STDOUT, "Authentication integration tests passed.\n");
} finally {
    if ($userId > 0) {
        try {
            $pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $userId]);
            $pdo->prepare('DELETE FROM security_events WHERE email=:email')->execute(['email' => $email]);
        } catch (Throwable $cleanupError) {
            fwrite(STDERR, 'Cleanup warning: ' . $cleanupError->getMessage() . PHP_EOL);
        }
    }
}
