<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/mitigation_planning.php';
require_once dirname(__DIR__) . '/includes/app/mitigation_status.php';
require_permission('reports.view');

$planId=query_int('id');
$scenarioId=query_int('scenario_id');
$savedPlan=$planId?mitigation_find_record($planId):null;
if($planId&&!$savedPlan){flash('error','The mitigation plan is outside the active company scope.');redirect_to(app_url('mitigations.php'));}
if($savedPlan)$scenarioId=(int)($savedPlan['scenario_id']??0);
$scenario=$scenarioId?scenario_find_record($scenarioId):null;
if(!$scenario){$scenarios=scenario_records();$scenario=$scenarios[0]??null;}
$blueprint=mitigation_default_blueprint($scenario);
$plan=$savedPlan??[
    'id'=>null,'plan_number'=>'Unsaved plan','company_id'=>current_company_id()==='enterprise'?null:(int)current_company_id(),
    'scenario_id'=>(int)($scenario['id']??0)?:null,'supplier_id'=>(int)($blueprint['simulation']['inputs']['supplier_id']??0)?:null,
    'category_id'=>(int)($blueprint['simulation']['inputs']['category_id']??0)?:null,'title'=>$blueprint['title'],
    'trigger_type'=>$blueprint['trigger_type'],'trigger_operator'=>$blueprint['trigger_operator'],'trigger_value'=>$blueprint['trigger_value'],
    'source_risk_score'=>$blueprint['simulation']['risk_score'],'source_net_impact'=>$blueprint['simulation']['net_impact'],
    'risk_level'=>$blueprint['simulation']['risk_level'],'status'=>'draft','owner_id'=>(int)current_user()['id'],'approval_id'=>null,
    'summary'=>$blueprint['summary'],'activation_notes'=>$blueprint['activation_notes'],'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),
];
$actions=$savedPlan?mitigation_actions((int)$savedPlan['id']):$blueprint['actions'];
$simulation=$blueprint['simulation'];
$metrics=mitigation_calculate_metrics($plan,$actions,$simulation);
if(query_string('export')==='csv'&&$savedPlan)mitigation_export_csv(mitigation_export_record($plan),$actions,$metrics);

$supplier=$simulation['baseline']['supplier'];
$category=data_find('categories',(int)$simulation['baseline']['category_id']);
$agentPrompt='Review mitigation plan '.($plan['plan_number']??'draft').' for '.($supplier['name']??($category['name']??'the active procurement scenario')).'. Evaluate trigger quality, action ownership, alternate supplier readiness, recovery value, residual risk, blockers, due dates, and approval evidence. Recommend the next governed actions.';
$headerActions='<a class="button ghost" href="'.h(app_url('scenarios.php'.($scenario?'?id='.(int)$scenario['id']:''))).'">Risk Simulation</a>';
if($savedPlan)$headerActions.='<a class="button secondary" href="'.h(app_url('executions.php?plan_id='.(int)$savedPlan['id'])).'">Execution Control</a>';
if($savedPlan&&can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('mitigations.php?id='.(int)$savedPlan['id'].'&export=csv')).'">Export Plan</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Mitigation Action Plans','mitigations','Supplier contingency management','Turn procurement-risk scenarios into owned, measurable, approval-controlled contingency plans.',$headerActions);
require dirname(__DIR__) . '/includes/app/mitigation_view_styles.php';
require dirname(__DIR__) . '/includes/app/mitigation_view.php';
render_app_end();
