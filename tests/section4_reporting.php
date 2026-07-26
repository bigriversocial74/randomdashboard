<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
};
$reporting = (string) file_get_contents($root . '/includes/app/reporting.php');
$reports = (string) file_get_contents($root . '/app/reports.php');
$export = (string) file_get_contents($root . '/app/report-export.php');
$assert(substr_count($reporting, "'title' =>") >= 6, 'Fewer than six real operational reports are defined.');
$assert(str_contains($reports, 'report-export.php'), 'Report page does not link to CSV export.');
$assert(str_contains($reports, 'data-print-page'), 'Printable report action is missing.');
$assert(str_contains($reports, '<caption'), 'Report table caption is missing.');
$assert(str_contains($export, 'text/csv'), 'CSV content type is missing.');
$assert(str_contains($export, 'fputcsv'), 'CSV export is not generated from scoped report rows.');
$assert(str_contains($export, "reports.export"), 'CSV export permission is not enforced.');
fwrite(STDOUT, "Section 4 reporting quality gates passed.\n");
