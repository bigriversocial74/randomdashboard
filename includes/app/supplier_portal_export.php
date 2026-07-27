<?php
declare(strict_types=1);

function supplier_portal_csv_cell(mixed $value): string
{
    $text=(string)$value;if(preg_match('/^[=+\-@]/',$text))$text="'".$text;return $text;
}

function supplier_portal_export_csv(): never
{
    require_permission('supplier_portal.export');
    $rows=[];
    foreach(supplier_portal_po_responses() as $r)$rows[]=['PO response',$r['response_number'],$r['supplier_id'],$r['purchase_order_id'],$r['status'],$r['notes'],$r['created_at']];
    foreach(supplier_portal_asns() as $r)$rows[]=['ASN',$r['asn_number'],$r['supplier_id'],$r['purchase_order_id'],$r['status'],$r['tracking_number'],$r['created_at']];
    foreach(supplier_portal_invoice_submissions() as $r)$rows[]=['Invoice',$r['invoice_number'],$r['supplier_id'],$r['purchase_order_id'],$r['status'],number_format((float)$r['total_amount'],2,'.',''),$r['created_at']];
    foreach(supplier_portal_documents() as $r)$rows[]=['Document',$r['title'],$r['supplier_id'],$r['entity_id'],$r['status'],$r['document_reference'],$r['created_at']];
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="supplier-portal-governance-'.date('Ymd-His').'.csv"');
    $out=fopen('php://output','wb');fputcsv($out,['Record type','Reference','Supplier ID','Entity ID','Status','Evidence','Created']);
    foreach($rows as $row)fputcsv($out,array_map('supplier_portal_csv_cell',$row));fclose($out);exit;
}
