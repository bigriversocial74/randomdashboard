<?php
declare(strict_types=1);

function savings_realization_number(string $prefix,array $rows): string
{
    return $prefix.'-'.date('Y').'-'.str_pad((string)(count($rows)+1),4,'0',STR_PAD_LEFT);
}

function savings_realization_default_opportunity(): ?array
{
    $rows=savings_realization_opportunities();
    return $rows[0]??null;
}

function savings_realization_current_baseline(int $opportunityId): ?array
{
    $rows=savings_realization_baselines($opportunityId);
    foreach ($rows as $row) if ((string)$row['status']==='approved') return $row;
    return $rows[0]??null;
}

function savings_realization_baseline_status(array $baseline): string
{
    $stored=(string)($baseline['status']??'draft');
    if (in_array($stored,['approved','rejected','superseded'],true)) return $stored;
    $approvalId=(int)($baseline['approval_id']??0);
    if ($approvalId>0) {
        $approval=data_find('workflow_approvals',$approvalId);
        if ($approval && in_array((string)$approval['status'],['pending','in_review','approved','changes_requested','rejected'],true)) return (string)$approval['status'];
    }
    return $stored;
}

function savings_realization_period_status(array $period): string
{
    $stored=(string)($period['status']??'draft');
    if (in_array($stored,['validated','rejected','closed'],true)) return $stored;
    $approvalId=(int)($period['approval_id']??0);
    if ($approvalId>0) {
        $approval=data_find('workflow_approvals',$approvalId);
        if ($approval && in_array((string)$approval['status'],['pending','in_review','approved','changes_requested','rejected'],true)) return (string)$approval['status'];
    }
    return $stored;
}

function savings_realization_calculate_period(array $period): array
{
    $gross=(float)($period['actual_hard_savings']??0)
        +(float)($period['actual_cost_avoidance']??0)
        +(float)($period['actual_recoveries']??0)
        +(float)($period['actual_working_capital']??0);
    $net=$gross
        -(float)($period['implementation_cost']??0)
        -(float)($period['operating_cost']??0)
        -(float)($period['leakage_amount']??0)
        +(float)($period['adjustment_amount']??0);
    return ['gross_realized_value'=>round($gross,2),'net_realized_value'=>round($net,2)];
}

function savings_realization_evidence_completeness(array $opportunity,array $period): int
{
    $score=0;
    $baseline=savings_realization_current_baseline((int)$opportunity['id']);
    if ($baseline && savings_realization_baseline_status($baseline)==='approved') $score+=25;
    $evidence=savings_realization_evidence((int)$opportunity['id'],(int)$period['id']);
    $verified=array_values(array_filter($evidence,static fn(array $row):bool=>(string)$row['status']==='verified'));
    if ($verified) $score+=25;
    if (count($verified)>=2) $score+=10;
    $actual=(float)($period['actual_hard_savings']??0)+(float)($period['actual_cost_avoidance']??0)+(float)($period['actual_recoveries']??0)+(float)($period['actual_working_capital']??0);
    if ($actual!==0.0) $score+=20;
    if (trim((string)($period['evidence_note']??''))!=='') $score+=10;
    $leakage=savings_realization_leakage((int)$opportunity['id'],(int)$period['id']);
    if (!$leakage || !array_filter($leakage,static fn(array $row):bool=>in_array((string)$row['status'],['open','investigating'],true))) $score+=10;
    return min(100,$score);
}

function savings_realization_submit_baseline(array $baseline): array
{
    if (!in_array(savings_realization_baseline_status($baseline),['draft','changes_requested'],true)) throw new RuntimeException('This baseline is already routed or closed.');
    if ((float)$baseline['baseline_total_cost']<=0) throw new RuntimeException('A positive baseline total is required.');
    $approval=data_upsert('workflow_approvals',[
        'id'=>null,'company_id'=>savings_realization_find_opportunity((int)$baseline['opportunity_id'])['company_id']??null,
        'module'=>'savings','entity_type'=>'savings_baseline','entity_id'=>$baseline['id'],
        'title'=>'Savings baseline v'.$baseline['version_number'].' · '.money((float)$baseline['baseline_total_cost']),
        'submitted_by'=>(int)current_user()['id'],'assigned_to'=>$baseline['reviewer_id'],
        'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+3 days')),
        'notes'=>'Validate period, volume, cost, methodology, assumptions, source transactions, and financial comparability.',
    ]);
    $before=$baseline['status'];
    $baseline['status']='submitted';
    $baseline['approval_id']=(int)$approval['id'];
    $saved=savings_realization_save_baseline($baseline);
    savings_realization_add_event((int)$saved['opportunity_id'],null,'baseline_submitted',$before,'submitted','medium',(float)$saved['baseline_total_cost'],'Baseline routed for independent review.');
    $opportunity=savings_realization_find_opportunity((int)$saved['opportunity_id']);
    if ($opportunity) savings_realization_notify((int)$saved['reviewer_id'],$opportunity,'Savings baseline awaiting review','Baseline version '.$saved['version_number'].' requires finance review.','warning');
    return $saved;
}

function savings_realization_lock_baseline(array $baseline,string $evidence): array
{
    require_permission('savings.approve');
    if (savings_realization_baseline_status($baseline)!=='approved') throw new RuntimeException('The workflow approval must be approved before the baseline can be locked.');
    $opportunity=savings_realization_find_opportunity((int)$baseline['opportunity_id']);
    if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
    if ((int)($opportunity['owner_id']??0)===(int)current_user()['id']) throw new RuntimeException('The opportunity owner cannot independently approve the finance baseline.');
    foreach (savings_realization_baselines((int)$baseline['opportunity_id']) as $other) {
        if ((int)$other['id']===(int)$baseline['id'] || (string)$other['status']!=='approved') continue;
        $other['status']='superseded';
        savings_realization_save_baseline($other);
    }
    $before=$baseline['status'];
    $baseline['status']='approved';
    $baseline['locked_at']=date('Y-m-d H:i:s');
    $baseline['evidence_note']=mb_substr($evidence,0,5000);
    $saved=savings_realization_save_baseline($baseline);
    savings_realization_add_event((int)$saved['opportunity_id'],null,'baseline_locked',$before,'approved','medium',(float)$saved['baseline_total_cost'],$evidence);
    return $saved;
}

function savings_realization_submit_period(array $period): array
{
    if (!in_array(savings_realization_period_status($period),['draft','changes_requested'],true)) throw new RuntimeException('This realization period is already routed or closed.');
    $opportunity=savings_realization_find_opportunity((int)$period['opportunity_id']);
    if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
    $baseline=savings_realization_current_baseline((int)$opportunity['id']);
    if (!$baseline || savings_realization_baseline_status($baseline)!=='approved') throw new RuntimeException('An approved and locked baseline is required before period submission.');
    $verified=array_filter(savings_realization_evidence((int)$opportunity['id'],(int)$period['id']),static fn(array $row):bool=>(string)$row['status']==='verified');
    if (!$verified) throw new RuntimeException('At least one verified transaction evidence link is required before period submission.');
    $calculated=savings_realization_calculate_period($period);
    $period=array_replace($period,$calculated);
    $approval=data_upsert('workflow_approvals',[
        'id'=>null,'company_id'=>$opportunity['company_id']??null,'module'=>'savings',
        'entity_type'=>'savings_realization_period','entity_id'=>$period['id'],
        'title'=>$opportunity['opportunity_number'].' · '.$period['fiscal_period'].' · '.money((float)$period['net_realized_value']),
        'submitted_by'=>(int)current_user()['id'],'assigned_to'=>$period['reviewer_id'],
        'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+4 days')),
        'notes'=>'Reconcile the approved baseline, source transactions, hard savings, cost avoidance, recoveries, working capital, costs, leakage, and adjustments.',
    ]);
    $before=$period['status'];
    $period['status']='submitted';
    $period['approval_id']=(int)$approval['id'];
    $period['submitted_at']=date('Y-m-d H:i:s');
    $saved=savings_realization_save_period($period);
    savings_realization_add_event((int)$saved['opportunity_id'],(int)$saved['id'],'period_submitted',$before,'submitted','medium',(float)$saved['net_realized_value'],'Realization period submitted for independent finance validation.');
    savings_realization_notify((int)$saved['reviewer_id'],$opportunity,'Savings realization awaiting validation',$opportunity['opportunity_number'].' '.$saved['fiscal_period'].' requires review.','warning');
    return $saved;
}

function savings_realization_validate_period(array $period,string $comments): array
{
    require_permission('savings.approve');
    if (savings_realization_period_status($period)!=='approved') throw new RuntimeException('The workflow approval must be approved before finance validation.');
    $opportunity=savings_realization_find_opportunity((int)$period['opportunity_id']);
    if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
    $userId=(int)current_user()['id'];
    if ($userId===(int)($opportunity['owner_id']??0) || $userId===(int)($period['owner_id']??0)) throw new RuntimeException('The procurement owner cannot independently validate the realized financial value.');
    $score=savings_realization_evidence_completeness($opportunity,$period);
    if ($score<75) throw new RuntimeException('Evidence completeness must reach 75% before finance validation.');
    $period=array_replace($period,savings_realization_calculate_period($period));
    $validation=savings_realization_save_validation([
        'id'=>null,'opportunity_id'=>$opportunity['id'],'realization_period_id'=>$period['id'],
        'validation_number'=>savings_realization_number('VAL-SAV',savings_realization_validations()),
        'reviewer_id'=>$userId,'decision'=>'validated','completeness_score'=>$score,
        'validated_hard_savings'=>$period['actual_hard_savings'],'validated_cost_avoidance'=>$period['actual_cost_avoidance'],
        'validated_recoveries'=>$period['actual_recoveries'],'validated_working_capital'=>$period['actual_working_capital'],
        'validated_net_value'=>$period['net_realized_value'],'comments'=>mb_substr($comments,0,5000),'decided_at'=>date('Y-m-d H:i:s'),
    ]);
    $before=$period['status'];
    $period['status']='validated';
    $period['validated_at']=date('Y-m-d H:i:s');
    $saved=savings_realization_save_period($period);
    savings_realization_recompute_opportunity((int)$opportunity['id']);
    savings_realization_add_event((int)$saved['opportunity_id'],(int)$saved['id'],'period_validated',$before,'validated','medium',(float)$saved['net_realized_value'],$comments);
    return ['period'=>$saved,'validation'=>$validation];
}

function savings_realization_request_changes(array $period,string $comments): array
{
    require_permission('savings.review');
    $opportunity=savings_realization_find_opportunity((int)$period['opportunity_id']);
    if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
    $score=savings_realization_evidence_completeness($opportunity,$period);
    savings_realization_save_validation([
        'id'=>null,'opportunity_id'=>$opportunity['id'],'realization_period_id'=>$period['id'],
        'validation_number'=>savings_realization_number('VAL-SAV',savings_realization_validations()),
        'reviewer_id'=>(int)current_user()['id'],'decision'=>'changes_requested','completeness_score'=>$score,
        'validated_hard_savings'=>0,'validated_cost_avoidance'=>0,'validated_recoveries'=>0,'validated_working_capital'=>0,
        'validated_net_value'=>0,'comments'=>mb_substr($comments,0,5000),'decided_at'=>date('Y-m-d H:i:s'),
    ]);
    $before=$period['status'];
    $period['status']='changes_requested';
    $saved=savings_realization_save_period($period);
    savings_realization_add_event((int)$saved['opportunity_id'],(int)$saved['id'],'period_changes_requested',$before,'changes_requested','high',0,$comments);
    return $saved;
}

function savings_realization_close_period(array $period,string $evidence): array
{
    require_permission('savings.approve');
    if ((string)$period['status']!=='validated') throw new RuntimeException('Only a validated period can be closed.');
    $before=$period['status'];
    $period['status']='closed';
    $period['closed_at']=date('Y-m-d H:i:s');
    $period['evidence_note']=mb_substr($evidence,0,5000);
    $saved=savings_realization_save_period($period);
    savings_realization_add_event((int)$saved['opportunity_id'],(int)$saved['id'],'period_closed',$before,'closed','medium',(float)$saved['net_realized_value'],$evidence);
    return $saved;
}

function savings_realization_verify_evidence(array $evidence,string $note): array
{
    require_permission('savings.review');
    if ((int)($evidence['created_by']??0)===(int)current_user()['id']) throw new RuntimeException('The evidence creator cannot independently verify the same source record.');
    $before=$evidence['status'];
    $evidence['status']='verified';
    $evidence['verified_by']=(int)current_user()['id'];
    $evidence['verified_at']=date('Y-m-d H:i:s');
    $evidence['evidence_note']=mb_substr($note,0,5000);
    $saved=savings_realization_save_evidence($evidence);
    savings_realization_add_event((int)$saved['opportunity_id'],$saved['realization_period_id']?(int)$saved['realization_period_id']:null,'evidence_verified',$before,'verified','medium',(float)$saved['evidence_amount'],$note);
    return $saved;
}

function savings_realization_recover_leakage(array $leakage,float $recovered,string $evidence): array
{
    require_permission('savings.edit');
    if ($recovered<0 || $recovered>(float)$leakage['amount']+0.0001) throw new RuntimeException('Recovered value must be between zero and the leakage amount.');
    $before=$leakage['status'];
    $leakage['recovered_amount']=$recovered;
    $leakage['status']=$recovered+0.0001>=(float)$leakage['amount']?'recovered':'contained';
    $leakage['evidence_note']=mb_substr($evidence,0,5000);
    $saved=savings_realization_save_leakage($leakage);
    savings_realization_add_event((int)$saved['opportunity_id'],$saved['realization_period_id']?(int)$saved['realization_period_id']:null,'leakage_reconciled',$before,$saved['status'],'medium',$recovered,$evidence);
    return $saved;
}

function savings_realization_recompute_opportunity(int $opportunityId): array
{
    $opportunity=savings_realization_find_opportunity($opportunityId);
    if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
    $periods=savings_realization_periods($opportunityId);
    $validated=array_values(array_filter($periods,static fn(array $row):bool=>in_array((string)$row['status'],['validated','closed'],true)));
    $net=array_sum(array_map(static fn(array $row):float=>(float)$row['net_realized_value'],$validated));
    $opportunity['realized_savings']=round($net,2);
    $opportunity['accounting_validation']=$validated?'validated':'pending';
    if ($validated) {
        $opportunity['stage']='approved';
        $opportunity['operational_status']=$net+0.01>=(float)$opportunity['annualized_value']?'completed':'implementing';
    }
    return data_upsert('savings_opportunities',$opportunity);
}

function savings_realization_metrics(?array $selected=null): array
{
    $opportunities=savings_realization_opportunities();
    $periods=savings_realization_periods();
    $validated=array_values(array_filter($periods,static fn(array $row):bool=>in_array((string)$row['status'],['validated','closed'],true)));
    $leakage=savings_realization_leakage();
    if ($selected) {
        $id=(int)$selected['id'];
        $periods=savings_realization_periods($id);
        $validated=array_values(array_filter($periods,static fn(array $row):bool=>in_array((string)$row['status'],['validated','closed'],true)));
        $leakage=savings_realization_leakage($id);
        $opportunities=[$selected];
    }
    $grossPipeline=array_sum(array_map(static fn(array $row):float=>(float)$row['annualized_value'],$opportunities));
    $weighted=array_sum(array_map(static fn(array $row):float=>(float)$row['annualized_value']*((int)$row['confidence']/100),$opportunities));
    $net=array_sum(array_map(static fn(array $row):float=>(float)$row['net_realized_value'],$validated));
    $hard=array_sum(array_map(static fn(array $row):float=>(float)$row['actual_hard_savings'],$validated));
    $avoidance=array_sum(array_map(static fn(array $row):float=>(float)$row['actual_cost_avoidance'],$validated));
    $recoveries=array_sum(array_map(static fn(array $row):float=>(float)$row['actual_recoveries'],$validated));
    $working=array_sum(array_map(static fn(array $row):float=>(float)$row['actual_working_capital'],$validated));
    $implementation=array_sum(array_map(static fn(array $row):float=>(float)$row['implementation_cost']+(float)$row['operating_cost'],$validated));
    $periodLeakage=array_sum(array_map(static fn(array $row):float=>(float)$row['leakage_amount'],$validated));
    $atRisk=array_sum(array_map(static fn(array $row):float=>in_array((string)$row['status'],['open','investigating','contained'],true)?max(0,(float)$row['amount']-(float)$row['recovered_amount']):0,$leakage));
    $planned=array_sum(array_map(static fn(array $row):float=>(float)$row['planned_hard_savings']+(float)$row['planned_cost_avoidance']+(float)$row['planned_recoveries']+(float)$row['planned_working_capital'],$periods));
    return [
        'gross_pipeline'=>$grossPipeline,'weighted_forecast'=>$weighted,'finance_validated'=>$net,'net_realized'=>$net,
        'hard_savings'=>$hard,'cost_avoidance'=>$avoidance,'recoveries'=>$recoveries,'working_capital'=>$working,
        'implementation_cost'=>$implementation,'leakage_value'=>$periodLeakage,'benefits_at_risk'=>$atRisk,
        'realization_pct'=>$grossPipeline>0?round($net/$grossPipeline*100,1):0,
        'forecast_accuracy'=>$planned>0?round(min(200,$net/$planned*100),1):0,
        'validated_periods'=>count($validated),'pending_periods'=>count(array_filter($periods,static fn(array $row):bool=>!in_array((string)$row['status'],['validated','closed','rejected'],true))),
    ];
}

function savings_realization_notify(int $userId,array $opportunity,string $title,string $message,string $severity='info'): void
{
    if ($userId<=0) return;
    data_upsert('notifications',[
        'id'=>null,'user_id'=>$userId,'company_id'=>$opportunity['company_id']??null,'module'=>'savings',
        'title'=>$title,'message'=>$message,'severity'=>$severity,'entity_type'=>'savings_opportunity',
        'entity_id'=>$opportunity['id'],'read'=>false,'created_at'=>date('Y-m-d H:i:s'),
    ]);
}
