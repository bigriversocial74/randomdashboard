<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};
$read = static fn(string $path): string => (string) file_get_contents($root . '/' . $path);

$assert(!is_file($root . '/config.php'), 'A live config.php must never be packaged or committed.');
$assert(is_file($root . '/config-example.php'), 'config-example.php is missing.');
$assert(str_contains($read('.gitignore'), '/config.php'), '.gitignore does not protect config.php.');
$assert(str_contains($read('manual-admin.php'), 'Manual setup is disabled'), 'manual-admin.php is not disabled.');
$assert(str_contains($read('install.php'), 'Browser installer disabled'), 'install.php is not disabled.');
$assert(is_file($root . '/manual-admin-example.php'), 'The manual administrator example is missing.');

$auth = $read('includes/app/auth.php');
$assert(str_contains($auth, 'production_failed_login_count'), 'Database-backed login throttling is missing.');
$assert(str_contains($auth, 'password_needs_rehash'), 'Password rehashing is missing.');
$assert(str_contains($auth, 'gruber_production_session_tracked'), 'Production session tracking is missing.');
$assert(str_contains($auth, "change-password.php"), 'Forced password-change routing is missing.');
$assert(str_contains($auth, 'successful_sign_in'), 'Successful sign-in security logging is missing.');

$signup = $read('signup.php');
$assert(str_contains($signup, 'INSERT INTO access_requests'), 'Account requests are not persisted.');
$assert(!str_contains($signup, 'Authentication will be connected'), 'Signup still contains prototype language.');

$lost = $read('lost-password.php');
$assert(str_contains($lost, 'password_assistance_requested'), 'Password assistance is not recorded.');
$assert(!str_contains($lost, 'Secure email delivery will be connected later'), 'Password assistance still contains prototype language.');

$config = $read('config-example.php');
$assert(str_contains($config, 'session_absolute_lifetime_minutes'), 'Absolute session lifetime is missing from config-example.php.');
$assert(str_contains($config, 'trusted_proxies'), 'Trusted proxy configuration is missing.');

fwrite(STDOUT, "Section 1 static quality gates passed.\n");
