<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/reporting.php';
require_app_user();
require_permission('reports.export');

$key = query_string('report', 'executive-summary');
$definitions = gruber_report_definitions();
if (!isset($definitions[$key])) {
    http_response_code(404);
    exit('Unknown report.');
}
$report = gruber_report_dataset($key);
$filename = 'gruber-' . preg_replace('/[^a-z0-9-]+/i', '-', $key) . '-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, private');
$output = fopen('php://output', 'wb');
if ($output === false) exit;
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [$report['title']]);
fputcsv($output, ['Scope', $report['scope']]);
fputcsv($output, ['Generated', date_us($report['generated_at'], true)]);
fputcsv($output, []);
fputcsv($output, $report['columns']);
foreach ($report['rows'] as $row) fputcsv($output, array_map(static fn(mixed $value): string => (string)$value, $row));
fclose($output);
