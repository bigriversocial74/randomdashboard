<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/execution_management.php';
require_permission('reports.view');

$executionId=query_int('id');$planId=query_int('plan_id');$actionId=query_int('action_id');
$selected=$executionId?execution_find_record($executionId):null;
if($executionId&&!$selected){flash('error','The execution record is outside the active company scope.');redirect_to(app_url('executions.php'));}
$plan=$selected?mitigation_find_record((int)$selected['plan_id']):($planId?mitigation_find_record($planId):execution_default_plan());
if(!$plan)$plan=execution_default_plan();
$planId=(int)($plan['id']??0);
$action=$selected?mitigation_find_action((int)$selected['action_id']):($actionId?mitigation_find_action($actionId):execution_default_action($plan));
if($action&&(int)($action['plan_id']??0)!==$planId)$action=execution_default_action($plan);
$error=null;$blueprint=null;
try{if($plan&&$action)$blueprint=execution_blueprint($plan,$action);}catch(Throwable $exception){$error=$exception->getMessage();}
$records=execution_records($planId?:null);
if(!$selected&&$records)$selected=$records[0];
$verification=$selected?execution_latest_verification((int)$selected['id']):null;
$events=$selected?execution_events((int)$selected['id']):[];
$metrics=$selected?execution_metrics($selected,$verification):($blueprint?execution_metrics(['before'=>$blueprint['before'],'target'=>$blueprint['target'],'actual'=>[]]):[]);
if(query_string('export')==='csv'&&$selected)execution_export_csv($selected,$verification,$events);

$agentPrompt='Review controlled procurement execution '.($selected['execution_number']??'blueprint').' for '.($plan['plan_number']??'the selected mitigation plan').'. Compare planned and actual recovery, implementation cost, risk reduction, lead-time improvement, inventory exposure, redirected purchase-order value, approval evidence, blockers, verification quality, and rollback readiness.';
$headerActions='<a class="button ghost" href="'.h(app_url('mitigations.php'.($plan?'?id='.(int)$plan['id']:''))).'">Mitigation Plan</a>';
$performanceSupplierId=(int)($action['supplier_id']??0)?:((int)($plan['supplier_id']??0));
if($selected&&$performanceSupplierId>0)$headerActions.='<a class="button ghost" href="'.h(app_url('performance.php?execution_id='.(int)$selected['id'].'&supplier_id='.$performanceSupplierId)).'">Monitor Supplier</a>';
if($selected&&can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('executions.php?id='.(int)$selected['id'].'&export=csv')).'">Export Execution</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Execution & Recovery Verification','executions','Procurement change control','Execute approved mitigation actions, preserve change evidence, verify actual recovery, and retain rollback control.',$headerActions);
require dirname(__DIR__) . '/includes/app/execution_view_styles.php';
require dirname(__DIR__) . '/includes/app/execution_view.php';
render_app_end();