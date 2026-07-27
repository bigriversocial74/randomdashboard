<?php
declare(strict_types=1);

function fulfillment_next_number(string $prefix,string $sessionKey,string $table,array $seed): string
{
    if(data_is_demo())$count=count($_SESSION['gruber_demo_state'][$sessionKey]??$seed);else{$count=fulfillment_tables_ready()?(int)production_database_connection()->query('SELECT COUNT(*) FROM '.$table)->fetchColumn():0;}return $prefix.'-'.date('Y').'-'.str_pad((string)($count+1),4,'0',STR_PAD_LEFT);
}

function fulfillment_save_profile(array $record): array
{
    $record['last_status_at']=date('Y-m-d H:i:s');if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['purchase_order_fulfillment_profiles']??fulfillment_demo_profiles();$id=(int)($record['id']??0);if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;}$found=false;foreach($rows as $i=>$row)if((int)$row['purchase_order_id']===(int)$record['purchase_order_id']){$record['id']=$row['id'];$rows[$i]=array_replace($row,$record);$record=$rows[$i];$found=true;break;}if(!$found)$rows[]=$record;$_SESSION['gruber_demo_state']['purchase_order_fulfillment_profiles']=$rows;return $record;}
    if(!fulfillment_tables_ready())throw new RuntimeException('Import the Section 18 migration before saving Production Data fulfillment governance.');$pdo=production_database_connection();$pdo->prepare('INSERT INTO purchase_order_fulfillment_profiles (purchase_order_id,owner_id,reviewer_id,shipment_status,asn_number,carrier,tracking_number,shipment_reference,fulfillment_evidence,last_status_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE owner_id=VALUES(owner_id),reviewer_id=VALUES(reviewer_id),shipment_status=VALUES(shipment_status),asn_number=VALUES(asn_number),carrier=VALUES(carrier),tracking_number=VALUES(tracking_number),shipment_reference=VALUES(shipment_reference),fulfillment_evidence=VALUES(fulfillment_evidence),last_status_at=NOW(),updated_at=NOW()')->execute([$record['purchase_order_id'],$record['owner_id'],$record['reviewer_id'],$record['shipment_status'],$record['asn_number'],$record['carrier'],$record['tracking_number'],$record['shipment_reference'],$record['fulfillment_evidence']]);return fulfillment_profile_for_po((int)$record['purchase_order_id'])??$record;
}

function fulfillment_save_receipt(array $record): array
{
    $record['received_at']=$record['received_at']??date('Y-m-d H:i:s');$record['receipt_number']=$record['receipt_number']??fulfillment_next_number('RCV','receipts','receipts',fulfillment_demo_receipts());if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['receipts']??fulfillment_demo_receipts();$record['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$record;$_SESSION['gruber_demo_state']['receipts']=$rows;return $record;}$pdo=production_database_connection();if(!$pdo)throw new RuntimeException('Production database unavailable.');$pdo->prepare('INSERT INTO receipts (receipt_number,purchase_order_id,location_id,received_by,received_at,packing_slip_number,carrier,tracking_number,notes) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$record['receipt_number'],$record['purchase_order_id'],$record['location_id'],$record['received_by'],$record['received_at'],$record['packing_slip_number'],$record['carrier'],$record['tracking_number'],$record['notes']]);$record['id']=(int)$pdo->lastInsertId();return $record;
}

function fulfillment_save_receipt_line(array $record): array
{
    $record['created_at']=$record['created_at']??date('Y-m-d H:i:s');if(data_is_demo()){$rows=$_SESSION['gruber_demo_state']['receipt_lines']??fulfillment_demo_receipt_lines();$record['id']=max([0,...array_map('intval',array_column($rows,'id'))])+1;$rows[]=$record;$_SESSION['gruber_demo_state']['receipt_lines']=$rows;return $record;}$pdo=production_database_connection();$pdo->prepare('INSERT INTO receipt_lines (receipt_id,purchase_order_line_id,quantity_received,quantity_accepted,quantity_rejected,condition_status,serial_or_lot_reference,notes) VALUES (?,?,?,?,?,?,?,?)')->execute([$record['receipt_id'],$record['purchase_order_line_id'],$record['quantity_received'],$record['quantity_accepted'],$record['quantity_rejected'],$record['condition_status'],$record['serial_or_lot_reference'],$record['notes']]);$record['id']=(int)$pdo->lastInsertId();return $record;
}
