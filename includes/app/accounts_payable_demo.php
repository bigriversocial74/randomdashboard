<?php
declare(strict_types=1);

function accounts_payable_demo_schedules(): array { return []; }
function accounts_payable_demo_batches(): array { return []; }
function accounts_payable_demo_batch_items(): array { return []; }
function accounts_payable_demo_executions(): array { return []; }
function accounts_payable_demo_remittances(): array { return []; }
function accounts_payable_demo_credits(): array
{
    return [[
        'id'=>1,'credit_number'=>'CR-2026-0001','company_id'=>2,'supplier_id'=>1,'invoice_id'=>null,
        'credit_type'=>'quality_recovery','credit_memo_number'=>'CM-CSCP-2201','credit_date'=>'2026-07-20',
        'expiration_date'=>'2027-07-20','currency_code'=>'USD','original_amount'=>1250.00,'applied_amount'=>0.00,
        'remaining_amount'=>1250.00,'status'=>'validated','owner_id'=>3,'reviewer_id'=>6,
        'evidence_note'=>'Supplier credit validated against quality recovery evidence.','created_at'=>'2026-07-20 10:00:00','updated_at'=>'2026-07-20 10:00:00',
    ]];
}
function accounts_payable_demo_reconciliations(): array { return []; }
function accounts_payable_demo_periods(): array
{
    return [[
        'id'=>1,'period_number'=>'AP-'.date('Ym'),'company_id'=>2,'fiscal_year'=>(int)date('Y'),
        'period_label'=>date('F Y'),'period_start'=>date('Y-m-01'),'period_end'=>date('Y-m-t'),
        'status'=>'open','soft_closed_at'=>null,'hard_closed_at'=>null,'locked_at'=>null,
        'owner_id'=>3,'reviewer_id'=>6,'evidence_note'=>'Seeded open accounting period for AP close governance.',
        'created_at'=>date('Y-m-01').' 08:00:00','updated_at'=>date('Y-m-01').' 08:00:00',
    ]];
}
function accounts_payable_demo_accruals(): array { return []; }
function accounts_payable_demo_certifications(): array { return []; }
function accounts_payable_demo_instructions(): array
{
    return [[
        'id'=>1,'instruction_number'=>'PAYINST-2026-0001','company_id'=>2,'supplier_id'=>1,
        'vault_reference'=>'VAULT-DEMO-CSCP-01','instruction_fingerprint'=>hash('sha256','demo-supplier-1'),
        'payment_method'=>'ach','status'=>'verified','requested_by'=>3,'verified_by'=>6,
        'requested_at'=>'2026-07-01 09:00:00','verified_at'=>'2026-07-02 10:00:00',
        'effective_date'=>'2026-07-03','cooling_until'=>'2026-07-05',
        'callback_evidence'=>'Independent callback completed using supplier master contact.',
        'change_reason'=>'Initial verified payment instruction reference.','created_at'=>'2026-07-01 09:00:00','updated_at'=>'2026-07-02 10:00:00',
    ]];
}
function accounts_payable_demo_events(): array { return []; }
