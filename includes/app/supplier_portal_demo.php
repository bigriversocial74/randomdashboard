<?php
declare(strict_types=1);

function supplier_portal_demo_accounts(): array
{
    return [[
        'id'=>1,'supplier_id'=>1,'supplier_contact_id'=>1,'name'=>'Alex Rivera','email'=>'portal@copperstate.example.test',
        'password_hash'=>password_hash('SupplierDemo!2026', PASSWORD_DEFAULT),'portal_role'=>'account_administrator','status'=>'active',
        'email_verified_at'=>date('Y-m-d H:i:s', strtotime('-30 days')),'mfa_required'=>0,'last_login_at'=>date('Y-m-d H:i:s', strtotime('-1 day')),
        'locked_until'=>null,'created_by'=>1,'created_at'=>date('Y-m-d H:i:s', strtotime('-60 days')),'updated_at'=>date('Y-m-d H:i:s'),
    ]];
}

function supplier_portal_demo_invitations(): array
{
    return [[
        'id'=>1,'supplier_id'=>2,'supplier_contact_id'=>null,'invited_name'=>'Morgan Chen','email'=>'billing@desertelectrical.example.test',
        'portal_role'=>'billing','token_hash'=>hash('sha256','section22-demo-invitation'),'expires_at'=>date('Y-m-d H:i:s', strtotime('+7 days')),
        'status'=>'pending','invited_by'=>1,'accepted_account_id'=>null,'accepted_at'=>null,'created_at'=>date('Y-m-d H:i:s', strtotime('-1 day')),
    ]];
}

function supplier_portal_demo_grants(): array
{
    return [
        ['id'=>1,'account_id'=>1,'company_id'=>1,'access_scope'=>'company','can_acknowledge_po'=>1,'can_submit_asn'=>1,'can_submit_invoice'=>1,'can_submit_documents'=>1,'can_message'=>1,'can_quality'=>1,'can_sourcing'=>1,'starts_at'=>date('Y-m-d',strtotime('-60 days')),'expires_at'=>null,'status'=>'active','granted_by'=>1,'created_at'=>date('Y-m-d H:i:s',strtotime('-60 days')),'updated_at'=>date('Y-m-d H:i:s')],
        ['id'=>2,'account_id'=>1,'company_id'=>2,'access_scope'=>'company','can_acknowledge_po'=>1,'can_submit_asn'=>1,'can_submit_invoice'=>1,'can_submit_documents'=>1,'can_message'=>1,'can_quality'=>1,'can_sourcing'=>1,'starts_at'=>date('Y-m-d',strtotime('-60 days')),'expires_at'=>null,'status'=>'active','granted_by'=>1,'created_at'=>date('Y-m-d H:i:s',strtotime('-60 days')),'updated_at'=>date('Y-m-d H:i:s')],
        ['id'=>3,'account_id'=>1,'company_id'=>3,'access_scope'=>'company','can_acknowledge_po'=>1,'can_submit_asn'=>1,'can_submit_invoice'=>1,'can_submit_documents'=>1,'can_message'=>1,'can_quality'=>1,'can_sourcing'=>1,'starts_at'=>date('Y-m-d',strtotime('-60 days')),'expires_at'=>null,'status'=>'active','granted_by'=>1,'created_at'=>date('Y-m-d H:i:s',strtotime('-60 days')),'updated_at'=>date('Y-m-d H:i:s')],
    ];
}

function supplier_portal_demo_po_responses(): array
{
    return [[
        'id'=>1,'response_number'=>'POR-2026-0001','supplier_id'=>1,'purchase_order_id'=>1,'account_id'=>1,'response_type'=>'accept',
        'proposed_delivery_date'=>date('Y-m-d',strtotime('+4 days')),'proposed_total_amount'=>94800.00,'currency_code'=>'USD',
        'notes'=>'Order accepted. Remaining battery quantity is scheduled for the confirmed delivery date.',
        'evidence_reference'=>'CONFIRM-CSCP-10428','status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'',
        'created_at'=>date('Y-m-d H:i:s',strtotime('-2 hours')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-2 hours')),
    ]];
}

function supplier_portal_demo_asns(): array
{
    return [[
        'id'=>1,'asn_number'=>'ASN-CSCP-2026-071','supplier_id'=>1,'purchase_order_id'=>1,'account_id'=>1,
        'ship_date'=>date('Y-m-d',strtotime('-1 day')),'estimated_arrival'=>date('Y-m-d',strtotime('+2 days')),
        'carrier'=>'Southwest Freight','tracking_number'=>'SWF-884201','package_count'=>20,'pallet_count'=>5,
        'packing_slip_reference'=>'PS-10428-A','status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'',
        'created_at'=>date('Y-m-d H:i:s',strtotime('-90 minutes')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-90 minutes')),
    ]];
}

function supplier_portal_demo_asn_lines(): array
{
    return [[
        'id'=>1,'shipment_notice_id'=>1,'purchase_order_line_id'=>1,'quantity_shipped'=>250.0,'lot_or_serial_reference'=>'LOT-BAT-26-071',
        'package_reference'=>'PALLETS-1-5','notes'=>'First partial shipment.','created_at'=>date('Y-m-d H:i:s',strtotime('-90 minutes')),
    ]];
}

function supplier_portal_demo_invoice_submissions(): array
{
    return [[
        'id'=>1,'submission_number'=>'INV-SUB-2026-0001','supplier_id'=>1,'purchase_order_id'=>1,'account_id'=>1,'invoice_number'=>'CSCP-77841',
        'invoice_date'=>date('Y-m-d',strtotime('-1 day')),'due_date'=>date('Y-m-d',strtotime('+29 days')),'currency_code'=>'USD',
        'subtotal'=>47400.00,'freight_amount'=>675.00,'tax_amount'=>0.00,'total_amount'=>48075.00,'document_reference'=>'DOC-CSCP-77841',
        'status'=>'submitted','duplicate_flag'=>0,'reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'',
        'canonical_invoice_id'=>null,'created_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes')),
    ]];
}

function supplier_portal_demo_invoice_lines(): array
{
    return [[
        'id'=>1,'invoice_submission_id'=>1,'purchase_order_line_id'=>1,'description'=>'12V 100Ah sealed UPS battery','quantity_invoiced'=>250.0,
        'unit_price'=>189.60,'tax_amount'=>0.00,'freight_amount'=>675.00,'line_total'=>47400.00,'created_at'=>date('Y-m-d H:i:s',strtotime('-45 minutes')),
    ]];
}

function supplier_portal_demo_documents(): array
{
    return [[
        'id'=>1,'supplier_id'=>1,'account_id'=>1,'entity_type'=>'supplier','entity_id'=>1,'document_type'=>'insurance_certificate',
        'title'=>'2026 Commercial Liability Certificate','document_reference'=>'COI-CSCP-2026','effective_date'=>'2026-01-01','expiration_date'=>'2026-12-31',
        'status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'','created_at'=>date('Y-m-d H:i:s',strtotime('-3 hours')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-3 hours')),
    ]];
}

function supplier_portal_demo_sourcing_submissions(): array
{
    return [[
        'id'=>1,'submission_number'=>'BID-2026-0001','supplier_id'=>1,'account_id'=>1,'sourcing_reference'=>'BATTERY-2027-RFQ',
        'title'=>'2027 battery supply proposal','bid_deadline'=>date('Y-m-d H:i:s',strtotime('+10 days')),'currency_code'=>'USD',
        'proposal_value'=>910000.00,'lead_time_days'=>14,'payment_terms'=>'Net 45','freight_terms'=>'FOB destination',
        'exceptions_and_assumptions'=>'Pricing assumes forecast releases by quarter and returnable core credits.',
        'document_reference'=>'BID-CSCP-2027-01','revision_number'=>1,'status'=>'submitted','locked_at'=>date('Y-m-d H:i:s',strtotime('-4 hours')),
        'reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'','created_at'=>date('Y-m-d H:i:s',strtotime('-4 hours')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-4 hours')),
    ]];
}

function supplier_portal_demo_quality_responses(): array
{
    return [[
        'id'=>1,'response_number'=>'QAR-2026-0001','supplier_id'=>1,'account_id'=>1,'quality_reference'=>'QE-GPS-2026-004',
        'response_type'=>'containment','containment_response'=>'Affected lots were isolated and shipment inspection increased to 100%.',
        'root_cause'=>'Packaging compression during consolidated freight handling.','corrective_action'=>'Revised pallet pattern and added corner protection.',
        'preventive_action'=>'Quarterly packaging audit and carrier handling review.','target_date'=>date('Y-m-d',strtotime('+14 days')),
        'evidence_reference'=>'CAPA-CSCP-004','status'=>'submitted','reviewed_by'=>null,'reviewed_at'=>null,'review_note'=>'',
        'created_at'=>date('Y-m-d H:i:s',strtotime('-5 hours')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-5 hours')),
    ]];
}

function supplier_portal_demo_messages(): array
{
    return [[
        'id'=>1,'supplier_id'=>1,'account_id'=>1,'company_id'=>2,'entity_type'=>'purchase_order','entity_id'=>1,'direction'=>'internal_to_supplier',
        'subject'=>'GPS-10428 delivery confirmation required','message_body'=>'Confirm the revised delivery date and provide the partial-shipment tracking reference.',
        'supplier_visible'=>1,'required_response_date'=>date('Y-m-d',strtotime('+2 days')),'status'=>'open','read_at'=>null,'acknowledged_at'=>null,
        'created_by_internal'=>3,'created_at'=>date('Y-m-d H:i:s',strtotime('-6 hours')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-6 hours')),
    ]];
}

function supplier_portal_demo_events(): array
{
    return [[
        'id'=>1,'supplier_id'=>1,'account_id'=>1,'company_id'=>2,'entity_type'=>'purchase_order_response','entity_id'=>1,
        'event_type'=>'supplier_response_submitted','from_status'=>'draft','to_status'=>'submitted','severity'=>'medium',
        'evidence_note'=>'Supplier accepted GPS-10428 and proposed a confirmed delivery date.','actor_type'=>'supplier','actor_internal_user_id'=>null,
        'actor_portal_account_id'=>1,'ip_address'=>'127.0.0.1','created_at'=>date('Y-m-d H:i:s',strtotime('-2 hours')),
    ]];
}
