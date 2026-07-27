<?php
declare(strict_types=1);

function inventory_ops_demo_policies(): array
{
    return [
        ['id'=>1,'policy_number'=>'POL-INV-0001','company_id'=>2,'inventory_location_id'=>2,'item_id'=>1,'minimum_quantity'=>120,'maximum_quantity'=>650,'reorder_point'=>220,'safety_stock'=>100,'lead_time_days'=>14,'review_frequency_days'=>7,'preferred_supplier_id'=>1,'owner_id'=>3,'reviewer_id'=>6,'status'=>'active','evidence_note'=>'UPS battery policy based on service demand, supplier lead time, and critical-response coverage.','created_at'=>'2026-07-01 09:00:00','updated_at'=>'2026-07-25 09:00:00'],
        ['id'=>2,'policy_number'=>'POL-INV-0002','company_id'=>6,'inventory_location_id'=>6,'item_id'=>5,'minimum_quantity'=>70,'maximum_quantity'=>260,'reorder_point'=>120,'safety_stock'=>50,'lead_time_days'=>10,'review_frequency_days'=>14,'preferred_supplier_id'=>5,'owner_id'=>4,'reviewer_id'=>6,'status'=>'active','evidence_note'=>'Facility filter policy combines six-company property usage and seasonal replacement history.','created_at'=>'2026-07-05 10:00:00','updated_at'=>'2026-07-24 10:00:00'],
    ];
}

function inventory_ops_demo_recommendations(): array
{
    return [
        ['id'=>1,'recommendation_number'=>'REC-INV-0001','policy_id'=>1,'company_id'=>2,'inventory_location_id'=>2,'item_id'=>1,'available_quantity'=>42,'allocated_quantity'=>140,'open_po_quantity'=>500,'forecast_quantity'=>180,'safety_stock_quantity'=>100,'recommended_quantity'=>0,'estimated_unit_cost'=>189.50,'estimated_value'=>0,'recommendation_type'=>'hold','source_inventory_location_id'=>null,'preferred_supplier_id'=>1,'required_date'=>'2026-08-10','status'=>'approved','approval_id'=>null,'converted_request_id'=>null,'converted_transfer_id'=>null,'evidence_note'=>'Open PO coverage exceeds forecast and safety-stock requirement; monitor the past-due delivery before adding demand.','created_by'=>3,'created_at'=>'2026-07-25 10:00:00','updated_at'=>'2026-07-25 10:00:00'],
        ['id'=>2,'recommendation_number'=>'REC-INV-0002','policy_id'=>2,'company_id'=>6,'inventory_location_id'=>6,'item_id'=>5,'available_quantity'=>94,'allocated_quantity'=>24,'open_po_quantity'=>300,'forecast_quantity'=>210,'safety_stock_quantity'=>50,'recommended_quantity'=>0,'estimated_unit_cost'=>22.90,'estimated_value'=>0,'recommendation_type'=>'hold','source_inventory_location_id'=>null,'preferred_supplier_id'=>5,'required_date'=>'2026-08-20','status'=>'draft','approval_id'=>null,'converted_request_id'=>null,'converted_transfer_id'=>null,'evidence_note'=>'Existing purchase order covers the projected requirement; no additional replenishment is supported.','created_by'=>4,'created_at'=>'2026-07-24 11:00:00','updated_at'=>'2026-07-24 11:00:00'],
    ];
}

function inventory_ops_demo_reservations(): array
{
    return [
        ['id'=>1,'reservation_number'=>'RSV-INV-0001','company_id'=>2,'inventory_location_id'=>2,'item_id'=>6,'project_workorder_id'=>null,'reference_type'=>'service_commitment','reference_id'=>10439,'quantity'=>40,'status'=>'active','needed_date'=>'2026-08-02','owner_id'=>3,'evidence_note'=>'Reserved for scheduled critical-power preventive-maintenance kits.','created_at'=>'2026-07-25 12:00:00','updated_at'=>'2026-07-25 12:00:00'],
    ];
}

function inventory_ops_demo_transfers(): array
{
    return [
        ['id'=>1,'transfer_number'=>'TRF-INV-0001','source_company_id'=>1,'destination_company_id'=>3,'source_inventory_location_id'=>1,'destination_inventory_location_id'=>3,'project_workorder_id'=>null,'status'=>'submitted','required_date'=>'2026-08-03','shipped_at'=>null,'received_at'=>null,'carrier'=>'Internal fleet','tracking_number'=>'','freight_cost'=>185,'owner_id'=>5,'reviewer_id'=>3,'approval_id'=>null,'evidence_note'=>'Transfer cable stock from GC to GTS before authorizing an additional field purchase.','created_at'=>'2026-07-25 13:00:00','updated_at'=>'2026-07-25 13:00:00'],
    ];
}

function inventory_ops_demo_transfer_lines(): array
{
    return [
        ['id'=>1,'transfer_request_id'=>1,'item_id'=>8,'quantity_requested'=>600,'quantity_shipped'=>0,'quantity_received'=>0,'unit_cost'=>1.82,'serial_or_lot_reference'=>'LOT-OS2-0726','condition_status'=>'pending','notes'=>'Validate spool lengths and project-site custody before dispatch.','created_at'=>'2026-07-25 13:05:00','updated_at'=>'2026-07-25 13:05:00'],
    ];
}

function inventory_ops_demo_transfer_events(): array
{
    return [
        ['id'=>1,'transfer_request_id'=>1,'event_type'=>'transfer_created','from_status'=>null,'to_status'=>'draft','severity'=>'medium','evidence_note'=>'Internal transfer request created from the cross-company inventory opportunity.','created_by'=>5,'created_at'=>'2026-07-25 13:00:00'],
        ['id'=>2,'transfer_request_id'=>1,'event_type'=>'submitted','from_status'=>'draft','to_status'=>'submitted','severity'=>'medium','evidence_note'=>'Transfer submitted for source, destination, custody, freight, and schedule review.','created_by'=>5,'created_at'=>'2026-07-25 13:10:00'],
    ];
}

function inventory_ops_demo_events(): array
{
    return [
        ['id'=>1,'company_id'=>2,'inventory_location_id'=>2,'item_id'=>1,'entity_type'=>'replenishment_recommendation','entity_id'=>1,'event_type'=>'recommendation_generated','from_status'=>null,'to_status'=>'approved','severity'=>'medium','quantity'=>0,'value_amount'=>0,'evidence_note'=>'Open-order coverage prevented unsupported duplicate replenishment.','created_by'=>3,'created_at'=>'2026-07-25 10:00:00'],
        ['id'=>2,'company_id'=>2,'inventory_location_id'=>2,'item_id'=>6,'entity_type'=>'inventory_reservation','entity_id'=>1,'event_type'=>'reserved','from_status'=>null,'to_status'=>'active','severity'=>'medium','quantity'=>40,'value_amount'=>746,'evidence_note'=>'Service-kit inventory reserved for scheduled work.','created_by'=>3,'created_at'=>'2026-07-25 12:00:00'],
    ];
}

function inventory_ops_demo_cycle_counts(): array
{
    return [
        ['id'=>1,'inventory_location_id'=>4,'count_reference'=>'CC-GMC-0726','status'=>'open','scheduled_date'=>'2026-07-28','completed_date'=>null,'assigned_to'=>4,'approved_by'=>null,'created_at'=>'2026-07-24 09:00:00','updated_at'=>'2026-07-24 09:00:00'],
    ];
}

function inventory_ops_demo_cycle_count_lines(): array
{
    return [
        ['id'=>1,'cycle_count_id'=>1,'item_id'=>4,'system_quantity'=>4,'counted_quantity'=>null,'variance_quantity'=>null,'variance_reason'=>'','created_at'=>'2026-07-24 09:05:00','updated_at'=>'2026-07-24 09:05:00'],
    ];
}

function inventory_ops_demo_transactions(): array
{
    return [
        ['id'=>1,'item_id'=>6,'from_inventory_location_id'=>null,'to_inventory_location_id'=>2,'project_workorder_id'=>null,'transaction_type'=>'reservation','quantity'=>40,'unit_cost'=>18.65,'reason_code'=>'service_commitment','reference_type'=>'inventory_reservation','reference_id'=>1,'notes'=>'Reserved for scheduled preventive maintenance.','performed_by'=>3,'performed_at'=>'2026-07-25 12:00:00'],
    ];
}
