<?php
declare(strict_types=1);

function savings_realization_demo_baselines(): array
{
    return [
        [
            'id'=>1,'opportunity_id'=>1,'version_number'=>1,'baseline_type'=>'historical_spend',
            'period_start'=>'2025-07-01','period_end'=>'2026-06-30','baseline_volume'=>4800,
            'baseline_unit_cost'=>210.00,'baseline_total_cost'=>1008000.00,'currency_code'=>'USD',
            'methodology'=>'Trailing twelve-month purchase-order and accepted-invoice baseline.',
            'assumptions'=>'Normalized for comparable battery specifications and standard freight.',
            'supplier_id'=>1,'contract_id'=>null,'status'=>'approved','owner_id'=>3,'reviewer_id'=>6,
            'approval_id'=>null,'locked_at'=>'2026-07-15 14:00:00',
            'evidence_note'=>'Approved baseline supported by purchase-order, receipt, and invoice history.',
            'created_at'=>'2026-07-10 09:00:00','updated_at'=>'2026-07-15 14:00:00',
        ],
    ];
}

function savings_realization_demo_periods(): array
{
    return [
        [
            'id'=>1,'opportunity_id'=>1,'period_start'=>'2026-07-01','period_end'=>'2026-07-31',
            'fiscal_year'=>2026,'fiscal_period'=>'2026-07',
            'planned_hard_savings'=>18000.00,'planned_cost_avoidance'=>3500.00,'planned_recoveries'=>0,
            'planned_working_capital'=>2500.00,'actual_hard_savings'=>16400.00,
            'actual_cost_avoidance'=>3200.00,'actual_recoveries'=>1200.00,'actual_working_capital'=>2200.00,
            'implementation_cost'=>1800.00,'operating_cost'=>400.00,'leakage_amount'=>900.00,
            'adjustment_amount'=>0.00,'gross_realized_value'=>23000.00,'net_realized_value'=>19900.00,
            'status'=>'validated','owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,
            'submitted_at'=>'2026-07-23 11:00:00','validated_at'=>'2026-07-25 15:00:00','closed_at'=>null,
            'evidence_note'=>'July value reconciled to accepted receipts, invoices, credits, and avoided emergency purchases.',
            'created_at'=>'2026-07-20 09:00:00','updated_at'=>'2026-07-25 15:00:00',
        ],
    ];
}

function savings_realization_demo_evidence(): array
{
    return [
        [
            'id'=>1,'opportunity_id'=>1,'realization_period_id'=>1,'entity_type'=>'supplier_invoice',
            'entity_id'=>1,'evidence_reference'=>'INV-GPS-24071','evidence_amount'=>16400.00,
            'evidence_date'=>'2026-07-22','status'=>'verified','verified_by'=>6,
            'verified_at'=>'2026-07-25 14:30:00','evidence_note'=>'Invoice and three-way-match evidence for lower contract pricing.',
            'created_by'=>3,'created_at'=>'2026-07-23 10:00:00','updated_at'=>'2026-07-25 14:30:00',
        ],
        [
            'id'=>2,'opportunity_id'=>1,'realization_period_id'=>1,'entity_type'=>'supplier_credit',
            'entity_id'=>1,'evidence_reference'=>'CR-24071','evidence_amount'=>1200.00,
            'evidence_date'=>'2026-07-24','status'=>'verified','verified_by'=>6,
            'verified_at'=>'2026-07-25 14:35:00','evidence_note'=>'Supplier credit recovered from invoice exception resolution.',
            'created_by'=>3,'created_at'=>'2026-07-24 12:00:00','updated_at'=>'2026-07-25 14:35:00',
        ],
    ];
}

function savings_realization_demo_validations(): array
{
    return [
        [
            'id'=>1,'opportunity_id'=>1,'realization_period_id'=>1,'validation_number'=>'VAL-SAV-2026-0001',
            'reviewer_id'=>6,'decision'=>'validated','completeness_score'=>95,
            'validated_hard_savings'=>16400.00,'validated_cost_avoidance'=>3200.00,
            'validated_recoveries'=>1200.00,'validated_working_capital'=>2200.00,
            'validated_net_value'=>19900.00,'comments'=>'Evidence reconciles to the approved baseline and source transactions.',
            'decided_at'=>'2026-07-25 15:00:00','created_at'=>'2026-07-25 15:00:00',
        ],
    ];
}

function savings_realization_demo_leakage(): array
{
    return [
        [
            'id'=>1,'opportunity_id'=>1,'realization_period_id'=>1,'leakage_type'=>'implementation_delay',
            'detected_date'=>'2026-07-18','amount'=>900.00,'recovered_amount'=>0.00,'status'=>'contained',
            'owner_id'=>3,'due_date'=>'2026-08-05','source_entity_type'=>'purchase_order','source_entity_id'=>1,
            'root_cause'=>'Two locations adopted the negotiated supplier one week later than planned.',
            'corrective_action'=>'Complete supplier conversion and block legacy catalog pricing.',
            'evidence_note'=>'Leakage reflected in the July realization period.',
            'created_at'=>'2026-07-18 13:00:00','updated_at'=>'2026-07-25 14:00:00',
        ],
    ];
}

function savings_realization_demo_events(): array
{
    return [
        [
            'id'=>1,'opportunity_id'=>1,'realization_period_id'=>1,'event_type'=>'period_validated',
            'from_status'=>'submitted','to_status'=>'validated','severity'=>'medium',
            'value_amount'=>19900.00,'evidence_note'=>'Finance validated the July realization period.',
            'created_by'=>6,'created_at'=>'2026-07-25 15:00:00',
        ],
        [
            'id'=>2,'opportunity_id'=>1,'realization_period_id'=>null,'event_type'=>'baseline_locked',
            'from_status'=>'submitted','to_status'=>'approved','severity'=>'medium',
            'value_amount'=>1008000.00,'evidence_note'=>'Finance-approved baseline locked for realization measurement.',
            'created_by'=>6,'created_at'=>'2026-07-15 14:00:00',
        ],
    ];
}
