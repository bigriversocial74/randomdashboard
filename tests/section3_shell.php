<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
};
$layout = (string) file_get_contents($root . '/includes/app/layout.php');
$js = (string) file_get_contents($root . '/app/assets/js/app.js');
$css = (string) file_get_contents($root . '/app/assets/css/app.css');
$assert(str_contains($layout, 'app-skip-link'), 'Application skip navigation is missing.');
$assert(str_contains($layout, 'id="appMainContent"'), 'Application main landmark target is missing.');
$assert(str_contains($layout, 'aria-current="page"'), 'Active-page semantics are missing.');
$assert(str_contains($layout, 'aria-controls="appSidebar"'), 'Sidebar toggle relationship is missing.');
$assert(str_contains($js, 'trapTabKey'), 'Modal/sidebar focus containment is missing.');
$assert(str_contains($js, 'sidebarOpener'), 'Sidebar focus restoration is missing.');
$assert(str_contains($js, 'modalOpeners'), 'Modal focus restoration is missing.');
$assert(str_contains($css, '.app-skip-link'), 'Application skip-link styling is missing.');
$assert(str_contains($css, 'prefers-reduced-motion:reduce'), 'Reduced-motion handling is missing.');
fwrite(STDOUT, "Section 3 application-shell quality gates passed.\n");
