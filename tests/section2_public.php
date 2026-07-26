<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
};
$read = static fn(string $path): string => (string) file_get_contents($root . '/' . $path);
$header = $read('includes/public/header.php');
$siteJs = $read('assets/js/site.js');
$siteCss = $read('assets/css/site.css');
foreach (['index.php','resume.php'] as $page) {
    $content = $read($page);
    $assert(str_contains($content, 'render_public_header'), "{$page} does not use the shared public header.");
    $assert(str_contains($content, 'id="mainContent"'), "{$page} is missing the main-content target.");
}
$account = $read('includes/public/account-menu.php');
$assert(str_contains($account, "return public_account_is_logged_in() ? app_url('dashboard.php') : root_url('demo.php');"), 'Logged-out Dashboard does not target demo.php.');
$assert(str_contains($header, 'render_public_account_dropdown'), 'Shared account menu is not rendered by the public header.');
$assert(str_contains($header, 'target="_blank"') && str_contains($header, 'EV Storage'), 'EV Storage does not open in a new tab.');
$assert(str_contains($siteJs, 'const focusable ='), 'Mobile navigation focus management is missing.');
$assert(str_contains($siteJs, "event.key === 'ArrowDown'"), 'Account-menu keyboard navigation is missing.');
$assert(str_contains($siteCss, '.skip-link'), 'Public skip-link styling is missing.');
$assert(str_contains($siteCss, ':focus-visible'), 'Public visible-focus styling is missing.');
fwrite(STDOUT, "Section 2 public-experience quality gates passed.\n");
