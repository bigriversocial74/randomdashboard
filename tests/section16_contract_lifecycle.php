<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/contracts.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 16 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/contract_management.php';

$contract=contract_default_record();
if(!$contract){fwrite(STDERR,"Contract seed is unavailable.\n");exit(1);}
$blueprint=contract_blueprint($contract);
foreach(['supplier','recommended_decision','alternate_supplier_count','performance_metrics'] as $field){if(!array_key_exists($field,$blueprint)){fwrite(STDERR,"Contract blueprint missing {$field}.\n");exit(1);}}
$metrics=contract_metrics($contract,contract_obligations((int)$contract['id']),contract_amendments((int)$contract['id']),contract_decisions((int)$contract['id']));
foreach(['days_to_expiry','notice_deadline','renewal_readiness_pct','sla_breach_count','obligation_completion_pct','off_contract_spend','performance_score'] as $field){if(!array_key_exists($field,$metrics)){fwrite(STDERR,"Contract metrics missing {$field}.\n");exit(1);}}

$new=contract_save_master(['id'=>null,'supplier_id'=>1,'company_id'=>2,'number'=>'CTR-QUALITY-016','title'=>'Section 16 Quality Contract','start_date'=>date('Y-m-d'),'end_date'=>date('Y-m-d',strtotime('+1 year')),'auto_renew'=>false,'payment_terms'=>'Net 30','freight_terms'=>'FOB destination','warranty_terms'=>'Quality warranty evidence.','service_level_terms'=>'95 percent service target.','annual_value'=>150000,'status'=>'active','document_path'=>'','owner_id'=>3,'reviewer_id'=>6,'renewal_notice_days'=>90,'review_status'=>'draft','risk_tier'=>'medium','performance_review_id'=>null,'approval_id'=>null,'committed_spend'=>150000,'actual_spend'=>25000,'off_contract_spend'=>0,'evidence_note'=>'Quality contract evidence.','next_review_date'=>date('Y-m-d',strtotime('+90 days'))]);
if((int)$new['id']<=0||!contract_find_record((int)$new['id'])){fwrite(STDERR,"Demo contract persistence failed.\n");exit(1);}

$obligation=contract_save_obligation(['id'=>null,'contract_id'=>(int)$new['id'],'company_id'=>2,'title'=>'Quality service obligation','obligation_type'=>'service_level','owner_id'=>3,'due_date'=>date('Y-m-d',strtotime('+30 days')),'status'=>'in_progress','target_value'=>95,'actual_value'=>90,'unit'=>'percent','evidence_note'=>'Quality obligation evidence.','completed_at'=>null]);
if((int)$obligation['id']<=0||!contract_find_obligation((int)$obligation['id'])){fwrite(STDERR,"Contract obligation persistence failed.\n");exit(1);}

$amendment=contract_save_amendment(['id'=>null,'contract_id'=>(int)$new['id'],'company_id'=>2,'title'=>'Quality amendment','status'=>'draft','effective_date'=>date('Y-m-d',strtotime('+45 days')),'value_change'=>-10000,'term_change_days'=>30,'before_terms'=>'Existing terms.','after_terms'=>'Improved terms.','rationale'=>'Quality amendment rationale.','approval_id'=>null,'created_by'=>1]);
if((int)$amendment['id']<=0||!contract_find_amendment((int)$amendment['id'])){fwrite(STDERR,"Contract amendment persistence failed.\n");exit(1);}

$decision=contract_save_decision(['id'=>null,'contract_id'=>(int)$new['id'],'company_id'=>2,'supplier_id'=>1,'performance_review_id'=>null,'decision'=>'renegotiate','status'=>'draft','owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'current_annual_value'=>150000,'proposed_annual_value'=>140000,'value_change'=>-10000,'effective_date'=>date('Y-m-d',strtotime('+1 year')),'alternative_supplier_id'=>2,'rationale'=>'Quality renewal rationale.','negotiation_objectives'=>'Improve price and SLA terms.','evidence_note'=>'Quality renewal evidence.','implemented_at'=>null]);
if((int)$decision['id']<=0||!contract_find_decision((int)$decision['id'])){fwrite(STDERR,"Renewal decision persistence failed.\n");exit(1);}
if(!contract_decision_requires_approval($decision,$new)){fwrite(STDERR,"Renewal approval rule failed.\n");exit(1);}
$decision['status']='implemented';
if(contract_decision_effective_status($decision)!=='implemented'){fwrite(STDERR,"Terminal renewal status precedence failed.\n");exit(1);}

$event=contract_add_event((int)$new['id'],'decision_created','renewal_decision',(int)$decision['id'],null,'draft','medium','Quality event evidence.');
if((int)$event['id']<=0||count(contract_events((int)$new['id']))<1){fwrite(STDERR,"Contract event persistence failed.\n");exit(1);}
if(contract_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)"){fwrite(STDERR,"Contract CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/contracts.php').file_get_contents($root.'/includes/app/contract_view.php');
$actionFile=file_get_contents($root.'/app/contract-action.php');
$sql=file_get_contents($root.'/database/20260726_section16_contract_lifecycle_renewal_governance.sql');
$supplierPage=file_get_contents($root.'/app/suppliers.php');
$performancePage=file_get_contents($root.'/app/performance.php');
foreach(['Contract Lifecycle, SLA Compliance &amp; Renewal Governance','Renewal decision intelligence','Contract obligations','Contract amendments','Renewal governance','Immutable history'] as $needle){if(!str_contains($page,$needle)){fwrite(STDERR,"Contract workspace missing {$needle}.\n");exit(1);}}
foreach(['save_contract','save_obligation','save_amendment','save_decision','submit_decision','implement_decision','workflow_approvals','contract_add_event','suppliers.approve'] as $needle){if(!str_contains($actionFile,$needle)){fwrite(STDERR,"Contract handler missing {$needle}.\n");exit(1);}}
foreach(['CREATE TABLE IF NOT EXISTS supplier_contract_governance_profiles','CREATE TABLE IF NOT EXISTS supplier_contract_obligations','CREATE TABLE IF NOT EXISTS supplier_contract_amendments','CREATE TABLE IF NOT EXISTS supplier_contract_renewal_decisions','CREATE TABLE IF NOT EXISTS supplier_contract_events','4.5-section16'] as $needle){if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 16 SQL missing {$needle}.\n");exit(1);}}
if(!str_contains($supplierPage,'contracts.php')||!str_contains($performancePage,'contracts.php')){fwrite(STDERR,"Contract workflow handoffs are missing.\n");exit(1);}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 16 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 16 contract lifecycle and renewal governance gates passed.\n");
