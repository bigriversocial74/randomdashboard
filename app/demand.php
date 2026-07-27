<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/demand_management.php';
require_permission('purchase_orders.view');

if(!function_exists('data_default_company_id')){
    function data_default_company_id(array $user): int
    {
        $permitted=permitted_company_ids($user);
        if(current_company_id()!=='enterprise')return (int)current_company_id();
        $primary=(int)($user['primary_company_id']??0);
        return in_array($primary,$permitted,true)?$primary:(int)($permitted[0]??0);
    }
}

$requestId=query_int('id');$selected=$requestId?demand_find_request($requestId):demand_default_request();
if($requestId&&!$selected){flash('error','The purchase request is outside the active scope.');redirect_to(app_url('demand.php'));}
$requests=demand_requests();$lines=$selected?demand_request_lines((int)$selected['id']):[];$assessments=$selected?demand_assessments((int)$selected['id']):[];$assessment=$assessments[0]??[];$events=$selected?demand_events((int)$selected['id']):[];$budgets=demand_budgets();$forecasts=demand_forecasts();$metrics=$selected?demand_metrics($selected,$lines,$assessment):[];
if(query_string('export')==='csv'&&$selected)demand_export_csv($selected,$lines,$assessment,$metrics,$events);
$agentPrompt='Review purchase request '.($selected['request_number']??'demand intake').' for budget fit, available inventory, internal transfer opportunity, open purchase orders, duplicate requests, contract coverage, supplier performance, required-date risk, historical pricing, consolidation opportunity, off-contract exposure, forecast demand, approval requirements, and purchase-order conversion readiness.';
$headerActions='<a class="button ghost" href="'.h(app_url('purchase-orders.php')).'">Purchase Orders</a><a class="button ghost" href="'.h(app_url('contracts.php')).'">Contract Governance</a>';
if($selected&&can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('demand.php?id='.(int)$selected['id'].'&export=csv')).'">Export Request</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Demand Intake, Purchase Requisitions & Budget Governance','demand','Pre-commitment demand control','Validate internal demand, inventory, budget, supplier, contract, timing, and consolidation evidence before purchase-order commitment.',$headerActions);
require dirname(__DIR__) . '/includes/app/demand_view_styles.php';
require dirname(__DIR__) . '/includes/app/demand_view.php';
render_app_end();
