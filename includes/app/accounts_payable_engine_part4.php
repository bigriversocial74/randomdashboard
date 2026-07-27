<?php
declare(strict_types=1);
function accounts_payable_close_period(array$period,string$target,int$reviewerId,int$approverId,string$note):array
{
    $actor=(int)current_user()['id'];$from=(string)$period['status'];$assignedReviewer=(int)$period['reviewer_id'];
    if($reviewerId!==$assignedReviewer)throw new RuntimeException('The accounting period reviewer cannot be changed during close.');
    if((int)$period['owner_id']===$assignedReviewer||$assignedReviewer===$approverId||(int)$period['owner_id']===$approverId)throw new RuntimeException('Period owner, reviewer, and approver must be different users.');
    if($target==='soft_closed'){if($from!=='open')throw new RuntimeException('Only open periods can be soft closed.');if($actor!==$assignedReviewer)throw new RuntimeException('The assigned reviewer must soft close the period.');$period['soft_closed_at']=date('Y-m-d H:i:s');}
    elseif($target==='hard_closed'){if($from!=='soft_closed')throw new RuntimeException('Only soft-closed periods can be hard closed.');if($actor!==$approverId)throw new RuntimeException('The assigned controller must hard close the period.');$openAccruals=count(array_filter(accounts_payable_accruals((int)$period['id']),static fn(array$a):bool=>!in_array((string)$a['status'],['approved','reversed'],true)));if($openAccruals>0)throw new RuntimeException('All accruals must be approved or reversed before hard close.');$readiness=accounts_payable_portfolio_metrics((int)$period['company_id']);if((int)$readiness['unsettled_batch_count']>0||(int)$readiness['unreconciled_count']>0)throw new RuntimeException('Released payments must be settled and reconciled before hard close.');$period['hard_closed_at']=date('Y-m-d H:i:s');$period['locked_at']=$period['hard_closed_at'];}
    else throw new RuntimeException('Unknown accounting-period close state.');
    $period['status']=$target;$saved=accounts_payable_save_period($period);$metrics=accounts_payable_portfolio_metrics((int)$period['company_id']);
    accounts_payable_save_certification(['id'=>null,'certification_number'=>accounts_payable_number('CERT',accounts_payable_certifications(),'certification_number'),'accounting_period_id'=>(int)$period['id'],'company_id'=>(int)$period['company_id'],'certification_type'=>$target,'status'=>'certified','open_invoice_count'=>$metrics['approved_unscheduled_count'],'unsettled_batch_count'=>$metrics['unsettled_batch_count'],'unreconciled_count'=>$metrics['unreconciled_count'],'unapplied_credit_amount'=>$metrics['unapplied_credit_amount'],'grni_amount'=>$metrics['grni_amount'],'prepared_by'=>(int)$period['owner_id'],'reviewed_by'=>$reviewerId,'approved_by'=>$approverId,'evidence_note'=>$note,'certified_at'=>date('Y-m-d H:i:s')]);
    accounts_payable_add_event((int)$period['company_id'],'accounting_period',(int)$period['id'],'period_'.$target,$from,$target,'high',0,$note);return$saved;
}
function accounts_payable_cash_forecast(?int$companyId=null):array
{
    $buckets=['due_7'=>0.0,'due_30'=>0.0,'due_60'=>0.0,'due_90'=>0.0,'approved_unscheduled'=>0.0,'scheduled_unreleased'=>0.0,'released_unsettled'=>0.0,'expected_open_commitments'=>0.0,'available_discount_amount'=>0.0];
    $scheduledIds=array_map('intval',array_column(array_filter(accounts_payable_schedules(),static fn(array$s):bool=>!in_array((string)$s['status'],['canceled','paid'],true)),'invoice_id'));
    foreach(fulfillment_invoices()as$i){if($companyId!==null&&(int)$i['company_id']!==$companyId)continue;if(!in_array(fulfillment_invoice_effective_status($i),['approved_for_payment','partially_paid'],true))continue;$days=(int)floor((strtotime((string)$i['due_date'])-time())/86400);$amount=accounts_payable_invoice_outstanding($i);if($days<=7)$buckets['due_7']+=$amount;if($days<=30)$buckets['due_30']+=$amount;if($days<=60)$buckets['due_60']+=$amount;if($days<=90)$buckets['due_90']+=$amount;if(!in_array((int)$i['id'],$scheduledIds,true))$buckets['approved_unscheduled']+=accounts_payable_invoice_outstanding($i);if(preg_match('/(\d+(?:\.\d+)?)\/(\d+)/',(string)$i['payment_terms'],$m)&&date('Y-m-d')<=date('Y-m-d',strtotime((string)$i['invoice_date'].' +'.(int)$m[2].' days')))$buckets['available_discount_amount']+=round($amount*((float)$m[1]/100),2);}
    foreach(accounts_payable_batches()as$b){if($companyId!==null&&(int)$b['company_id']!==$companyId)continue;if(in_array((string)$b['status'],['draft','reviewed','approved'],true))$buckets['scheduled_unreleased']+=(float)$b['net_amount'];if(in_array((string)$b['status'],['released','transmitted','accepted'],true))$buckets['released_unsettled']+=(float)$b['net_amount'];}
    foreach(fulfillment_orders()as$po)if(($companyId===null||(int)$po['company_id']===$companyId)&&in_array((string)$po['status'],['open','past_due','partially_received'],true)){$ordered=array_sum(array_column(fulfillment_po_lines((int)$po['id']),'line_total'));$received=0.0;foreach(fulfillment_receipt_lines_for_po((int)$po['id'])as$line){$poLine=fulfillment_find_po_line((int)$line['purchase_order_line_id']);$received+=(float)$line['quantity_accepted']*(float)($poLine['unit_cost']??0);}$buckets['expected_open_commitments']+=max(0,$ordered-$received);}
    return array_map(static fn($v)=>round((float)$v,2),$buckets);
}
function accounts_payable_portfolio_metrics(?int$companyId=null):array
{
    $forecast=accounts_payable_cash_forecast($companyId);$credits=array_values(array_filter(accounts_payable_credits(),static fn(array$c):bool=>$companyId===null||(int)$c['company_id']===$companyId));$grni=array_values(array_filter(accounts_payable_grni_rows(),static fn(array$g):bool=>$companyId===null||(int)$g['company_id']===$companyId));$batches=array_values(array_filter(accounts_payable_batches(),static fn(array$b):bool=>$companyId===null||(int)$b['company_id']===$companyId));
    return array_merge($forecast,[
        'approved_unscheduled_count'=>count(array_filter(fulfillment_invoices(),static fn(array$i):bool=>($companyId===null||(int)$i['company_id']===$companyId)&&in_array(fulfillment_invoice_effective_status($i),['approved_for_payment','partially_paid'],true)&&!array_filter(accounts_payable_schedules(),static fn(array$s):bool=>(int)$s['invoice_id']===(int)$i['id']&&!in_array((string)$s['status'],['canceled','paid'],true)))),
        'unsettled_batch_count'=>count(array_filter($batches,static fn(array$b):bool=>in_array((string)$b['status'],['released','transmitted','accepted'],true))),
        'unreconciled_count'=>count(array_filter($batches,static fn(array$b):bool=>(string)$b['status']==='settled')),
        'unapplied_credit_amount'=>round(array_sum(array_map(static fn(array$c):float=>in_array((string)$c['status'],['validated','partial'],true)?(float)$c['remaining_amount']:0,$credits)),2),
        'grni_amount'=>round(array_sum(array_column($grni,'grni_amount')),2),
        'settled_amount'=>round(array_sum(array_map(static fn(array$b):float=>in_array((string)$b['status'],['settled','reconciled'],true)?(float)$b['net_amount']:0,$batches)),2),
    ]);
}
