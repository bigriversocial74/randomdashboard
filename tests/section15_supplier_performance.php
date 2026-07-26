<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/performance.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 15 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/performance_management.php';

$supplier=data_find('suppliers',4);if(!$supplier){fwrite(STDERR,"Supplier seed is unavailable.\n");exit(1);}
$blueprint=performance_review_blueprint($supplier,null,60);
foreach(['baseline','current','targets','risk_tier','recommendation','spend_exposure','evidence_note'] as $field){if(!array_key_exists($field,$blueprint)){fwrite(STDERR,"Performance blueprint missing {$field}.\n");exit(1);}}
$current=$blueprint['current'];$current['on_time_delivery']=62;$current['fill_rate']=60;$current['service_level']=58;$current['defect_rate_pct']=6;$current['lead_time_variance_days']=10;
$probe=['baseline'=>$blueprint['baseline'],'current'=>$current,'targets'=>$blueprint['targets'],'spend_exposure'=>200000,'savings_retained'=>0,'repeated_failure_count'=>3];$probeMetrics=performance_metrics($probe,[]);
if((int)$probeMetrics['regression_count']<2||!in_array($probeMetrics['risk_tier'],['high','critical'],true)){fwrite(STDERR,"Regression and risk calculation failed.\n");exit(1);}
foreach(['sourcing_score','scenario_risk_adjustment','mitigation_readiness_pct','execution_target_confidence_pct'] as $field){if(!array_key_exists($field,$probeMetrics)){fwrite(STDERR,"Closed-loop feedback missing {$field}.\n");exit(1);}}
$record=performance_save_review(['id'=>null,'company_id'=>4,'supplier_id'=>4,'source_execution_id'=>null,'source_verification_id'=>null,'review_window_days'=>60,'period_start'=>date('Y-m-d',strtotime('-60 days')),'period_end'=>date('Y-m-d'),'owner_id'=>1,'reviewer_id'=>6,'status'=>'draft','recommendation'=>$probeMetrics['recommendation'],'risk_tier'=>$probeMetrics['risk_tier'],'approval_id'=>null,'baseline'=>$blueprint['baseline'],'current'=>$current,'targets'=>$blueprint['targets'],'overall_score'=>$probeMetrics['current_score'],'improvement_pct'=>$probeMetrics['improvement_pct'],'sustainability_pct'=>$probeMetrics['sustainability_pct'],'repeated_failure_count'=>3,'spend_exposure'=>200000,'savings_retained'=>0,'evidence_note'=>'Quality review evidence.','next_review_date'=>date('Y-m-d',strtotime('+30 days'))]);
if((int)$record['id']<=0||!performance_find_review((int)$record['id'])){fwrite(STDERR,"Demo review persistence failed.\n");exit(1);}
if(!performance_requires_approval($record,$probeMetrics)){fwrite(STDERR,"High-risk supplier recommendation approval rule failed.\n");exit(1);}
$event=performance_add_event((int)$record['id'],'metric_regression','on_time_delivery',80,62,95,'critical','Quality regression evidence.');
if((int)$event['id']<=0||count(performance_events((int)$record['id']))<1){fwrite(STDERR,"Performance event persistence failed.\n");exit(1);}
$action=performance_save_action(['id'=>null,'company_id'=>4,'review_id'=>(int)$record['id'],'supplier_id'=>4,'title'=>'Restore delivery performance','root_cause'=>'Capacity planning gap.','corrective_action'=>'Publish capacity-backed shipment schedule.','owner_id'=>1,'status'=>'in_progress','severity'=>'critical','due_date'=>date('Y-m-d',strtotime('+10 days')),'target_metric'=>'on_time_delivery','target_value'=>95,'actual_value'=>62,'evidence_note'=>'Action evidence.','completed_at'=>null,'verified_at'=>null]);
if((int)$action['id']<=0||!performance_find_action((int)$action['id'])){fwrite(STDERR,"Corrective action persistence failed.\n");exit(1);}
$notReady=performance_close_readiness($record,[$action]);if($notReady['ready']){fwrite(STDERR,"Review closure gate allowed open corrective action.\n");exit(1);}
$record['current']=$record['targets'];$record['repeated_failure_count']=0;$goodMetrics=performance_metrics($record,[]);$record['overall_score']=$goodMetrics['current_score'];$record['improvement_pct']=$goodMetrics['improvement_pct'];$record['sustainability_pct']=$goodMetrics['sustainability_pct'];$record['risk_tier']=$goodMetrics['risk_tier'];$record['recommendation']=$goodMetrics['recommendation'];$record=performance_save_review($record);$action['status']='verified';$action['actual_value']=96;$action['completed_at']=date('Y-m-d H:i:s');$action['verified_at']=date('Y-m-d H:i:s');$action=performance_save_action($action);$ready=performance_close_readiness($record,[$action]);if(!$ready['ready']){fwrite(STDERR,"Verified corrective-action closure gate failed.\n");exit(1);}
if(performance_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)"){fwrite(STDERR,"Performance CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/performance.php').file_get_contents($root.'/includes/app/performance_view.php');$handler=file_get_contents($root.'/app/performance-action.php');$sql=file_get_contents($root.'/database/20260726_section15_supplier_performance_improvement.sql');
foreach(['Supplier Performance Monitoring &amp; Continuous Improvement','Baseline, current performance, and approved targets','Supplier corrective action','Closed-loop intelligence','Performance evidence trail','Export Review'] as $needle){if(!str_contains($page,$needle)){fwrite(STDERR,"Performance workspace missing {$needle}.\n");exit(1);}}
foreach(['supplier_performance_review','supplier_corrective_action','workflow_approvals','metric_regression','monitoring_started','review_closed','data_add_audit'] as $needle){if(!str_contains($handler,$needle)){fwrite(STDERR,"Performance workflow missing {$needle}.\n");exit(1);}}
foreach(['CREATE TABLE IF NOT EXISTS supplier_performance_reviews','CREATE TABLE IF NOT EXISTS supplier_corrective_action_plans','CREATE TABLE IF NOT EXISTS supplier_performance_events','4.4-section15'] as $needle){if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 15 SQL missing {$needle}.\n");exit(1);}}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 15 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 15 supplier performance and continuous-improvement gates passed.\n");
