<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/inventory-operations.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Gruber Section 19 quality test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
require_once $root.'/includes/app/inventory_operations.php';

$positions=inventory_ops_positions();$locations=inventory_ops_locations();
if(!$positions||count($locations)<2){fwrite(STDERR,"Inventory positions or locations are unavailable.\n");exit(1);}
$source=null;foreach($positions as $position)if((float)$position['available']>=5&&inventory_ops_find_location((int)$position['inventory_location_id'])){$source=$position;break;}
if(!$source){fwrite(STDERR,"No usable inventory source position is available.\n");exit(1);}
$destination=null;foreach($locations as $location)if((int)$location['id']!==(int)$source['inventory_location_id']&&(int)$location['company_id']>0){$destination=$location;break;}
if(!$destination){fwrite(STDERR,"No transfer destination is available.\n");exit(1);}
$companyId=(int)$source['company_id'];$itemId=(int)$source['item_id'];$locationId=(int)$source['inventory_location_id'];

$policy=inventory_ops_save_policy([
    'id'=>null,'policy_number'=>'POL-QUALITY-019','company_id'=>$companyId,'inventory_location_id'=>$locationId,'item_id'=>$itemId,
    'minimum_quantity'=>1,'maximum_quantity'=>max(20,(float)$source['quantity_on_hand']+20),'reorder_point'=>5,'safety_stock'=>2,
    'lead_time_days'=>7,'review_frequency_days'=>7,'preferred_supplier_id'=>null,'owner_id'=>1,'reviewer_id'=>6,'status'=>'active','evidence_note'=>'Section 19 quality policy evidence.'
]);
if((int)$policy['id']<=0||!inventory_ops_find_policy((int)$policy['id'])){fwrite(STDERR,"Replenishment policy persistence failed.\n");exit(1);}
$blueprint=inventory_ops_policy_blueprint($policy);
foreach(['available_quantity','allocated_quantity','open_po_quantity','forecast_quantity','safety_stock_quantity','recommended_quantity','estimated_value','recommendation_type','required_date','readiness_score'] as $field)if(!array_key_exists($field,$blueprint)){fwrite(STDERR,"Policy blueprint missing {$field}.\n");exit(1);}
$recommendation=inventory_ops_generate_recommendation($policy);
if((int)$recommendation['id']<=0||!inventory_ops_find_recommendation((int)$recommendation['id'])){fwrite(STDERR,"Replenishment recommendation persistence failed.\n");exit(1);}

$beforeReservation=inventory_ops_find_position($locationId,$itemId);
$reservation=inventory_ops_create_reservation([
    'id'=>null,'reservation_number'=>null,'company_id'=>$companyId,'inventory_location_id'=>$locationId,'item_id'=>$itemId,
    'project_workorder_id'=>null,'reference_type'=>'other','reference_id'=>1900,'quantity'=>1,'status'=>'active','needed_date'=>date('Y-m-d',strtotime('+5 days')),
    'owner_id'=>1,'evidence_note'=>'Section 19 reservation evidence.'
]);
$afterReservation=inventory_ops_find_position($locationId,$itemId);
if((int)$reservation['id']<=0||abs(((float)$afterReservation['quantity_allocated']-(float)$beforeReservation['quantity_allocated'])-1)>0.0001){fwrite(STDERR,"Reservation allocation posting failed.\n");exit(1);}
$released=inventory_ops_release_reservation($reservation,'Section 19 reservation release evidence.');
$afterRelease=inventory_ops_find_position($locationId,$itemId);
if($released['status']!=='released'||abs((float)$afterRelease['quantity_allocated']-(float)$beforeReservation['quantity_allocated'])>0.0001){fwrite(STDERR,"Reservation release posting failed.\n");exit(1);}

$transfer=inventory_ops_save_transfer([
    'id'=>null,'transfer_number'=>'TRF-QUALITY-019','source_company_id'=>$companyId,'destination_company_id'=>$destination['company_id'],
    'source_inventory_location_id'=>$locationId,'destination_inventory_location_id'=>$destination['id'],'project_workorder_id'=>null,'status'=>'draft',
    'required_date'=>date('Y-m-d',strtotime('+7 days')),'shipped_at'=>null,'received_at'=>null,'carrier'=>'','tracking_number'=>'','freight_cost'=>10,
    'owner_id'=>1,'reviewer_id'=>6,'approval_id'=>null,'evidence_note'=>'Section 19 transfer evidence.'
]);
$line=inventory_ops_save_transfer_line([
    'id'=>null,'transfer_request_id'=>$transfer['id'],'item_id'=>$itemId,'quantity_requested'=>2,'quantity_shipped'=>0,'quantity_received'=>0,
    'unit_cost'=>$source['unit_cost'],'serial_or_lot_reference'=>'QUALITY-LOT','condition_status'=>'pending','notes'=>'Section 19 transfer line evidence.'
]);
if((int)$line['id']<=0){fwrite(STDERR,"Transfer line persistence failed.\n");exit(1);}
$submitted=inventory_ops_submit_transfer($transfer);
$approval=data_find('workflow_approvals',(int)$submitted['approval_id']);$approval['status']='approved';data_upsert('workflow_approvals',$approval);
$sourceBeforeDispatch=inventory_ops_find_position($locationId,$itemId);
$dispatched=inventory_ops_dispatch_transfer($submitted,'Quality carrier','QUALITY-TRACK','Section 19 dispatch evidence.');
$sourceAfterDispatch=inventory_ops_find_position($locationId,$itemId);
if($dispatched['status']!=='in_transit'||abs(((float)$sourceBeforeDispatch['quantity_on_hand']-(float)$sourceAfterDispatch['quantity_on_hand'])-2)>0.0001){fwrite(STDERR,"Transfer dispatch balance posting failed.\n");exit(1);}
$destinationBefore=inventory_ops_find_position((int)$destination['id'],$itemId);
$received=inventory_ops_receive_transfer($dispatched,'Section 19 destination receipt evidence.');
$destinationAfter=inventory_ops_find_position((int)$destination['id'],$itemId);
$priorDestination=(float)($destinationBefore['quantity_on_hand']??0);
if($received['status']!=='received'||abs(((float)$destinationAfter['quantity_on_hand']-$priorDestination)-2)>0.0001){fwrite(STDERR,"Transfer receipt balance posting failed.\n");exit(1);}
if(count(inventory_ops_transfer_events((int)$transfer['id']))<3){fwrite(STDERR,"Transfer event history failed.\n");exit(1);}

$count=inventory_ops_save_cycle_count(['id'=>null,'inventory_location_id'=>$locationId,'count_reference'=>'CC-QUALITY-019','status'=>'open','scheduled_date'=>date('Y-m-d'),'completed_date'=>null,'assigned_to'=>1,'approved_by'=>null]);
$currentPosition=inventory_ops_find_position($locationId,$itemId);
$countLine=inventory_ops_save_cycle_line(['id'=>null,'cycle_count_id'=>$count['id'],'item_id'=>$itemId,'system_quantity'=>$currentPosition['quantity_on_hand'],'counted_quantity'=>$currentPosition['quantity_on_hand']+1,'variance_reason'=>'Section 19 approved count variance evidence.']);
if(abs(inventory_ops_cycle_count_variance($countLine)-1)>0.0001){fwrite(STDERR,"Cycle-count variance calculation failed.\n");exit(1);}
$submittedCount=inventory_ops_submit_cycle_count($count);$countApproval=inventory_ops_cycle_count_approval((int)$count['id']);$countApproval['status']='approved';data_upsert('workflow_approvals',$countApproval);
$postedCount=inventory_ops_post_cycle_count($submittedCount,'Section 19 approved count posting evidence.');
if($postedCount['status']!=='posted'){fwrite(STDERR,"Cycle-count approval posting failed.\n");exit(1);}

$movement=inventory_ops_controlled_movement($locationId,$itemId,'return_to_stock',1,null,'Section 19 controlled movement evidence.');
if((int)$movement['id']<=0||$movement['transaction_type']!=='return_to_stock'){fwrite(STDERR,"Controlled inventory movement failed.\n");exit(1);}
$metrics=inventory_ops_metrics();foreach(['inventory_value','available_quantity','active_reservations','reserved_quantity','open_transfers','replenishment_value','stockout_risks','excess_value','open_counts'] as $field)if(!array_key_exists($field,$metrics)){fwrite(STDERR,"Inventory metrics missing {$field}.\n");exit(1);}
if(inventory_ops_csv_cell('=SUM(A1:A2)')!=="'=SUM(A1:A2)"){fwrite(STDERR,"Inventory CSV protection failed.\n");exit(1);}

$page=file_get_contents($root.'/app/inventory-operations.php').file_get_contents($root.'/includes/app/inventory_operations_view.php').file_get_contents($root.'/includes/app/inventory_operations_view_main.php').file_get_contents($root.'/includes/app/inventory_operations_view_aside.php');
$actionFile=file_get_contents($root.'/app/inventory-operations-action.php');
$sql=file_get_contents($root.'/database/20260727_section19_inventory_operations_replenishment_governance.sql');
$inventoryPage=file_get_contents($root.'/app/inventory.php');$fulfillmentPage=file_get_contents($root.'/app/fulfillment.php');$demandPage=file_get_contents($root.'/app/demand.php');
foreach(['Inventory Operations, Replenishment & Transfer Governance','Replenishment governance','Reservations and controlled movements','Transfer governance','Cycle counts','Operations history'] as $needle)if(!str_contains($page,$needle)){fwrite(STDERR,"Inventory workspace missing {$needle}.\n");exit(1);}
foreach(['save_policy','generate_recommendation','submit_recommendation','convert_recommendation','create_reservation','release_reservation','save_transfer','save_transfer_line','submit_transfer','dispatch_transfer','receive_transfer','create_cycle_count','save_cycle_line','submit_cycle_count','post_cycle_count','controlled_movement'] as $needle)if(!str_contains($actionFile,$needle)){fwrite(STDERR,"Inventory handler missing {$needle}.\n");exit(1);}
foreach(['CREATE TABLE IF NOT EXISTS inventory_replenishment_policies','CREATE TABLE IF NOT EXISTS inventory_replenishment_recommendations','CREATE TABLE IF NOT EXISTS inventory_reservations','CREATE TABLE IF NOT EXISTS inventory_transfer_requests','CREATE TABLE IF NOT EXISTS inventory_transfer_lines','CREATE TABLE IF NOT EXISTS inventory_transfer_events','CREATE TABLE IF NOT EXISTS inventory_governance_events','4.8-section19'] as $needle)if(!str_contains($sql,$needle)){fwrite(STDERR,"Section 19 SQL missing {$needle}.\n");exit(1);}
if(!str_contains($inventoryPage,'inventory-operations.php')||!str_contains($fulfillmentPage,'inventory-operations.php')||!str_contains($demandPage,'inventory-operations.php')){fwrite(STDERR,"Inventory workflow handoffs are missing.\n");exit(1);}
if(str_contains($sql,'gruber_ai_procurement_single_install_v3')){fwrite(STDERR,"Section 19 migration must not reimport the fresh-install schema.\n");exit(1);}
fwrite(STDOUT,"Section 19 inventory operations, replenishment, transfer, reservation, cycle-count, and controlled-movement gates passed.\n");
