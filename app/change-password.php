<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';

$user = require_app_user();
if (demo_mode_active()) {
    flash('info', 'Passwords are not changed in Demo Mode.');
    redirect_to(app_url('profile.php'));
}

$error = '';
if (request_method() === 'POST') {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    try {
        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('The new password confirmation does not match.');
        }
        if (!password_meets_runtime_policy($newPassword)) {
            throw new RuntimeException('Use at least 12 characters with uppercase, lowercase, a number, and a symbol.');
        }

        $pdo = database_connection();
        if (!$pdo instanceof PDO) throw new RuntimeException('The production database is unavailable.');

        $statement = $pdo->prepare('SELECT password_hash FROM users WHERE id=:id LIMIT 1');
        $statement->execute(['id' => (int) $user['id']]);
        $passwordHash = (string) $statement->fetchColumn();
        if ($passwordHash === '' || !password_verify($currentPassword, $passwordHash)) {
            throw new RuntimeException('The current password is incorrect.');
        }
        if (password_verify($newPassword, $passwordHash)) {
            throw new RuntimeException('Choose a new password that differs from the current password.');
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET password_hash=:password_hash WHERE id=:id')->execute([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => (int) $user['id'],
            ]);
            $pdo->prepare('UPDATE user_profiles SET password_reset_required=0 WHERE user_id=:id')->execute([
                'id' => (int) $user['id'],
            ]);
            $pdo->prepare(
                'UPDATE user_sessions
                 SET revoked_at=NOW(),revoked_by=:user_id
                 WHERE user_id=:user_id AND id<>:current_session AND revoked_at IS NULL'
            )->execute([
                'user_id' => (int) $user['id'],
                'current_session' => hash('sha256', session_id()),
            ]);
            $pdo->commit();
        } catch (Throwable $transactionError) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $transactionError;
        }

        record_security_event($pdo, 'password_changed', 'info', (string) $user['email'], (int) $user['id']);
        unset($_SESSION['gruber_csrf']);
        flash('success', 'Your password was changed. Other active sessions were revoked.');
        redirect_to(app_url('profile.php'));
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

render_app_start(
    'Change password',
    'profile',
    'Account security',
    'Update your production password and revoke other active sessions.',
    ''
);
?>
<section class="panel security-form-panel">
    <header class="panel-head">
        <div><span class="eyebrow">Credential security</span><h2>Choose a new password</h2></div>
    </header>
    <?php if ($error): ?><div class="flash flash-error" role="alert"><?= h($error) ?></div><?php endif; ?>
    <form class="settings-form form-grid" method="post">
        <?= csrf_field() ?>
        <label class="span-2"><span>Current password</span><input type="password" name="current_password" autocomplete="current-password" required></label>
        <label><span>New password</span><input type="password" name="new_password" autocomplete="new-password" required></label>
        <label><span>Confirm new password</span><input type="password" name="confirm_password" autocomplete="new-password" required></label>
        <p class="span-2 form-help">Minimum 12 characters with uppercase, lowercase, a number, and a symbol.</p>
        <div class="span-2 page-actions">
            <a class="button secondary" href="<?= h(app_url('profile.php')) ?>">Cancel</a>
            <button class="button primary" type="submit">Change password</button>
        </div>
    </form>
</section>
<?php render_app_end(); ?>
