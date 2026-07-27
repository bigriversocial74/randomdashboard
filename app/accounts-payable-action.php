<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/accounts_payable.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('accounts-payable.php'));
verify_csrf();

function accounts_payable_required(string$key,string$label):string{$v=trim(post_string($key));if($v==='')throw new RuntimeException($label.' is required.');return mb_substr($v,0,5000);}
function accounts_payable_date(string$key):string{$v=post_string($key);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);if(!$d||$d->format('Y-m-d')!==$v)throw new RuntimeException('Enter a valid date.');return$v;}
function accounts_payable_redirect(string$tab,array$params=[]):never{redirect_to(app_url('accounts-payable.php?'.http_build_query(array_replace(['tab'=>$tab],$params))));}

try{
    $action=post_string('action');
    if($action==='schedule_invoice'){
        require_permission('accounts_payable.create');$invoice=fulfillment_find_invoice(post_int('invoice_id'));if(!$invoice)throw new RuntimeException('The invoice is outside the active company scope.');
        accounts_payable_schedule_invoice($invoice,accounts_payable_date('scheduled_date'),post_string('payment_method','ach'),post_int('owner_id'),post_int('reviewer_id'),accounts_payable_required('evidence_note','Scheduling evidence'));
        flash('success','Approved invoice scheduled for controlled payment.');accounts_payable_redirect('schedules');
    }
    if($action==='create_batch'){
        require_permission('accounts_payable.create');$companyId=post_int('company_id');if(!data_company_within_scope($companyId))throw new RuntimeException('The payment batch company is outside your permitted scope.');
        $batch=accounts_payable_create_batch($companyId,post_string('payment_method','ach'),accounts_payable_date('execution_date'),accounts_payable_required('bank_account_reference','Bank or adapter reference'),post_int('prepared_by'),post_int('reviewed_by'),post_int('approved_by'),accounts_payable_required('evidence_note','Batch evidence'));
        flash('success','Payment batch prepared with independent review assignments.');accounts_payable_redirect('batches',['batch'=>$batch['id']]);
    }
    if($action==='add_batch_item'){
        require_permission('accounts_payable.edit');$batch=accounts_payable_find_batch(post_int('batch_id'));if(!$batch)throw new RuntimeException('The payment batch is outside the active scope.');$invoice=fulfillment_find_invoice(post_int('invoice_id'));if(!$invoice)throw new RuntimeException('The invoice is outside the active scope.');$creditId=post_int('credit_id');$credit=$creditId?accounts_payable_find_credit($creditId):null;
        accounts_payable_add_invoice_to_batch($batch,$invoice,$credit,accounts_payable_required('evidence_note','Batch-item evidence'),max(0,(float)post_string('payment_amount','0'))?:null);flash('success','Invoice added to the payment batch.');accounts_payable_redirect('batches',['batch'=>$batch['id']]);
    }
    if($action==='transition_batch'){
        $target=post_string('target_status');$permission=match($target){'reviewed'=>'accounts_payable.review','approved'=>'accounts_payable.approve','released'=>'accounts_payable.execute',default=>'accounts_payable.approve'};require_permission($permission);
        $batch=accounts_payable_find_batch(post_int('batch_id'));if(!$batch)throw new RuntimeException('The payment batch is outside the active scope.');$saved=accounts_payable_transition_batch($batch,$target,accounts_payable_required('evidence_note','Transition evidence'));flash('success','Payment batch moved to '.status_label($saved['status']).'.');accounts_payable_redirect('batches',['batch'=>$saved['id']]);
    }
    if($action==='record_execution'){
        require_permission('accounts_payable.execute');$batch=accounts_payable_find_batch(post_int('batch_id'));if(!$batch)throw new RuntimeException('The payment batch is outside the active scope.');
        $execution=accounts_payable_record_execution($batch,post_string('execution_status'),accounts_payable_required('provider_reference','Provider reference'),trim(post_string('settlement_reference')),max(0,(float)post_string('fee_amount','0')),accounts_payable_required('evidence_note','Execution evidence'));
        flash('success','External payment evidence recorded: '.status_label($execution['execution_status']).'.');accounts_payable_redirect('batches',['batch'=>$batch['id']]);
    }
    if($action==='reconcile'){
        require_permission('accounts_payable.reconcile');$batch=accounts_payable_find_batch(post_int('batch_id'));if(!$batch)throw new RuntimeException('The payment batch is outside the active scope.');$execution=accounts_payable_find(accounts_payable_executions((int)$batch['id']),post_int('execution_id'));if(!$execution)throw new RuntimeException('The payment execution is unavailable.');
        $saved=accounts_payable_reconcile($batch,$execution,(float)post_string('settled_amount','0'),max(0,(float)post_string('fee_amount','0')),accounts_payable_required('bank_reference','Bank settlement reference'),post_int('reviewer_id'),accounts_payable_required('evidence_note','Reconciliation evidence'));
        flash('success','Payment reconciliation completed: '.status_label($saved['status']).'.');accounts_payable_redirect('batches',['batch'=>$batch['id']]);
    }
    if($action==='save_credit'){
        require_permission('accounts_payable.create');$companyId=post_int('company_id');if(!data_company_within_scope($companyId))throw new RuntimeException('The supplier credit company is outside your permitted scope.');$supplierId=post_int('supplier_id');$supplier=data_find('suppliers',$supplierId);if(!$supplier||!data_record_visible($supplier)||!accounts_payable_supplier_company_valid($supplierId,$companyId))throw new RuntimeException('The supplier is not authorized for the selected company.');$owner=(int)current_user()['id'];$reviewer=post_int('reviewer_id');if($owner===$reviewer)throw new RuntimeException('Supplier credit owner and reviewer must be different users.');$amount=max(0,(float)post_string('original_amount','0'));if($amount<=0)throw new RuntimeException('Supplier credit amount must be greater than zero.');
        $credit=accounts_payable_save_credit(['id'=>null,'credit_number'=>accounts_payable_number('CR',accounts_payable_credits(),'credit_number'),'company_id'=>$companyId,'supplier_id'=>$supplierId,'invoice_id'=>null,'credit_type'=>post_string('credit_type','credit_memo'),'credit_memo_number'=>accounts_payable_required('credit_memo_number','Credit memo number'),'credit_date'=>accounts_payable_date('credit_date'),'expiration_date'=>accounts_payable_date('expiration_date'),'currency_code'=>'USD','original_amount'=>$amount,'applied_amount'=>0,'remaining_amount'=>$amount,'status'=>'draft','owner_id'=>$owner,'reviewer_id'=>$reviewer,'evidence_note'=>accounts_payable_required('evidence_note','Credit evidence')]);
        accounts_payable_add_event($companyId,'supplier_credit',(int)$credit['id'],'supplier_credit_created',null,'draft','medium',$amount,$credit['evidence_note']);flash('success','Supplier credit recorded for independent finance validation.');accounts_payable_redirect('credits');
    }
    if($action==='validate_credit'){
        require_permission('accounts_payable.review');$credit=accounts_payable_find_credit(post_int('credit_id'));if(!$credit)throw new RuntimeException('The supplier credit is outside the active scope.');$saved=accounts_payable_validate_credit($credit,accounts_payable_required('evidence_note','Credit validation evidence'));
        flash('success','Supplier credit finance validated: '.$saved['credit_number'].'.');accounts_payable_redirect('credits');
    }
    if($action==='save_instruction'){
        require_permission('accounts_payable.approve');$companyId=post_int('company_id');if(!data_company_within_scope($companyId))throw new RuntimeException('The payment instruction company is outside your permitted scope.');$supplierId=post_int('supplier_id');if(!accounts_payable_supplier_company_valid($supplierId,$companyId))throw new RuntimeException('The supplier is not authorized for the selected company.');$vault=accounts_payable_required('vault_reference','External vault reference');$requested=post_int('requested_by');$verified=(int)current_user()['id'];
        accounts_payable_save_verified_instruction(['id'=>null,'instruction_number'=>accounts_payable_number('PAYINST',accounts_payable_instructions(),'instruction_number'),'company_id'=>$companyId,'supplier_id'=>$supplierId,'vault_reference'=>$vault,'instruction_fingerprint'=>hash('sha256',$supplierId.'|'.$vault.'|'.post_string('payment_method')),'payment_method'=>post_string('payment_method','ach'),'status'=>'verified','requested_by'=>$requested,'verified_by'=>$verified,'requested_at'=>date('Y-m-d H:i:s'),'verified_at'=>date('Y-m-d H:i:s'),'effective_date'=>accounts_payable_date('effective_date'),'cooling_until'=>accounts_payable_date('cooling_until'),'callback_evidence'=>accounts_payable_required('callback_evidence','Callback evidence'),'change_reason'=>accounts_payable_required('change_reason','Change reason')]);
        flash('success','Payment-instruction reference independently verified.');accounts_payable_redirect('credits');
    }
    if($action==='create_period'){
        require_permission('accounts_payable.create');$companyId=post_int('company_id');if(!data_company_within_scope($companyId))throw new RuntimeException('The accounting period company is outside your permitted scope.');$start=accounts_payable_date('period_start');$end=accounts_payable_date('period_end');if($end<$start)throw new RuntimeException('The accounting period end cannot precede its start.');$owner=post_int('owner_id');$reviewer=post_int('reviewer_id');if($owner===$reviewer)throw new RuntimeException('Accounting period owner and reviewer must be different users.');
        $period=accounts_payable_save_period(['id'=>null,'period_number'=>accounts_payable_number('AP',accounts_payable_periods(),'period_number'),'company_id'=>$companyId,'fiscal_year'=>(int)date('Y',strtotime($end)),'period_label'=>accounts_payable_required('period_label','Period label'),'period_start'=>$start,'period_end'=>$end,'status'=>'open','soft_closed_at'=>null,'hard_closed_at'=>null,'locked_at'=>null,'owner_id'=>$owner,'reviewer_id'=>$reviewer,'evidence_note'=>accounts_payable_required('evidence_note','Period evidence')]);
        accounts_payable_add_event($companyId,'accounting_period',(int)$period['id'],'period_created',null,'open','medium',0,$period['evidence_note']);flash('success','Accounts-payable accounting period created.');accounts_payable_redirect('close',['period'=>$period['id']]);
    }
    if($action==='create_accrual'){
        require_permission('accounts_payable.create');$period=accounts_payable_find_period(post_int('period_id'));if(!$period)throw new RuntimeException('The accounting period is outside the active scope.');$poId=post_int('purchase_order_id');$grni=null;foreach(accounts_payable_grni_rows()as$row)if((int)$row['purchase_order_id']===$poId){$grni=$row;break;}if(!$grni)throw new RuntimeException('The selected purchase order has no current GRNI exposure.');
        $accrual=accounts_payable_create_accrual($period,$grni,post_int('owner_id'),post_int('reviewer_id'),accounts_payable_required('evidence_note','Accrual evidence'));flash('success','GRNI accrual created: '.$accrual['accrual_number'].'.');accounts_payable_redirect('close',['period'=>$period['id']]);
    }
    if($action==='approve_accrual'){
        require_permission('accounts_payable.review');$accrual=accounts_payable_find(accounts_payable_accruals(),post_int('accrual_id'));if(!$accrual)throw new RuntimeException('The accrual is outside the active scope.');$saved=accounts_payable_approve_accrual($accrual,accounts_payable_required('evidence_note','Accrual approval evidence'));flash('success','Accrual approved: '.$saved['accrual_number'].'.');accounts_payable_redirect('close',['period'=>$saved['accounting_period_id']]);
    }
    if($action==='close_period'){
        require_permission('accounts_payable.close');$period=accounts_payable_find_period(post_int('period_id'));if(!$period)throw new RuntimeException('The accounting period is outside the active scope.');$saved=accounts_payable_close_period($period,post_string('target_status'),post_int('reviewer_id'),post_int('approver_id'),accounts_payable_required('evidence_note','Close certification evidence'));
        flash('success','Accounting period moved to '.status_label($saved['status']).'.');accounts_payable_redirect('close',['period'=>$saved['id']]);
    }
    throw new RuntimeException('Unknown accounts-payable governance action.');
}catch(Throwable$exception){flash('error','The accounts-payable action could not be completed: '.$exception->getMessage());accounts_payable_redirect('overview');}
