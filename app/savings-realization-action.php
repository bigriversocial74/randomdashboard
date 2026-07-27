<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/savings_realization.php';
require_app_user();
if (request_method()!=='POST') redirect_to(app_url('savings-realization.php'));
verify_csrf();

function savings_realization_valid_date(string $value,bool $allowBlank=false): ?string
{
    $value=trim($value);
    if ($allowBlank && $value==='') return null;
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    if (!$date || $date->format('Y-m-d')!==$value) throw new RuntimeException('A valid date is required.');
    return $value;
}

function savings_realization_required_evidence(string $value): string
{
    $value=trim($value);
    if ($value==='') throw new RuntimeException('Governance evidence is required.');
    return mb_substr($value,0,5000);
}

function savings_realization_post_float(string $key,float $default=0): float
{
    $value=trim(post_string($key,(string)$default));
    if ($value==='' || !is_numeric($value)) return $default;
    return round((float)$value,2);
}

function savings_realization_action_redirect(int $opportunityId,array $extra=[]): never
{
    $params=array_replace(['id'=>$opportunityId],$extra);
    redirect_to(app_url('savings-realization.php?'.http_build_query($params)));
}

try {
    $action=post_string('action');

    if ($action==='save_baseline') {
        require_permission('savings.edit');
        $opportunityId=post_int('opportunity_id');
        $opportunity=savings_realization_find_opportunity($opportunityId);
        if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
        $id=post_int('id');
        $existing=$id?savings_realization_find_baseline($id):null;
        if ($existing && !in_array(savings_realization_baseline_status($existing),['draft','changes_requested'],true)) throw new RuntimeException('Only a draft or changes-requested baseline can be edited.');
        $version=$existing?(int)$existing['version_number']:(max([0,...array_map('intval',array_column(savings_realization_baselines($opportunityId),'version_number'))])+1);
        $start=savings_realization_valid_date(post_string('period_start'));
        $end=savings_realization_valid_date(post_string('period_end'));
        if ($end<$start) throw new RuntimeException('The baseline end date must not precede the start date.');
        $volume=savings_realization_post_float('baseline_volume');
        $unit=savings_realization_post_float('baseline_unit_cost');
        $total=savings_realization_post_float('baseline_total_cost',$volume*$unit);
        if ($total<=0) throw new RuntimeException('Baseline total cost must be positive.');
        $ownerId=post_int('owner_id',(int)current_user()['id']);
        $reviewerId=post_int('reviewer_id',6);
        if ($ownerId===$reviewerId) throw new RuntimeException('The baseline owner and finance reviewer must be different users.');
        $record=[
            'id'=>$existing['id']??null,'opportunity_id'=>$opportunityId,'version_number'=>$version,
            'baseline_type'=>post_string('baseline_type','historical_spend'),'period_start'=>$start,'period_end'=>$end,
            'baseline_volume'=>$volume,'baseline_unit_cost'=>$unit,'baseline_total_cost'=>$total,'currency_code'=>'USD',
            'methodology'=>savings_realization_required_evidence(post_string('methodology')),
            'assumptions'=>mb_substr(post_string('assumptions'),0,5000),
            'supplier_id'=>post_int('supplier_id')?:null,'contract_id'=>post_int('contract_id')?:null,
            'status'=>$existing['status']??'draft','owner_id'=>$ownerId,
            'reviewer_id'=>$reviewerId,'approval_id'=>$existing['approval_id']??null,
            'locked_at'=>$existing['locked_at']??null,'evidence_note'=>savings_realization_required_evidence(post_string('evidence_note')),
            'created_at'=>$existing['created_at']??date('Y-m-d H:i:s'),
        ];
        $saved=savings_realization_save_baseline($record);
        savings_realization_add_event($opportunityId,null,$existing?'baseline_updated':'baseline_created',$existing['status']??null,$saved['status'],'medium',$total,$saved['evidence_note']);
        data_add_audit('Savings','baseline_saved','savings_baseline',$saved['id'],$existing,$saved,$opportunity['company_id']??null);
        flash('success','Savings baseline saved.');
        savings_realization_action_redirect($opportunityId);
    }

    if ($action==='submit_baseline') {
        require_permission('savings.submit');
        $baseline=savings_realization_find_baseline(post_int('id'));
        if (!$baseline) throw new RuntimeException('The savings baseline is unavailable.');
        $saved=savings_realization_submit_baseline($baseline);
        flash('success','Savings baseline submitted for independent review.');
        savings_realization_action_redirect((int)$saved['opportunity_id']);
    }

    if ($action==='lock_baseline') {
        $baseline=savings_realization_find_baseline(post_int('id'));
        if (!$baseline) throw new RuntimeException('The savings baseline is unavailable.');
        $saved=savings_realization_lock_baseline($baseline,savings_realization_required_evidence(post_string('evidence_note')));
        flash('success','Finance-approved baseline locked.');
        savings_realization_action_redirect((int)$saved['opportunity_id']);
    }

    if ($action==='save_period') {
        require_permission('savings.edit');
        $opportunityId=post_int('opportunity_id');
        $opportunity=savings_realization_find_opportunity($opportunityId);
        if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
        $id=post_int('id');
        $existing=$id?savings_realization_find_period($id):null;
        if ($existing && !in_array(savings_realization_period_status($existing),['draft','changes_requested'],true)) throw new RuntimeException('Only a draft or changes-requested period can be edited.');
        $start=savings_realization_valid_date(post_string('period_start'));
        $end=savings_realization_valid_date(post_string('period_end'));
        if ($end<$start) throw new RuntimeException('The realization end date must not precede the start date.');
        $ownerId=post_int('owner_id',(int)current_user()['id']);
        $reviewerId=post_int('reviewer_id',6);
        if ($ownerId===$reviewerId) throw new RuntimeException('The realization owner and finance reviewer must be different users.');
        $record=[
            'id'=>$existing['id']??null,'opportunity_id'=>$opportunityId,'period_start'=>$start,'period_end'=>$end,
            'fiscal_year'=>(int)date('Y',strtotime($end)),'fiscal_period'=>post_string('fiscal_period',date('Y-m',strtotime($end))),
            'planned_hard_savings'=>savings_realization_post_float('planned_hard_savings'),
            'planned_cost_avoidance'=>savings_realization_post_float('planned_cost_avoidance'),
            'planned_recoveries'=>savings_realization_post_float('planned_recoveries'),
            'planned_working_capital'=>savings_realization_post_float('planned_working_capital'),
            'actual_hard_savings'=>savings_realization_post_float('actual_hard_savings'),
            'actual_cost_avoidance'=>savings_realization_post_float('actual_cost_avoidance'),
            'actual_recoveries'=>savings_realization_post_float('actual_recoveries'),
            'actual_working_capital'=>savings_realization_post_float('actual_working_capital'),
            'implementation_cost'=>savings_realization_post_float('implementation_cost'),
            'operating_cost'=>savings_realization_post_float('operating_cost'),
            'leakage_amount'=>savings_realization_post_float('leakage_amount'),
            'adjustment_amount'=>savings_realization_post_float('adjustment_amount'),
            'status'=>$existing['status']??'draft','owner_id'=>$ownerId,
            'reviewer_id'=>$reviewerId,'approval_id'=>$existing['approval_id']??null,
            'submitted_at'=>$existing['submitted_at']??null,'validated_at'=>$existing['validated_at']??null,
            'closed_at'=>$existing['closed_at']??null,
            'evidence_note'=>savings_realization_required_evidence(post_string('evidence_note')),
            'created_at'=>$existing['created_at']??date('Y-m-d H:i:s'),
        ];
        $record=array_replace($record,savings_realization_calculate_period($record));
        $saved=savings_realization_save_period($record);
        savings_realization_add_event($opportunityId,(int)$saved['id'],$existing?'period_updated':'period_created',$existing['status']??null,$saved['status'],'medium',(float)$saved['net_realized_value'],$saved['evidence_note']);
        data_add_audit('Savings','realization_period_saved','savings_realization_period',$saved['id'],$existing,$saved,$opportunity['company_id']??null);
        flash('success','Savings realization period saved.');
        savings_realization_action_redirect($opportunityId,['period_id'=>$saved['id']]);
    }

    if ($action==='add_evidence') {
        require_permission('savings.edit');
        $opportunityId=post_int('opportunity_id');
        $opportunity=savings_realization_find_opportunity($opportunityId);
        if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
        $periodId=post_int('realization_period_id')?:null;
        if ($periodId) {
            $period=savings_realization_find_period($periodId);
            if (!$period || (int)$period['opportunity_id']!==$opportunityId) throw new RuntimeException('The realization period is unavailable.');
        }
        $types=['supplier_comparison','supplier_contract','contract_amendment','purchase_request','purchase_order','purchase_order_line','receipt','supplier_invoice','match_result','invoice_exception','supplier_credit','inventory_transfer','replenishment_avoidance','quality_recovery','corrective_action','other'];
        $type=post_string('entity_type','other');
        if (!in_array($type,$types,true)) throw new RuntimeException('The evidence type is invalid.');
        $record=[
            'id'=>null,'opportunity_id'=>$opportunityId,'realization_period_id'=>$periodId,'entity_type'=>$type,
            'entity_id'=>post_int('entity_id')?:null,'evidence_reference'=>mb_substr(post_string('evidence_reference'),0,160),
            'evidence_amount'=>savings_realization_post_float('evidence_amount'),'evidence_date'=>savings_realization_valid_date(post_string('evidence_date')),
            'status'=>'linked','verified_by'=>null,'verified_at'=>null,
            'evidence_note'=>savings_realization_required_evidence(post_string('evidence_note')),
            'created_by'=>(int)current_user()['id'],
        ];
        $saved=savings_realization_save_evidence($record);
        savings_realization_add_event($opportunityId,$periodId,'evidence_linked',null,'linked','medium',(float)$saved['evidence_amount'],$saved['evidence_note']);
        flash('success','Transaction evidence linked.');
        savings_realization_action_redirect($opportunityId,$periodId?['period_id'=>$periodId]:[]);
    }

    if ($action==='verify_evidence') {
        $evidence=savings_realization_find_evidence(post_int('id'));
        if (!$evidence) throw new RuntimeException('The savings evidence is unavailable.');
        $saved=savings_realization_verify_evidence($evidence,savings_realization_required_evidence(post_string('evidence_note')));
        flash('success','Savings evidence verified.');
        savings_realization_action_redirect((int)$saved['opportunity_id'],$saved['realization_period_id']?['period_id'=>$saved['realization_period_id']]:[]);
    }

    if ($action==='submit_period') {
        require_permission('savings.submit');
        $period=savings_realization_find_period(post_int('id'));
        if (!$period) throw new RuntimeException('The realization period is unavailable.');
        $saved=savings_realization_submit_period($period);
        flash('success','Realization period submitted for finance validation.');
        savings_realization_action_redirect((int)$saved['opportunity_id'],['period_id'=>$saved['id']]);
    }

    if ($action==='validate_period') {
        $period=savings_realization_find_period(post_int('id'));
        if (!$period) throw new RuntimeException('The realization period is unavailable.');
        $result=savings_realization_validate_period($period,savings_realization_required_evidence(post_string('comments')));
        flash('success','Finance validated the realization period.');
        savings_realization_action_redirect((int)$result['period']['opportunity_id'],['period_id'=>$result['period']['id']]);
    }

    if ($action==='request_changes_period') {
        $period=savings_realization_find_period(post_int('id'));
        if (!$period) throw new RuntimeException('The realization period is unavailable.');
        $saved=savings_realization_request_changes($period,savings_realization_required_evidence(post_string('comments')));
        flash('warning','Changes requested for the realization period.');
        savings_realization_action_redirect((int)$saved['opportunity_id'],['period_id'=>$saved['id']]);
    }

    if ($action==='close_period') {
        $period=savings_realization_find_period(post_int('id'));
        if (!$period) throw new RuntimeException('The realization period is unavailable.');
        $saved=savings_realization_close_period($period,savings_realization_required_evidence(post_string('evidence_note')));
        flash('success','Validated finance period closed.');
        savings_realization_action_redirect((int)$saved['opportunity_id'],['period_id'=>$saved['id']]);
    }

    if ($action==='save_leakage') {
        require_permission('savings.edit');
        $opportunityId=post_int('opportunity_id');
        $opportunity=savings_realization_find_opportunity($opportunityId);
        if (!$opportunity) throw new RuntimeException('The savings opportunity is unavailable.');
        $periodId=post_int('realization_period_id')?:null;
        if ($periodId) {
            $period=savings_realization_find_period($periodId);
            if (!$period || (int)$period['opportunity_id']!==$opportunityId) throw new RuntimeException('The leakage realization period is unavailable.');
        }
        $amount=savings_realization_post_float('amount');
        if ($amount<=0) throw new RuntimeException('Leakage value must be positive.');
        $types=['contract_price_erosion','off_contract_purchase','missed_credit','invoice_overpayment','volume_shortfall','implementation_delay','supplier_noncompliance','emergency_purchase','missed_transfer','inventory_carrying_cost','benefit_expiration','other'];
        $type=post_string('leakage_type','other');
        if (!in_array($type,$types,true)) throw new RuntimeException('The leakage type is invalid.');
        $record=[
            'id'=>null,'opportunity_id'=>$opportunityId,'realization_period_id'=>$periodId,'leakage_type'=>$type,
            'detected_date'=>savings_realization_valid_date(post_string('detected_date')),'amount'=>$amount,'recovered_amount'=>0,
            'status'=>'open','owner_id'=>post_int('owner_id',(int)current_user()['id']),
            'due_date'=>savings_realization_valid_date(post_string('due_date'),true),
            'source_entity_type'=>mb_substr(post_string('source_entity_type'),0,80),'source_entity_id'=>post_int('source_entity_id')?:null,
            'root_cause'=>savings_realization_required_evidence(post_string('root_cause')),
            'corrective_action'=>savings_realization_required_evidence(post_string('corrective_action')),
            'evidence_note'=>savings_realization_required_evidence(post_string('evidence_note')),
        ];
        $saved=savings_realization_save_leakage($record);
        savings_realization_add_event($opportunityId,$periodId,'leakage_detected',null,'open','high',$amount,$saved['evidence_note']);
        savings_realization_notify((int)$saved['owner_id'],$opportunity,'Savings leakage requires action',money($amount).' of potential value is at risk.','warning');
        flash('warning','Savings leakage recorded for corrective action.');
        savings_realization_action_redirect($opportunityId,$periodId?['period_id'=>$periodId]:[]);
    }

    if ($action==='recover_leakage') {
        $leakage=savings_realization_find_leakage(post_int('id'));
        if (!$leakage) throw new RuntimeException('The savings leakage record is unavailable.');
        $saved=savings_realization_recover_leakage($leakage,savings_realization_post_float('recovered_amount'),savings_realization_required_evidence(post_string('evidence_note')));
        flash('success','Savings leakage recovery updated.');
        savings_realization_action_redirect((int)$saved['opportunity_id'],$saved['realization_period_id']?['period_id'=>$saved['realization_period_id']]:[]);
    }

    throw new RuntimeException('Unknown savings-realization action.');
} catch (Throwable $exception) {
    flash('error','The savings-realization action could not be completed: '.$exception->getMessage());
    $opportunityId=post_int('opportunity_id');
    if (!$opportunityId) {
        $baseline=savings_realization_find_baseline(post_int('id'));
        $period=savings_realization_find_period(post_int('id'));
        $evidence=savings_realization_find_evidence(post_int('id'));
        $leakage=savings_realization_find_leakage(post_int('id'));
        $opportunityId=(int)($baseline['opportunity_id']??$period['opportunity_id']??$evidence['opportunity_id']??$leakage['opportunity_id']??0);
    }
    if ($opportunityId) savings_realization_action_redirect($opportunityId);
    redirect_to(app_url('savings-realization.php'));
}
