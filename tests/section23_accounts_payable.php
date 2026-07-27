<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/accounts-payable.php';$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_USER_AGENT']='Section 23 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/accounts_payable.php';

function s23(bool$condition,string$message):void{if(!$condition){fwrite(STDERR,"Section 23 failure: {$message}\n");exit(1);}}
function s23_user(int$id):void{$_SESSION['gruber_demo_user_id']=$id;}

s23(accounts_payable_tables_ready(),'Demo mode must report AP tables ready.');
s23(count(accounts_payable_tables())===12,'Section 23 must govern twelve supplemental AP tables.');
$invoice=fulfillment_find_invoice(2);s23($invoice!==null,'Seeded approved invoice is required.');
s23(fulfillment_invoice_effective_status($invoice)==='approved_for_payment','Seeded invoice must be payment ready.');

$draftCredit=accounts_payable_save_credit(['id'=>null,'credit_number'=>'CR-TEST-23','company_id'=>2,'supplier_id'=>1,'invoice_id'=>null,'credit_type'=>'credit_memo','credit_memo_number'=>'CM-TEST-23','credit_date'=>date('Y-m-d'),'expiration_date'=>date('Y-m-d',strtotime('+1 year')),'currency_code'=>'USD','original_amount'=>250.00,'applied_amount'=>0.00,'remaining_amount'=>250.00,'status'=>'draft','owner_id'=>1,'reviewer_id'=>3,'evidence_note'=>'Draft supplier credit awaiting independent finance validation.']);
s23_user(3);$draftCredit=accounts_payable_validate_credit($draftCredit,'Independent finance reviewer validated the supplier credit evidence.');s23($draftCredit['status']==='validated','Supplier credits require independent finance validation.');s23((int)$draftCredit['owner_id']!==(int)$draftCredit['reviewer_id'],'Supplier-credit owner and reviewer must remain separate.');s23_user(1);

$schedule=accounts_payable_schedule_invoice($invoice,date('Y-m-d',strtotime('+2 days')),'ach',1,3,'Approved invoice scheduled from three-way match evidence.');
s23((int)$schedule['invoice_id']===2&&$schedule['status']==='scheduled','Approved invoice must be scheduled.');
try{accounts_payable_schedule_invoice($invoice,date('Y-m-d',strtotime('+3 days')),'ach',1,3,'Duplicate schedule attempt.');s23(false,'Duplicate active schedules must be blocked.');}catch(RuntimeException){}

$batch=accounts_payable_create_batch(2,'ach',date('Y-m-d',strtotime('+2 days')),'PAYMENT-VAULT-DEMO',1,3,6,'Batch prepared from approved invoices.');
$item=accounts_payable_add_invoice_to_batch($batch,$invoice,accounts_payable_find_credit(1),'Invoice and supplier credit verified.');
$batch=accounts_payable_find_batch((int)$batch['id']);s23($batch!==null&&(int)$batch['invoice_count']===1,'Batch must contain one invoice.');
s23((float)$batch['net_amount']<(float)$batch['gross_amount'],'Validated supplier credit must reduce the net payment.');
$other=accounts_payable_create_batch(2,'ach',date('Y-m-d',strtotime('+3 days')),'PAYMENT-VAULT-DEMO',1,3,6,'Duplicate control test.');
try{accounts_payable_add_invoice_to_batch($other,$invoice,null,'Duplicate batch attempt.');s23(false,'Invoice must not appear in two active batches.');}catch(RuntimeException){}

s23_user(3);$batch=accounts_payable_transition_batch($batch,'reviewed','Independent reviewer verified invoices, credits, totals, and payment instruction.');
s23_user(6);$batch=accounts_payable_transition_batch($batch,'approved','Independent approver locked the batch hash and totals.');
s23_user(1);$batch=accounts_payable_transition_batch($batch,'released','Authorized release completed after payment-instruction verification and cooling period.');
s23($batch['status']==='released'&&!empty($batch['released_at']),'Approved batch must be released under dual control.');

$transmitted=accounts_payable_record_execution($batch,'transmitted','PROVIDER-23-001','',0,'Manual provider transmission reference recorded; adapter remained human initiated.');
$batch=accounts_payable_find_batch((int)$batch['id']);
$accepted=accounts_payable_record_execution($batch,'accepted','PROVIDER-23-001','',0,'Provider acknowledgment accepted the payment batch.');
$batch=accounts_payable_find_batch((int)$batch['id']);
$settled=accounts_payable_record_execution($batch,'settled','PROVIDER-23-001','SETTLEMENT-23-001',12.50,'Bank settlement evidence confirmed the net payment.');
s23($settled['execution_status']==='settled','Settlement execution must be recorded.');
$paid=fulfillment_find_invoice(2);s23($paid['status']==='paid'&&!empty($paid['paid_at']),'Canonical invoice becomes paid only after settlement evidence.');
s23(count(accounts_payable_remittances(1))===1,'Settlement must create one supplier-visible remittance.');$paidSchedules=array_values(array_filter(accounts_payable_schedules(),static fn(array$s):bool=>(int)$s['invoice_id']===2));s23(($paidSchedules[0]['status']??'')==='paid','Settlement must close the governed payment schedule.');
$repeat=accounts_payable_record_execution(accounts_payable_find_batch((int)$batch['id']),'settled','PROVIDER-23-001','SETTLEMENT-23-001',12.50,'Repeated idempotent settlement.');
s23((int)$repeat['id']===(int)$settled['id'],'Repeated execution evidence must be idempotent.');

s23_user(3);$settledBatch=accounts_payable_find_batch((int)$batch['id']);$reconciliation=accounts_payable_reconcile($settledBatch,$settled,(float)$settledBatch['net_amount'],0,'BANK-23-001',3,'Independent bank reconciliation matched the batch settlement.');
s23($reconciliation['status']==='reconciled','Matching settlement must reconcile.');s23((int)$reconciliation['owner_id']!==(int)$reconciliation['reviewer_id'],'Reconciliation owner and reviewer must be different users.');
s23(accounts_payable_find_batch((int)$batch['id'])['status']==='reconciled','Reconciled batches must lock in the reconciled state.');

s23_user(1);$period=accounts_payable_save_period(['id'=>null,'period_number'=>'AP-TEST-23','company_id'=>2,'fiscal_year'=>(int)date('Y'),'period_label'=>'Section 23 Test','period_start'=>date('Y-m-01'),'period_end'=>date('Y-m-t'),'status'=>'open','soft_closed_at'=>null,'hard_closed_at'=>null,'locked_at'=>null,'owner_id'=>1,'reviewer_id'=>3,'evidence_note'=>'Test period for controlled close.']);
s23_user(3);$period=accounts_payable_close_period($period,'soft_closed',3,6,'Soft close completed after AP cutoff and reconciliation review.');
s23_user(6);$period=accounts_payable_close_period($period,'hard_closed',3,6,'Controller hard close certification completed and period locked.');
s23($period['status']==='hard_closed'&&!empty($period['locked_at']),'Hard close must lock the accounting period.');
s23(count(accounts_payable_certifications((int)$period['id']))===2,'Soft and hard close certifications must be retained.');

s23(count(accounts_payable_events())>=10,'Immutable AP governance history must accumulate events.');
$metrics=accounts_payable_portfolio_metrics();s23(array_key_exists('grni_amount',$metrics)&&array_key_exists('released_unsettled',$metrics),'AP portfolio metrics must include GRNI and unsettled exposure.');

$migration=file_get_contents($root.'/database/20260727_section23_accounts_payable_payment_close_governance.sql');
foreach(accounts_payable_tables()as$table)s23(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),'Migration must create '.$table.'.');
s23(str_contains($migration,"'5.2-section23'"),'Migration version must be recorded.');
s23(str_contains($migration,'accounts_payable.execute'),'Production execution permission must be seeded.');
$engine='';foreach(glob($root.'/includes/app/accounts_payable_engine_part*.php') as $engineFile)$engine.=file_get_contents($engineFile);
s23(str_contains($engine,'The payment approver cannot release the same batch'),'Approval and release separation is required.');
s23(str_contains($engine,"'status']='paid'"),'Invoice paid state must be tied to settlement finalization.');s23(str_contains($engine,'partially_paid'),'Partial-payment governance must preserve remaining invoice balances.');s23(str_contains($engine,'expected_open_commitments'),'Cash forecasting must include expected open commitments.');
$action=file_get_contents($root.'/app/accounts-payable-action.php');
s23(str_contains($action,"require_permission('accounts_payable.execute')"),'Execution actions must require AP execution permission.');s23(str_contains($action,"validate_credit"),'Supplier credits must have a separate validation action.');
$legacyPayment=file_get_contents($root.'/app/fulfillment_action_exceptions.php');s23(str_contains($legacyPayment,'Direct paid-state updates are disabled'),'Legacy mark-paid action must be disabled.');
$paymentView=file_get_contents($root.'/includes/app/fulfillment_view_payment.php');s23(!str_contains($paymentView,'value="mark_paid"'),'Fulfillment UI must not expose a direct mark-paid form.');
$export=file_get_contents($root.'/includes/app/accounts_payable_export.php');
s23(str_contains($export,"preg_match('/^[=+\\-@]/'"),'CSV formula-injection protection is required.');

echo "Section 23 accounts payable, payment execution, reconciliation, accrual, cash forecast, and close governance tests passed.\n";
