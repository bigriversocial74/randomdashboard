<?php
declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = '/gruber/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require dirname(__DIR__) . '/includes/public/account-menu.php';

$state = $argv[1] ?? 'logged-out';
if ($state === 'logged-in') {
    $_SESSION['gruber_demo_mode'] = true;
    $_SESSION['gruber_demo_user_id'] = 1;
    $_SESSION['gruber_demo_company_id'] = 'enterprise';
}

ob_start();
render_public_account_dropdown();
render_public_mobile_account_links();
$output = (string) ob_get_clean();

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

if ($state === 'logged-in') {
    $assert(str_contains($output, 'Profile settings'), 'Logged-in menu is missing Profile settings.');
    $assert(str_contains($output, 'Demo accounts'), 'Logged-in menu is missing Demo accounts.');
    $assert(str_contains($output, 'Sign out'), 'Logged-in menu is missing Sign out.');
    $assert(!str_contains($output, '<strong>Sign in</strong>'), 'Logged-in menu contains Sign in.');
    $assert(!str_contains($output, '<strong>Create Account</strong>'), 'Logged-in menu contains Create Account.');
    $assert(public_dashboard_href() === '/gruber/app/dashboard.php', 'Logged-in Dashboard route is incorrect.');
} else {
    $assert(str_contains($output, 'Sign in'), 'Logged-out menu is missing Sign in.');
    $assert(str_contains($output, 'Create Account'), 'Logged-out menu is missing Create Account.');
    $assert(str_contains($output, 'Demo accounts'), 'Logged-out menu is missing Demo accounts.');
    $assert(!str_contains($output, 'Profile settings'), 'Logged-out menu contains Profile settings.');
    $assert(!str_contains($output, 'Sign out'), 'Logged-out menu contains Sign out.');
    $assert(public_dashboard_href() === '/gruber/demo.php', 'Logged-out Dashboard route is incorrect.');
}

fwrite(STDOUT, "Account menu {$state} state passed.\n");
