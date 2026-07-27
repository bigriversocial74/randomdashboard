<?php
declare(strict_types=1);

function spend_strategy_demo_snapshots(): array { return []; }
function spend_strategy_demo_classifications(): array { return []; }
function spend_strategy_demo_strategies(): array
{
    return [[
        'id'=>1,'strategy_number'=>'CAT-2026-001','company_id'=>1,'category_id'=>2,'fiscal_year'=>2026,
        'title'=>'Electrical materials enterprise strategy','status'=>'draft','owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,
        'current_spend'=>0,'addressable_spend'=>0,'contracted_spend'=>0,'supplier_count'=>0,'concentration_pct'=>0,'high_risk_spend'=>0,'validated_savings'=>0,
        'demand_summary'=>'Consolidate recurring electrical demand across operating companies and planned projects.',
        'market_structure'=>'Regional distributors with multiple qualified alternates and manufacturer-direct options.',
        'supplier_panel'=>'Preferred and alternate suppliers will be confirmed from approved supplier records.',
        'risk_summary'=>'Monitor concentration, delivery reliability, emergency purchases, and contract coverage.',
        'inventory_alternatives'=>'Use existing stock and cross-company transfers before external purchasing.',
        'performance_summary'=>'Review scorecards, invoice accuracy, quality events, and corrective-action history.',
        'negotiation_strategy'=>'Aggregate forecasted volume and negotiate tiered pricing, freight, and payment terms.',
        'sourcing_strategy'=>'Run a competitive sourcing event with incumbent and qualified alternate suppliers.',
        'target_terms'=>'Net 45, contracted freight rules, price holds, service levels, and documented alternates.',
        'strategy_decision'=>'consolidate_with_alternates','review_date'=>date('Y-m-d',strtotime('+90 days')),'renewal_date'=>date('Y-m-d',strtotime('+365 days')),
        'evidence_note'=>'Seeded demonstration strategy; refresh the spend snapshot to populate current metrics.','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),
    ]];
}
function spend_strategy_demo_actions(): array { return []; }
function spend_strategy_demo_plans(): array { return []; }
function spend_strategy_demo_targets(): array { return []; }
function spend_strategy_demo_events(): array { return []; }
