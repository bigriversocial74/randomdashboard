<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$page = $argv[1] ?? '';
$required = array_slice($argv, 2);
$allowed = [
    'dashboard.php','briefing.php','reports.php','suppliers.php','sourcing.php','scenarios.php','mitigations.php','items.php','purchase-orders.php',
    'inventory.php','scorecards.php','imports.php','discovery.php','data-collection.php','agent.php','savings.php','approvals.php','tour.php',
    'notifications.php','profile.php','settings.php','change-password.php','admin/index.php','admin/users.php',
    'admin/roles.php','admin/companies.php','admin/access-requests.php','admin/sessions.php',
    'admin/security.php','admin/audit.php','admin/settings.php','admin/environment.php',
];
if (!in_array($page, $allowed, true)) {
    fwrite(STDERR, "Unsupported render target: {$page}\n");
    exit(2);
}
$_SERVER['SCRIPT_NAME'] = '/gruber/app/' . $page;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Gruber quality test';
require $root . '/includes/app/bootstrap.php';
if (!demo_start_session(1)) {
    fwrite(STDERR, "Could not start the System Administrator demo session.\n");
    exit(1);
}
ob_start();
require $root . '/app/' . $page;
$html = (string) ob_get_clean();
if ($html === '' || !str_contains($html, '<!doctype html>')) {
    fwrite(STDERR, "{$page} did not render a complete HTML page.\n");
    exit(1);
}
foreach ($required as $needle) {
    if (!str_contains($html, $needle)) {
        fwrite(STDERR, "{$page} is missing required output: {$needle}\n");
        exit(1);
    }
}
if (preg_match('/(?:Fatal error|Uncaught (?:Error|Exception)|Warning:|Notice:)/i', $html)) {
    fwrite(STDERR, "{$page} rendered a PHP error.\n");
    exit(1);
}
fwrite(STDOUT, "Rendered {$page} successfully.\n");
