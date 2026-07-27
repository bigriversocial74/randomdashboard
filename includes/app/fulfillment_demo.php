<?php
declare(strict_types=1);

function fulfillment_demo_profiles(): array
{
    return [
        ['id'=>1,'purchase_order_id'=>1,'owner_id'=>3,'reviewer_id'=>6,'shipment_status'=>'delayed','asn_number'=>'ASN-CSCP-10428','carrier'=>'Old Dominion Freight Line','tracking_number'=>'ODFL-7781426','shipment_reference'=>'Battery shipment delayed at regional cross-dock.','fulfillment_evidence'=>'Supplier acknowledged the delay; revised delivery and customer-impact evidence are required.','last_status_at'=>'2026-07-25 09:30:00'],
        ['id'=>2,'purchase_order_id'=>5,'owner_id'=>5,'reviewer_id'=>6,'shipment_status'=>'partially_received','asn_number'=>'ASN-SIS-01103','carrier'=>'UPS Freight','tracking_number'=>'1Z-DEMO-01103','shipment_reference'=>'First shipment received; battery modules remain outstanding.','fulfillment_evidence'=>'Partial receipt accepted with one lot held for quality review.','last_status_at'=>'2026-07-25 14:10:00'],
    ];
}

function fulfillment_demo_receipts(): array
{
    return [
        ['id'=>1,'receipt_number'=>'RCV-2026-0001','purchase_order_id'=>5,'location_id'=>5,'received_by'=>5,'received_at'=>'2026-07-25 13:40:00','packing_slip_number'=>'PS-01103-A','carrier'=>'UPS Freight','tracking_number'=>'1Z-DEMO-01103','notes'=>'Partial shipment received and inspected.'],
        ['id'=>2,'receipt_number'=>'RCV-2026-0002','purchase_order_id'=>1,'location_id'=>2,'received_by'=>3,'received_at'=>'2026-07-25 16:20:00','packing_slip_number'=>'PS-10428-A','carrier'=>'Old Dominion Freight Line','tracking_number'=>'ODFL-7781426','notes'=>'Expedited partial battery delivery.'],
    ];
}

function fulfillment_demo_receipt_lines(): array
{
    return [
        ['id'=>1,'receipt_id'=>1,'purchase_order_line_id'=>7,'quantity_received'=>180.0,'quantity_accepted'=>175.0,'quantity_rejected'=>5.0,'condition_status'=>'quality_hold','serial_or_lot_reference'=>'LOT-BRG-0725','notes'=>'Five bearings held for seal inspection.','created_at'=>'2026-07-25 13:45:00'],
        ['id'=>2,'receipt_id'=>2,'purchase_order_line_id'=>1,'quantity_received'=>120.0,'quantity_accepted'=>120.0,'quantity_rejected'=>0.0,'condition_status'=>'accepted','serial_or_lot_reference'=>'LOT-BAT-10428-A','notes'=>'Accepted after voltage and visual inspection.','created_at'=>'2026-07-25 16:25:00'],
    ];
}

function fulfillment_demo_invoices(): array
{
    return [
        ['id'=>1,'invoice_number'=>'INV-SIS-7781','company_id'=>5,'supplier_id'=>6,'purchase_order_id'=>5,'contract_id'=>null,'invoice_date'=>'2026-07-25','due_date'=>'2026-08-24','payment_terms'=>'Net 30','currency_code'=>'USD','subtotal'=>5600.0,'freight_amount'=>180.0,'tax_amount'=>0.0,'total_amount'=>5780.0,'document_path'=>'evidence/invoices/inv-sis-7781.pdf','status'=>'exception','owner_id'=>5,'reviewer_id'=>6,'approval_id'=>null,'hold_reason'=>'Invoice quantity and price exceed accepted receipt evidence.','duplicate_fingerprint'=>hash('sha256','6|INV-SIS-7781|5780.00'),'released_at'=>null,'paid_at'=>null,'created_at'=>'2026-07-25 15:00:00','updated_at'=>'2026-07-25 15:20:00'],
        ['id'=>2,'invoice_number'=>'INV-CSCP-10428-A','company_id'=>2,'supplier_id'=>1,'purchase_order_id'=>1,'contract_id'=>1,'invoice_date'=>'2026-07-25','due_date'=>'2026-08-24','payment_terms'=>'Net 30','currency_code'=>'USD','subtotal'=>22752.0,'freight_amount'=>0.0,'tax_amount'=>0.0,'total_amount'=>22752.0,'document_path'=>'evidence/invoices/inv-cscp-10428-a.pdf','status'=>'approved_for_payment','owner_id'=>3,'reviewer_id'=>6,'approval_id'=>null,'hold_reason'=>'','duplicate_fingerprint'=>hash('sha256','1|INV-CSCP-10428-A|22752.00'),'released_at'=>'2026-07-25 17:00:00','paid_at'=>null,'created_at'=>'2026-07-25 16:40:00','updated_at'=>'2026-07-25 17:00:00'],
    ];
}

function fulfillment_demo_invoice_lines(): array
{
    return [
        ['id'=>1,'invoice_id'=>1,'purchase_order_line_id'=>7,'item_id'=>6,'description'=>'6205 sealed service bearing','quantity_invoiced'=>180.0,'unit_price'=>31.1111,'line_total'=>5600.0,'tax_amount'=>0.0,'freight_amount'=>0.0,'created_at'=>'2026-07-25 15:00:00','updated_at'=>'2026-07-25 15:00:00'],
        ['id'=>2,'invoice_id'=>2,'purchase_order_line_id'=>1,'item_id'=>1,'description'=>'12V 100Ah sealed UPS battery','quantity_invoiced'=>120.0,'unit_price'=>189.60,'line_total'=>22752.0,'tax_amount'=>0.0,'freight_amount'=>0.0,'created_at'=>'2026-07-25 16:40:00','updated_at'=>'2026-07-25 16:40:00'],
    ];
}

function fulfillment_demo_matches(): array
{
    return [
        ['id'=>1,'match_number'=>'MATCH-2026-0001','invoice_id'=>1,'purchase_order_id'=>5,'po_value'=>15780.0,'accepted_receipt_value'=>3255.0,'invoice_value'=>5780.0,'quantity_variance'=>155.56,'price_variance'=>2252.0,'freight_variance'=>180.0,'tax_variance'=>0.0,'contract_variance'=>2252.0,'unreceived_value'=>155.56,'held_rejected_value'=>93.0,'remaining_po_balance'=>15780.0,'duplicate_flag'=>false,'tolerance_amount'=>100.0,'tolerance_pct'=>2.0,'match_status'=>'exception','match_score'=>42.0,'evidence_note'=>'Invoice price and quantity exceed accepted receipt evidence.','created_by'=>1,'created_at'=>'2026-07-25 15:20:00'],
        ['id'=>2,'match_number'=>'MATCH-2026-0002','invoice_id'=>2,'purchase_order_id'=>1,'po_value'=>94800.0,'accepted_receipt_value'=>22752.0,'invoice_value'=>22752.0,'quantity_variance'=>0.0,'price_variance'=>0.0,'freight_variance'=>0.0,'tax_variance'=>0.0,'contract_variance'=>12.0,'unreceived_value'=>0.0,'held_rejected_value'=>0.0,'remaining_po_balance'=>94800.0,'duplicate_flag'=>false,'tolerance_amount'=>100.0,'tolerance_pct'=>2.0,'match_status'=>'within_tolerance','match_score'=>99.0,'evidence_note'=>'PO, accepted receipt, and invoice agree within configured tolerance.','created_by'=>1,'created_at'=>'2026-07-25 16:55:00'],
    ];
}

function fulfillment_demo_exceptions(): array
{
    return [
        ['id'=>1,'exception_number'=>'EXC-2026-0001','invoice_id'=>1,'match_result_id'=>1,'purchase_order_id'=>5,'exception_type'=>'price_variance','severity'=>'high','status'=>'supplier_action','owner_id'=>5,'reviewer_id'=>6,'due_date'=>'2026-07-29','disputed_amount'=>2252.0,'waiver_reason'=>'','resolution_note'=>'Supplier asked to issue a corrected invoice or credit memo.','supplier_credit_amount'=>0.0,'approval_id'=>null,'created_at'=>'2026-07-25 15:25:00','updated_at'=>'2026-07-25 15:25:00'],
        ['id'=>2,'exception_number'=>'EXC-2026-0002','invoice_id'=>1,'match_result_id'=>1,'purchase_order_id'=>5,'exception_type'=>'rejected_quality','severity'=>'medium','status'=>'investigating','owner_id'=>5,'reviewer_id'=>6,'due_date'=>'2026-07-30','disputed_amount'=>93.0,'waiver_reason'=>'','resolution_note'=>'Five units remain on quality hold.','supplier_credit_amount'=>0.0,'approval_id'=>null,'created_at'=>'2026-07-25 15:26:00','updated_at'=>'2026-07-25 15:26:00'],
    ];
}

function fulfillment_demo_events(): array
{
    return [
        ['id'=>1,'purchase_order_id'=>5,'invoice_id'=>1,'event_type'=>'match_exception','entity_type'=>'supplier_invoice','entity_id'=>1,'from_status'=>'matching','to_status'=>'exception','severity'=>'high','evidence_note'=>'Price, quantity, and quality-hold exceptions identified.','created_by'=>1,'created_at'=>'2026-07-25 15:20:00'],
        ['id'=>2,'purchase_order_id'=>1,'invoice_id'=>2,'event_type'=>'payment_released','entity_type'=>'supplier_invoice','entity_id'=>2,'from_status'=>'matching','to_status'=>'approved_for_payment','severity'=>'low','evidence_note'=>'Three-way match completed within tolerance.','created_by'=>1,'created_at'=>'2026-07-25 17:00:00'],
    ];
}
