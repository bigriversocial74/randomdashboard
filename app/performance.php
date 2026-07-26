<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/performance_management.php';
require_permission('scorecards.view');

$reviewId=query_int('id');$supplierId=query_int('supplier_id');$executionId=query_int('execution_id');
$selected=$reviewId?performance_find_review($reviewId):null;if($reviewId&&!$selected){flash('error','The supplier performance review is outside the active scope.');redirect_to(app_url('performance.php'));}
if($selected)$supplierId=(int)$selected['supplier_id'];
$supplier=$supplierId?data_find('suppliers',$supplierId):null;if(!$supplier){$suppliers=data_visible_collection('suppliers');$supplier=$suppliers[0]??null;}
$execution=$executionId?execution_find_record($executionId):($supplier?performance_source_execution((int)$supplier['id']):null);
$error=null;$blueprint=null;try{if($supplier)$blueprint=performance_review_blueprint($supplier,$execution,query_int('window',30));}catch(Throwable $exception){$error=$exception->getMessage();}
$reviews=performance_reviews($supplier?(int)$supplier['id']:null);if(!$selected&&$reviews)$selected=$reviews[0];if($selected){$supplier=data_find('suppliers',(int)$selected['supplier_id']);$execution=!empty($selected['source_execution_id'])?execution_find_record((int)$selected['source_execution_id']):null;}
$actions=$selected?performance_actions((int)$selected['id']):[];$events=$selected?performance_events((int)$selected['id']):[];$metrics=$selected?performance_metrics($selected,$actions):($blueprint?performance_metrics(['baseline'=>$blueprint['baseline'],'current'=>$blueprint['current'],'targets'=>$blueprint['targets'],'spend_exposure'=>$blueprint['spend_exposure'],'savings_retained'=>$blueprint['savings_retained'],'repeated_failure_count'=>0],[]):[]);
if(query_string('export')==='csv'&&$selected)performance_export_csv($selected,$actions,$events);
$agentPrompt='Review supplier performance '.($selected['review_number']??'blueprint').' for '.($supplier['name']??'the selected supplier').'. Evaluate 30/60/90-day recovery sustainability, scorecard trends, delivery, quality, fill rate, service, lead-time variance, defects, price variance, corrective actions, repeated failures, spend exposure, retained savings, risk tier, supplier recommendation, and feedback to sourcing, risk scenarios, mitigation readiness, and execution targets.';
$headerActions='<a class="button ghost" href="'.h(app_url('scorecards.php')).'">Supplier Scorecards</a>';
if($execution)$headerActions.='<a class="button ghost" href="'.h(app_url('executions.php?id='.(int)$execution['id'])).'">Recovery Evidence</a>';
if($selected&&can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('performance.php?id='.(int)$selected['id'].'&export=csv')).'">Export Review</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Supplier Performance & Improvement','performance','Continuous supplier governance','Monitor post-recovery performance, detect regression, govern corrective actions, and feed trusted evidence back into sourcing and risk decisions.',$headerActions);
require dirname(__DIR__) . '/includes/app/performance_view_styles.php';
require dirname(__DIR__) . '/includes/app/performance_view.php';
render_app_end();
