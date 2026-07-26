<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/scenario_planning.php';
require_permission('reports.view');

$recordId = query_int('id');
$savedRecord = $recordId ? scenario_find_record($recordId) : null;
if ($savedRecord && empty($savedRecord['decision_note'])) {
    $savedRecord['decision_note'] = (string)($savedRecord['result_summary']['decision_note'] ?? '');
}
$raw = $savedRecord['inputs'] ?? scenario_default_inputs();
foreach (array_keys(scenario_default_inputs()) as $key) {
    if (isset($_GET[$key]) && is_string($_GET[$key])) $raw[$key] = $_GET[$key];
}
$error = null;
try {
    $simulation = scenario_calculate($raw);
} catch (Throwable $exception) {
    $error = $exception->getMessage();
    $simulation = scenario_calculate(scenario_default_inputs());
}
if (query_string('export') === 'csv') scenario_export_csv($simulation);

$supplier = $simulation['baseline']['supplier'];
$category = data_find('categories',(int)$simulation['baseline']['category_id']);
$active = $simulation['active_assumptions'];
$agentPrompt = 'Simulate procurement risk for ' . ($supplier['name'] ?? ($category['name'] ?? 'the selected category'))
    . ' using a ' . $active['price_change_pct'] . '% active price change, ' . $active['demand_change_pct']
    . '% active demand change, ' . $active['lead_time_delay_days'] . ' active delay days, and '
    . $active['disruption_pct'] . '% active disruption. Explain mitigation priorities and evidence gaps.';
$actions = '<a class="button ghost" href="'.h(app_url('sourcing.php')).'">Supplier Comparison</a>';
if (can('reports.export')) {
    $actions .= '<a class="button secondary" href="'.h(app_url('scenarios.php?'.http_build_query([...$simulation['inputs'],'export'=>'csv']))).'">Export Scenario</a>';
}
$actions .= '<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Scenario Planning','reports','Procurement risk simulation','Model price, demand, lead-time, disruption, inventory-transfer, and savings outcomes before a sourcing or purchasing decision.',$actions);
require dirname(__DIR__) . '/includes/app/scenario_view_styles.php';
require dirname(__DIR__) . '/includes/app/scenario_view_controls.php';
require dirname(__DIR__) . '/includes/app/scenario_view_results.php';
require dirname(__DIR__) . '/includes/app/scenario_view_records.php';
render_app_end();
