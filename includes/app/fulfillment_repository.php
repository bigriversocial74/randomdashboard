<?php
declare(strict_types=1);

function fulfillment_profiles(): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['purchase_order_fulfillment_profiles']))$_SESSION['gruber_demo_state']['purchase_order_fulfillment_profiles']=fulfillment_demo_profiles();return array_values($_SESSION['gruber_demo_state']['purchase_order_fulfillment_profiles']);}
    if(!fulfillment_tables_ready())return [];$rows=production_database_connection()->query('SELECT * FROM purchase_order_fulfillment_profiles ORDER BY last_status_at DESC,id DESC')->fetchAll();return $rows;
}

function fulfillment_profile_for_po(int $poId): ?array { foreach(fulfillment_profiles() as $row)if((int)$row['purchase_order_id']===$poId)return $row;return null; }

function fulfillment_orders(): array
{
    if(data_is_demo()){
        $profiles=[];foreach(fulfillment_profiles() as $profile)$profiles[(int)$profile['purchase_order_id']]=$profile;
        $rows=[];foreach(data_visible_collection('purchase_orders') as $po)$rows[]=fulfillment_normalize_po(array_replace($po,$profiles[(int)$po['id']]??[]));
    }else{
        $pdo=production_database_connection();if(!$pdo)return [];$ready=fulfillment_tables_ready();$join=$ready?' LEFT JOIN purchase_order_fulfillment_profiles fp ON fp.purchase_order_id=po.id':'';$select=$ready?',fp.owner_id,fp.reviewer_id,fp.shipment_status,fp.asn_number,fp.carrier,fp.tracking_number,fp.shipment_reference,fp.fulfillment_evidence,fp.last_status_at':'';$where=current_company_id()==='enterprise'?'':' WHERE po.company_id=?';$stmt=$pdo->prepare('SELECT po.*'.$select.' FROM purchase_orders po'.$join.$where.' ORDER BY po.expected_date,po.id');$stmt->execute(current_company_id()==='enterprise'?[]:[(int)current_company_id()]);$rows=array_map('fulfillment_normalize_po',$stmt->fetchAll());
    }
    usort($rows,static function(array $a,array $b):int{$rank=['past_due'=>0,'partially_received'=>1,'open'=>2,'pending_approval'=>3,'received'=>4,'closed'=>5,'draft'=>6,'canceled'=>7];return ($rank[$a['status']]??9)<=>($rank[$b['status']]??9)?:strcmp((string)($a['expected_date']??'9999-12-31'),(string)($b['expected_date']??'9999-12-31'));});return $rows;
}

function fulfillment_find_order(int $id): ?array { foreach(fulfillment_orders() as $row)if((int)$row['id']===$id)return $row;return null; }

function fulfillment_default_order(): ?array { foreach(fulfillment_orders() as $row)if(in_array((string)$row['status'],['past_due','partially_received','open'],true))return $row;return fulfillment_orders()[0]??null; }

function fulfillment_po_lines(int $poId): array
{
    $rows=[];foreach(data_collection('purchase_order_lines') as $line){if((int)($line['purchase_order_id']??0)!==$poId)continue;$line['id']=(int)$line['id'];$line['item_id']=isset($line['item_id'])?(int)$line['item_id']:null;$line['quantity_ordered']=(float)($line['quantity_ordered']??$line['quantity']??0);$line['quantity_received']=(float)($line['quantity_received']??0);$line['unit_cost']=(float)($line['unit_cost']??0);$line['contract_cost_at_order']=isset($line['contract_cost_at_order'])&&$line['contract_cost_at_order']!==null?(float)$line['contract_cost_at_order']:null;$line['line_total']=(float)($line['line_total']??round($line['quantity_ordered']*$line['unit_cost'],2));$line['unit_of_measure']=(string)($line['unit_of_measure']??'EA');$rows[]=$line;}return $rows;
}

function fulfillment_find_po_line(int $id): ?array { foreach(fulfillment_orders() as $po)foreach(fulfillment_po_lines((int)$po['id']) as $line)if((int)$line['id']===$id)return $line;return null; }

function fulfillment_receipts(int $poId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['receipts']))$_SESSION['gruber_demo_state']['receipts']=fulfillment_demo_receipts();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['receipts'],static fn(array $row):bool=>(int)$row['purchase_order_id']===$poId));}
    else{$pdo=production_database_connection();if(!$pdo)return [];$stmt=$pdo->prepare('SELECT * FROM receipts WHERE purchase_order_id=? ORDER BY received_at DESC,id DESC');$stmt->execute([$poId]);$rows=$stmt->fetchAll();}
    usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['received_at'],(string)$a['received_at']));return $rows;
}

function fulfillment_find_receipt(int $id): ?array { foreach(fulfillment_orders() as $po)foreach(fulfillment_receipts((int)$po['id']) as $row)if((int)$row['id']===$id)return $row;return null; }

function fulfillment_receipt_lines(int $receiptId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['receipt_lines']))$_SESSION['gruber_demo_state']['receipt_lines']=fulfillment_demo_receipt_lines();return array_values(array_filter($_SESSION['gruber_demo_state']['receipt_lines'],static fn(array $row):bool=>(int)$row['receipt_id']===$receiptId));}
    $pdo=production_database_connection();if(!$pdo)return [];$stmt=$pdo->prepare('SELECT * FROM receipt_lines WHERE receipt_id=? ORDER BY id');$stmt->execute([$receiptId]);return $stmt->fetchAll();
}

function fulfillment_receipt_lines_for_po(int $poId): array { $rows=[];foreach(fulfillment_receipts($poId) as $receipt)foreach(fulfillment_receipt_lines((int)$receipt['id']) as $line){$line['receipt_number']=$receipt['receipt_number'];$line['received_at']=$receipt['received_at'];$rows[]=$line;}return $rows; }

function fulfillment_invoices(?int $poId=null): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_invoices']))$_SESSION['gruber_demo_state']['supplier_invoices']=fulfillment_demo_invoices();$rows=array_values($_SESSION['gruber_demo_state']['supplier_invoices']);$rows=array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));}
    else{if(!fulfillment_tables_ready())return [];$pdo=production_database_connection();$where=[];$params=[];if(current_company_id()!=='enterprise'){$where[]='company_id=?';$params[]=(int)current_company_id();}if($poId){$where[]='purchase_order_id=?';$params[]=$poId;}$stmt=$pdo->prepare('SELECT * FROM supplier_invoices'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY invoice_date DESC,id DESC');$stmt->execute($params);$rows=$stmt->fetchAll();}
    if($poId)$rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['purchase_order_id']===$poId));foreach($rows as &$row){$row['id']=(int)$row['id'];$row['company_id']=(int)$row['company_id'];$row['supplier_id']=(int)$row['supplier_id'];$row['purchase_order_id']=(int)$row['purchase_order_id'];foreach(['subtotal','freight_amount','tax_amount','total_amount'] as $f)$row[$f]=(float)($row[$f]??0);$row['owner_id']=(int)($row['owner_id']??1);$row['reviewer_id']=(int)($row['reviewer_id']??6);$row['approval_id']=isset($row['approval_id'])&&$row['approval_id']!==null?(int)$row['approval_id']:null;}unset($row);return $rows;
}

function fulfillment_find_invoice(int $id): ?array { foreach(fulfillment_invoices() as $row)if((int)$row['id']===$id)return $row;return null; }

function fulfillment_default_invoice(int $poId): ?array { $rows=fulfillment_invoices($poId);foreach($rows as $row)if(in_array((string)$row['status'],['exception','on_hold','received','matching'],true))return $row;return $rows[0]??null; }

function fulfillment_invoice_lines(int $invoiceId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['supplier_invoice_lines']))$_SESSION['gruber_demo_state']['supplier_invoice_lines']=fulfillment_demo_invoice_lines();return array_values(array_filter($_SESSION['gruber_demo_state']['supplier_invoice_lines'],static fn(array $row):bool=>(int)$row['invoice_id']===$invoiceId));}
    if(!fulfillment_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM supplier_invoice_lines WHERE invoice_id=? ORDER BY id');$stmt->execute([$invoiceId]);return $stmt->fetchAll();
}

function fulfillment_find_invoice_line(int $id): ?array { foreach(fulfillment_invoices() as $invoice)foreach(fulfillment_invoice_lines((int)$invoice['id']) as $row)if((int)$row['id']===$id)return $row;return null; }

function fulfillment_matches(int $invoiceId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_match_results']))$_SESSION['gruber_demo_state']['procurement_match_results']=fulfillment_demo_matches();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['procurement_match_results'],static fn(array $row):bool=>(int)$row['invoice_id']===$invoiceId));}
    else{if(!fulfillment_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM procurement_match_results WHERE invoice_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$invoiceId]);$rows=$stmt->fetchAll();}return $rows;
}

function fulfillment_find_match(int $id): ?array { foreach(fulfillment_invoices() as $invoice)foreach(fulfillment_matches((int)$invoice['id']) as $row)if((int)$row['id']===$id)return $row;return null; }

function fulfillment_exceptions(int $invoiceId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_invoice_exceptions']))$_SESSION['gruber_demo_state']['procurement_invoice_exceptions']=fulfillment_demo_exceptions();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['procurement_invoice_exceptions'],static fn(array $row):bool=>(int)$row['invoice_id']===$invoiceId));}
    else{if(!fulfillment_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM procurement_invoice_exceptions WHERE invoice_id=? ORDER BY updated_at DESC,id DESC');$stmt->execute([$invoiceId]);$rows=$stmt->fetchAll();}return $rows;
}

function fulfillment_find_exception(int $id): ?array { foreach(fulfillment_invoices() as $invoice)foreach(fulfillment_exceptions((int)$invoice['id']) as $row)if((int)$row['id']===$id)return $row;return null; }

function fulfillment_events(int $poId): array
{
    if(data_is_demo()){if(!isset($_SESSION['gruber_demo_state']['procurement_fulfillment_events']))$_SESSION['gruber_demo_state']['procurement_fulfillment_events']=fulfillment_demo_events();$rows=array_values(array_filter($_SESSION['gruber_demo_state']['procurement_fulfillment_events'],static fn(array $row):bool=>(int)$row['purchase_order_id']===$poId));}
    else{if(!fulfillment_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM procurement_fulfillment_events WHERE purchase_order_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$poId]);$rows=$stmt->fetchAll();}usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;
}

function fulfillment_received_quantities(int $poId): array
{
    $out=[];foreach(fulfillment_receipt_lines_for_po($poId) as $line){$lineId=(int)$line['purchase_order_line_id'];if(!isset($out[$lineId]))$out[$lineId]=['received'=>0.0,'accepted'=>0.0,'rejected'=>0.0,'held'=>0.0];$out[$lineId]['received']+=(float)$line['quantity_received'];$out[$lineId]['accepted']+=(float)$line['quantity_accepted'];$out[$lineId]['rejected']+=(float)$line['quantity_rejected'];if(in_array((string)$line['condition_status'],['quality_hold','damaged','incorrect','returned'],true))$out[$lineId]['held']+=(float)$line['quantity_rejected'];}return $out;
}

function fulfillment_invoice_effective_status(array $invoice): string
{
    $stored=(string)($invoice['status']??'draft');if(in_array($stored,['approved_for_payment','paid','void'],true))return $stored;$approvalId=(int)($invoice['approval_id']??0);if($approvalId>0){$approval=data_find('workflow_approvals',$approvalId);if($approval&&in_array((string)($approval['status']??''),['pending','in_review','approved','changes_requested','rejected'],true))return (string)$approval['status'];}return $stored;
}

function fulfillment_invoice_duplicate(array $invoice): bool
{
    $fingerprint=(string)($invoice['duplicate_fingerprint']??hash('sha256',(int)$invoice['supplier_id'].'|'.strtoupper((string)$invoice['invoice_number']).'|'.number_format((float)$invoice['total_amount'],2,'.','')));foreach(fulfillment_invoices() as $other){if((int)$other['id']===(int)($invoice['id']??0))continue;if((string)($other['duplicate_fingerprint']??'')===$fingerprint||((int)$other['supplier_id']===(int)$invoice['supplier_id']&&strtoupper((string)$other['invoice_number'])===strtoupper((string)$invoice['invoice_number'])&&(string)$other['status']!=='void'))return true;}return false;
}
