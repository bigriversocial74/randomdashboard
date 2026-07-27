<?php
declare(strict_types=1);

function fulfillment_invoice_statuses(): array { return ['draft','received','matching','exception','on_hold','approved_for_payment','paid','void']; }

function fulfillment_exception_statuses(): array { return ['open','investigating','supplier_action','pending_approval','waived','resolved','rejected']; }

function fulfillment_conditions(): array { return ['accepted','damaged','incorrect','quality_hold','returned']; }

function fulfillment_shipment_statuses(): array { return ['not_acknowledged','acknowledged','scheduled','in_transit','partially_received','received','delayed','canceled']; }

function fulfillment_match_statuses(): array { return ['matched','within_tolerance','exception','blocked']; }

function fulfillment_event_types(): array { return ['fulfillment_updated','supplier_acknowledged','shipment_updated','receipt_created','receipt_posted','quality_exception','invoice_created','invoice_updated','invoice_line_created','match_completed','match_exception','exception_created','exception_updated','payment_release_requested','payment_released','invoice_paid','invoice_voided']; }

function fulfillment_default_company_id(?array $user=null): int
{
    $user ??= current_user();
    $permitted=permitted_company_ids($user);
    if(current_company_id()!=='enterprise')return (int)current_company_id();
    $primary=(int)($user['primary_company_id']??0);
    return in_array($primary,$permitted,true)?$primary:(int)($permitted[0]??0);
}

function fulfillment_tables_ready(): bool
{
    if(data_is_demo())return true;
    $pdo=production_database_connection();if(!$pdo)return false;
    try{
        foreach(['purchase_order_fulfillment_profiles','supplier_invoices','supplier_invoice_lines','procurement_match_results','procurement_invoice_exceptions','procurement_fulfillment_events'] as $table)$pdo->query("SELECT id FROM {$table} LIMIT 1");
        return true;
    }catch(Throwable){return false;}
}

function fulfillment_normalize_po(array $row): array
{
    $row['id']=(int)($row['id']??0);$row['company_id']=(int)($row['company_id']??0);$row['supplier_id']=(int)($row['supplier_id']??0);
    $row['buyer_id']=(int)($row['buyer_id']??$row['buyer_user_id']??1);$row['location_id']=isset($row['location_id'])&&$row['location_id']!==null?(int)$row['location_id']:null;$row['department_id']=isset($row['department_id'])&&$row['department_id']!==null?(int)$row['department_id']:null;$row['purchase_request_id']=isset($row['purchase_request_id'])&&$row['purchase_request_id']!==null?(int)$row['purchase_request_id']:null;$row['project_workorder_id']=isset($row['project_workorder_id'])&&$row['project_workorder_id']!==null?(int)$row['project_workorder_id']:null;
    foreach(['subtotal','freight_amount','tax_amount','total_amount'] as $field)$row[$field]=(float)($row[$field]??($field==='subtotal'?($row['total_amount']??0):0));
    $row['currency_code']=(string)($row['currency_code']??'USD');$row['business_purpose']=(string)($row['business_purpose']??'inventory_replenishment');$row['supplier_acknowledged_at']=$row['supplier_acknowledged_at']??null;
    $row['owner_id']=(int)($row['owner_id']??$row['buyer_id']??1);$row['reviewer_id']=(int)($row['reviewer_id']??6);$row['shipment_status']=(string)($row['shipment_status']??(!empty($row['supplier_acknowledged_at'])?'acknowledged':'not_acknowledged'));
    $row['asn_number']=(string)($row['asn_number']??'');$row['carrier']=(string)($row['carrier']??'');$row['tracking_number']=(string)($row['tracking_number']??'');$row['shipment_reference']=(string)($row['shipment_reference']??'');$row['fulfillment_evidence']=(string)($row['fulfillment_evidence']??'Confirm supplier acknowledgment, shipment evidence, receipt condition, invoice match, and payment-release controls.');
    $row['last_status_at']=(string)($row['last_status_at']??$row['updated_at']??date('Y-m-d H:i:s'));
    return $row;
}
