<?php
declare(strict_types=1);

function inventory_ops_tables_ready(): bool
{
    if(data_is_demo())return true;
    $pdo=production_database_connection();if(!$pdo)return false;
    try{$names=['inventory_replenishment_policies','inventory_replenishment_recommendations','inventory_reservations','inventory_transfer_requests','inventory_transfer_lines','inventory_transfer_events','inventory_governance_events'];$placeholders=implode(',',array_fill(0,count($names),'?'));$stmt=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ({$placeholders})");$stmt->execute($names);return (int)$stmt->fetchColumn()===count($names);}catch(Throwable){return false;}
}

function inventory_ops_require_tables(): void
{
    if(!inventory_ops_tables_ready())throw new RuntimeException('Section 19 migration is required before Production Data inventory-governance writes can be used.');
}

function inventory_ops_demo_collection(string $key,callable $seed): array
{
    if(!isset($_SESSION['gruber_demo_state'][$key]))$_SESSION['gruber_demo_state'][$key]=$seed();
    return array_values($_SESSION['gruber_demo_state'][$key]);
}

function inventory_ops_demo_save(string $key,array $record,callable $seed): array
{
    $rows=inventory_ops_demo_collection($key,$seed);$id=(int)($record['id']??0);
    if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;$rows[]=$record;}
    else{$found=false;foreach($rows as $i=>$row){if((int)$row['id']===$id){$rows[$i]=array_replace($row,$record);$record=$rows[$i];$found=true;break;}}if(!$found)$rows[]=$record;}
    $_SESSION['gruber_demo_state'][$key]=array_values($rows);return $record;
}

function inventory_ops_positions(): array
{
    $locations=[];foreach(inventory_ops_locations() as $row)$locations[(int)$row['id']]=$row;
    $rows=[];foreach(data_visible_collection('inventory_snapshots') as $row){$locationId=(int)($row['inventory_location_id']??$row['location_id']??0);$location=$locations[$locationId]??null;$companyId=(int)($row['company_id']??$location['company_id']??0);$onHand=(float)($row['quantity_on_hand']??$row['quantity']??0);$allocated=(float)($row['quantity_allocated']??$row['allocated']??0);$available=(float)($row['available']??max(0,$onHand-$allocated));$unitCost=(float)($row['average_unit_cost']??$row['unit_cost']??0);$rows[]=['id'=>(int)($row['id']??0),'company_id'=>$companyId,'inventory_location_id'=>$locationId,'item_id'=>(int)($row['item_id']??0),'quantity_on_hand'=>$onHand,'quantity_allocated'=>$allocated,'available'=>$available,'unit_cost'=>$unitCost,'value'=>(float)($row['value']??round($onHand*$unitCost,2)),'snapshot_date'=>$row['snapshot_date']??$row['updated_at']??date('Y-m-d')];}return $rows;
}

function inventory_ops_locations(): array
{
    $rows=[];foreach(data_visible_collection('inventory_locations') as $row)$rows[]=['id'=>(int)$row['id'],'location_id'=>(int)($row['location_id']??$row['id']),'company_id'=>(int)($row['company_id']??0),'code'=>(string)($row['code']??''),'name'=>(string)($row['name']??$row['location_name']??'Inventory location'),'type'=>(string)($row['type']??$row['inventory_type']??'stock'),'status'=>(string)($row['status']??'active')];return $rows;
}

function inventory_ops_find_position(int $inventoryLocationId,int $itemId): ?array {foreach(inventory_ops_positions() as $row)if((int)$row['inventory_location_id']===$inventoryLocationId&&(int)$row['item_id']===$itemId)return $row;return null;}
function inventory_ops_find_location(int $id): ?array {foreach(inventory_ops_locations() as $row)if((int)$row['id']===$id)return $row;return null;}

function inventory_ops_company_filter(string $alias=''): array
{
    if(current_company_id()==='enterprise')return ['',[]];$prefix=$alias!==''?$alias.'.':'';return [" WHERE {$prefix}company_id=?",[(int)current_company_id()]];
}

function inventory_ops_policies(): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_replenishment_policies','inventory_ops_demo_policies');
    else{if(!inventory_ops_tables_ready())return [];[$where,$params]=inventory_ops_company_filter();$stmt=production_database_connection()->prepare('SELECT * FROM inventory_replenishment_policies'.$where.' ORDER BY updated_at DESC,id DESC');$stmt->execute($params);$rows=$stmt->fetchAll();}
    return array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));
}
function inventory_ops_find_policy(int $id): ?array {foreach(inventory_ops_policies() as $row)if((int)$row['id']===$id)return $row;return null;}

function inventory_ops_recommendations(): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_replenishment_recommendations','inventory_ops_demo_recommendations');
    else{if(!inventory_ops_tables_ready())return [];[$where,$params]=inventory_ops_company_filter();$stmt=production_database_connection()->prepare('SELECT * FROM inventory_replenishment_recommendations'.$where.' ORDER BY created_at DESC,id DESC');$stmt->execute($params);$rows=$stmt->fetchAll();}
    return array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));
}
function inventory_ops_find_recommendation(int $id): ?array {foreach(inventory_ops_recommendations() as $row)if((int)$row['id']===$id)return $row;return null;}

function inventory_ops_reservations(): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_reservations','inventory_ops_demo_reservations');
    else{if(!inventory_ops_tables_ready())return [];[$where,$params]=inventory_ops_company_filter();$stmt=production_database_connection()->prepare('SELECT * FROM inventory_reservations'.$where.' ORDER BY created_at DESC,id DESC');$stmt->execute($params);$rows=$stmt->fetchAll();}
    return array_values(array_filter($rows,static fn(array $row):bool=>data_record_visible($row)));
}
function inventory_ops_find_reservation(int $id): ?array {foreach(inventory_ops_reservations() as $row)if((int)$row['id']===$id)return $row;return null;}

function inventory_ops_transfers(): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_transfer_requests','inventory_ops_demo_transfers');
    else{if(!inventory_ops_tables_ready())return [];$params=[];$where='';if(current_company_id()!=='enterprise'){$where=' WHERE source_company_id=? OR destination_company_id=?';$params=[(int)current_company_id(),(int)current_company_id()];}$stmt=production_database_connection()->prepare('SELECT * FROM inventory_transfer_requests'.$where.' ORDER BY updated_at DESC,id DESC');$stmt->execute($params);$rows=$stmt->fetchAll();}
    if(current_company_id()==='enterprise')return array_values(array_filter($rows,static fn(array $row):bool=>data_company_within_scope((int)$row['source_company_id'])||data_company_within_scope((int)$row['destination_company_id'])));$selected=(int)current_company_id();return array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['source_company_id']===$selected||(int)$row['destination_company_id']===$selected));
}
function inventory_ops_find_transfer(int $id): ?array {foreach(inventory_ops_transfers() as $row)if((int)$row['id']===$id)return $row;return null;}

function inventory_ops_transfer_lines(int $transferId): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_transfer_lines','inventory_ops_demo_transfer_lines');
    else{if(!inventory_ops_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM inventory_transfer_lines WHERE transfer_request_id=? ORDER BY id');$stmt->execute([$transferId]);$rows=$stmt->fetchAll();}
    return array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['transfer_request_id']===$transferId));
}
function inventory_ops_find_transfer_line(int $id): ?array {foreach(inventory_ops_transfers() as $transfer)foreach(inventory_ops_transfer_lines((int)$transfer['id']) as $row)if((int)$row['id']===$id)return $row;return null;}

function inventory_ops_transfer_events(int $transferId): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_transfer_events','inventory_ops_demo_transfer_events');
    else{if(!inventory_ops_tables_ready())return [];$stmt=production_database_connection()->prepare('SELECT * FROM inventory_transfer_events WHERE transfer_request_id=? ORDER BY created_at DESC,id DESC');$stmt->execute([$transferId]);$rows=$stmt->fetchAll();}
    $rows=array_values(array_filter($rows,static fn(array $row):bool=>(int)$row['transfer_request_id']===$transferId));usort($rows,static fn(array $a,array $b):int=>strcmp((string)$b['created_at'],(string)$a['created_at']));return $rows;
}

function inventory_ops_events(?string $entityType=null,?int $entityId=null): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('inventory_governance_events','inventory_ops_demo_events');
    else{if(!inventory_ops_tables_ready())return [];$where=[];$params=[];if(current_company_id()!=='enterprise'){$where[]='company_id=?';$params[]=(int)current_company_id();}if($entityType!==null){$where[]='entity_type=?';$params[]=$entityType;}if($entityId!==null){$where[]='entity_id=?';$params[]=$entityId;}$stmt=production_database_connection()->prepare('SELECT * FROM inventory_governance_events'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY created_at DESC,id DESC');$stmt->execute($params);$rows=$stmt->fetchAll();}
    if(data_is_demo())$rows=array_values(array_filter($rows,static fn(array $r):bool=>data_record_visible($r)));if($entityType!==null)$rows=array_values(array_filter($rows,static fn(array $r):bool=>(string)$r['entity_type']===$entityType));if($entityId!==null)$rows=array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['entity_id']===$entityId));return $rows;
}

function inventory_ops_cycle_counts(): array
{
    if(data_is_demo()){$rows=inventory_ops_demo_collection('cycle_counts','inventory_ops_demo_cycle_counts');return array_values(array_filter($rows,static function(array $row):bool{$company=inventory_ops_location_company((int)$row['inventory_location_id']);return current_company_id()==='enterprise'?data_company_within_scope($company):$company===(int)current_company_id();}));}
    $pdo=production_database_connection();if(!$pdo)return [];$sql='SELECT cc.*,l.company_id FROM cycle_counts cc JOIN inventory_locations il ON il.id=cc.inventory_location_id JOIN locations l ON l.id=il.location_id';$params=[];if(current_company_id()!=='enterprise'){$sql.=' WHERE l.company_id=?';$params[]=(int)current_company_id();}$sql.=' ORDER BY cc.updated_at DESC,cc.id DESC';$stmt=$pdo->prepare($sql);$stmt->execute($params);return $stmt->fetchAll();
}
function inventory_ops_find_cycle_count(int $id): ?array {foreach(inventory_ops_cycle_counts() as $row)if((int)$row['id']===$id)return $row;return null;}
function inventory_ops_cycle_count_lines(int $countId): array
{
    if(data_is_demo())$rows=inventory_ops_demo_collection('cycle_count_lines','inventory_ops_demo_cycle_count_lines');
    else{$pdo=production_database_connection();if(!$pdo)return [];$stmt=$pdo->prepare('SELECT * FROM cycle_count_lines WHERE cycle_count_id=? ORDER BY id');$stmt->execute([$countId]);$rows=$stmt->fetchAll();}
    return array_values(array_filter($rows,static fn(array $r):bool=>(int)$r['cycle_count_id']===$countId));
}

function inventory_ops_transactions(): array
{
    if(data_is_demo()){$rows=inventory_ops_demo_collection('inventory_transactions','inventory_ops_demo_transactions');return array_values(array_filter($rows,static function(array $row):bool{$locationId=(int)($row['to_inventory_location_id']??$row['from_inventory_location_id']??0);$company=inventory_ops_location_company($locationId);return current_company_id()==='enterprise'?data_company_within_scope($company):$company===(int)current_company_id();}));}
    $pdo=production_database_connection();if(!$pdo)return [];$sql='SELECT it.*,l.company_id FROM inventory_transactions it LEFT JOIN inventory_locations il ON il.id=COALESCE(it.to_inventory_location_id,it.from_inventory_location_id) LEFT JOIN locations l ON l.id=il.location_id';$params=[];if(current_company_id()!=='enterprise'){$sql.=' WHERE l.company_id=?';$params[]=(int)current_company_id();}$sql.=' ORDER BY it.performed_at DESC,it.id DESC LIMIT 250';$stmt=$pdo->prepare($sql);$stmt->execute($params);return $stmt->fetchAll();
}

