<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/inventory_operations.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('inventory-operations.php'));
verify_csrf();

function inventory_ops_valid_date(string $value,bool $allowBlank=false): ?string
{
    $value=trim($value);if($allowBlank&&$value==='')return null;$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$date||$date->format('Y-m-d')!==$value)throw new RuntimeException('A valid date is required.');return $value;
}
function inventory_ops_required_evidence(string $value): string
{
    $value=trim($value);if($value==='')throw new RuntimeException('Governance evidence is required.');return mb_substr($value,0,5000);
}
function inventory_ops_redirect(array $params=[]): never
{
    $query=$params?'?'.http_build_query($params):'';redirect_to(app_url('inventory-operations.php'.$query));
}

try{
    $action=post_string('action');

    if($action==='save_policy'){
        require_permission('purchase_orders.create');$companyId=post_int('company_id',inventory_ops_default_company_id());$locationId=post_int('inventory_location_id');$itemId=post_int('item_id');$location=inventory_ops_find_location($locationId);$item=data_find('items',$itemId);if(!$location||!$item)throw new RuntimeException('Select a valid inventory location and item.');if(!data_company_within_scope($companyId)||(int)$location['company_id']!==$companyId)throw new RuntimeException('The policy company and inventory location must be within the active scope.');
        $minimum=max(0,(float)post_string('minimum_quantity','0'));$maximum=max(0,(float)post_string('maximum_quantity','0'));$reorder=max(0,(float)post_string('reorder_point','0'));$safety=max(0,(float)post_string('safety_stock','0'));if($maximum>0&&$maximum<$minimum)throw new RuntimeException('Maximum quantity cannot be below minimum quantity.');if($maximum>0&&$reorder>$maximum)throw new RuntimeException('Reorder point cannot exceed maximum quantity.');
        $supplierId=post_int('preferred_supplier_id');if($supplierId>0&&!data_find('suppliers',$supplierId))throw new RuntimeException('Select a valid preferred supplier.');$evidence=inventory_ops_required_evidence(post_string('evidence_note'));
        $saved=inventory_ops_save_policy(['id'=>null,'policy_number'=>inventory_ops_number('POL-INV',inventory_ops_policies()),'company_id'=>$companyId,'inventory_location_id'=>$locationId,'item_id'=>$itemId,'minimum_quantity'=>$minimum,'maximum_quantity'=>$maximum,'reorder_point'=>$reorder,'safety_stock'=>$safety,'lead_time_days'=>max(1,post_int('lead_time_days',14)),'review_frequency_days'=>max(1,post_int('review_frequency_days',7)),'preferred_supplier_id'=>$supplierId?:null,'owner_id'=>max(1,post_int('owner_id',(int)current_user()['id'])),'reviewer_id'=>max(1,post_int('reviewer_id',6)),'status'=>'active','evidence_note'=>$evidence]);
        inventory_ops_add_event($companyId,$locationId,$itemId,'inventory_replenishment_policy',(int)$saved['id'],'policy_created',null,'active','medium',0,0,$evidence);data_add_audit('Inventory Operations','policy_created','inventory_replenishment_policy',$saved['id'],null,$saved,$companyId);flash('success','Replenishment policy saved: '.$saved['policy_number'].'.');inventory_ops_redirect(['policy_id'=>$saved['id'],'section'=>'replenishment']);
    }

    if($action==='generate_recommendation'){
        require_permission('purchase_orders.create');$policy=inventory_ops_find_policy(post_int('policy_id'));if(!$policy)throw new RuntimeException('The replenishment policy is outside the active scope.');$saved=inventory_ops_generate_recommendation($policy);data_add_audit('Inventory Operations','recommendation_generated','inventory_replenishment_recommendation',$saved['id'],null,$saved,$saved['company_id']);flash('success','Recommendation generated: '.$saved['recommendation_number'].'.');inventory_ops_redirect(['recommendation_id'=>$saved['id']]);
    }

    if($action==='submit_recommendation'){
        require_permission('approvals.submit');$before=inventory_ops_find_recommendation(post_int('id'));if(!$before)throw new RuntimeException('The recommendation is outside the active scope.');$saved=inventory_ops_submit_recommendation($before);data_add_audit('Inventory Operations','recommendation_submitted','inventory_replenishment_recommendation',$saved['id'],$before,$saved,$saved['company_id']);flash('success','Recommendation routed to Reviews & Approvals.');inventory_ops_redirect(['recommendation_id'=>$saved['id']]);
    }

    if($action==='convert_recommendation'){
        require_permission('purchase_orders.create');$before=inventory_ops_find_recommendation(post_int('id'));if(!$before)throw new RuntimeException('The recommendation is outside the active scope.');$conversion=inventory_ops_convert_recommendation($before);$saved=inventory_ops_find_recommendation((int)$before['id']);data_add_audit('Inventory Operations','recommendation_converted','inventory_replenishment_recommendation',$before['id'],$before,['recommendation'=>$saved,'conversion'=>$conversion],$before['company_id']);flash('success','Recommendation converted to '.status_label($conversion['kind']).' #'.$conversion['id'].'.');if($conversion['kind']==='transfer')inventory_ops_redirect(['transfer_id'=>$conversion['id']]);redirect_to(app_url('demand.php?id='.(int)$conversion['id']));
    }

    if($action==='create_reservation'){
        require_permission('purchase_orders.create');$locationId=post_int('inventory_location_id');$location=inventory_ops_find_location($locationId);$itemId=post_int('item_id');if(!$location||!data_find('items',$itemId))throw new RuntimeException('Select a valid location and item.');$companyId=(int)$location['company_id'];if(!data_company_within_scope($companyId))throw new RuntimeException('The inventory location is outside the active company scope.');$quantity=max(0,(float)post_string('quantity','0'));if($quantity<=0)throw new RuntimeException('Reservation quantity must be greater than zero.');$referenceType=post_string('reference_type','other');if(!in_array($referenceType,['project','work_order','service_commitment','customer_order','other'],true))throw new RuntimeException('Select a supported reservation reference.');$referenceId=post_int('reference_id');$evidence=inventory_ops_required_evidence(post_string('evidence_note'));
        $saved=inventory_ops_create_reservation(['id'=>null,'reservation_number'=>null,'company_id'=>$companyId,'inventory_location_id'=>$locationId,'item_id'=>$itemId,'project_workorder_id'=>in_array($referenceType,['project','work_order'],true)&&$referenceId>0?$referenceId:null,'reference_type'=>$referenceType,'reference_id'=>$referenceId?:null,'quantity'=>$quantity,'status'=>'active','needed_date'=>inventory_ops_valid_date(post_string('needed_date',date('Y-m-d',strtotime('+7 days')))),'owner_id'=>(int)current_user()['id'],'evidence_note'=>$evidence]);data_add_audit('Inventory Operations','reservation_created','inventory_reservation',$saved['id'],null,$saved,$companyId);flash('success','Inventory reserved: '.$saved['reservation_number'].'.');inventory_ops_redirect(['reservation_id'=>$saved['id']]);
    }

    if($action==='release_reservation'){
        require_permission('purchase_orders.create');$before=inventory_ops_find_reservation(post_int('id'));if(!$before)throw new RuntimeException('The reservation is outside the active scope.');$saved=inventory_ops_release_reservation($before,inventory_ops_required_evidence(post_string('evidence_note')));data_add_audit('Inventory Operations','reservation_released','inventory_reservation',$saved['id'],$before,$saved,$saved['company_id']);flash('success','Reservation released: '.$saved['reservation_number'].'.');inventory_ops_redirect(['reservation_id'=>$saved['id']]);
    }

    if($action==='save_transfer'){
        require_permission('purchase_orders.create');$sourceId=post_int('source_inventory_location_id');$destinationId=post_int('destination_inventory_location_id');$source=inventory_ops_find_location($sourceId);$destination=inventory_ops_find_location($destinationId);if(!$source||!$destination||$sourceId===$destinationId)throw new RuntimeException('Select different valid source and destination locations.');if(!data_company_within_scope((int)$source['company_id'])||!data_company_within_scope((int)$destination['company_id']))throw new RuntimeException('Both transfer companies must be within the permitted scope.');$evidence=inventory_ops_required_evidence(post_string('evidence_note'));
        $saved=inventory_ops_save_transfer(['id'=>null,'transfer_number'=>inventory_ops_number('TRF-INV',inventory_ops_transfers()),'source_company_id'=>$source['company_id'],'destination_company_id'=>$destination['company_id'],'source_inventory_location_id'=>$sourceId,'destination_inventory_location_id'=>$destinationId,'project_workorder_id'=>post_int('project_workorder_id')?:null,'status'=>'draft','required_date'=>inventory_ops_valid_date(post_string('required_date',date('Y-m-d',strtotime('+7 days')))),'shipped_at'=>null,'received_at'=>null,'carrier'=>'','tracking_number'=>'','freight_cost'=>max(0,(float)post_string('freight_cost','0')),'owner_id'=>max(1,post_int('owner_id',(int)current_user()['id'])),'reviewer_id'=>max(1,post_int('reviewer_id',6)),'approval_id'=>null,'evidence_note'=>$evidence]);inventory_ops_add_transfer_event((int)$saved['id'],'transfer_created',null,'draft','medium',$evidence);data_add_audit('Inventory Operations','transfer_created','inventory_transfer_request',$saved['id'],null,$saved,$saved['source_company_id']);flash('success','Transfer request created: '.$saved['transfer_number'].'.');inventory_ops_redirect(['transfer_id'=>$saved['id']]);
    }

    if($action==='save_transfer_line'){
        require_permission('purchase_orders.create');$transfer=inventory_ops_find_transfer(post_int('transfer_request_id'));if(!$transfer)throw new RuntimeException('The transfer request is outside the active scope.');if(!in_array(inventory_ops_transfer_effective_status($transfer),['draft','changes_requested'],true))throw new RuntimeException('Transfer lines can be changed only before approval.');$itemId=post_int('item_id');if(!data_find('items',$itemId))throw new RuntimeException('Select a valid inventory item.');$quantity=max(0,(float)post_string('quantity_requested','0'));if($quantity<=0)throw new RuntimeException('Transfer quantity must be greater than zero.');$position=inventory_ops_find_position((int)$transfer['source_inventory_location_id'],$itemId);if(!$position||(float)$position['available']+0.0001<$quantity)throw new RuntimeException('Source available inventory is insufficient for this line.');
        $saved=inventory_ops_save_transfer_line(['id'=>null,'transfer_request_id'=>$transfer['id'],'item_id'=>$itemId,'quantity_requested'=>$quantity,'quantity_shipped'=>0,'quantity_received'=>0,'unit_cost'=>max(0,(float)post_string('unit_cost',(string)$position['unit_cost'])),'serial_or_lot_reference'=>mb_substr(trim(post_string('serial_or_lot_reference')),0,240),'condition_status'=>'pending','notes'=>inventory_ops_required_evidence(post_string('notes'))]);inventory_ops_add_transfer_event((int)$transfer['id'],'line_added',$transfer['status'],$transfer['status'],'medium','Added '.number_format($quantity,2).' units of '.data_item_name($itemId).'.');data_add_audit('Inventory Operations','transfer_line_created','inventory_transfer_line',$saved['id'],null,$saved,$transfer['source_company_id']);flash('success','Transfer line added.');inventory_ops_redirect(['transfer_id'=>$transfer['id']]);
    }

    if($action==='submit_transfer'){
        require_permission('approvals.submit');$before=inventory_ops_find_transfer(post_int('id'));if(!$before)throw new RuntimeException('The transfer request is outside the active scope.');$saved=inventory_ops_submit_transfer($before);data_add_audit('Inventory Operations','transfer_submitted','inventory_transfer_request',$saved['id'],$before,$saved,$saved['source_company_id']);flash('success','Transfer routed to Reviews & Approvals.');inventory_ops_redirect(['transfer_id'=>$saved['id']]);
    }

    if($action==='dispatch_transfer'){
        require_permission('purchase_orders.approve');$before=inventory_ops_find_transfer(post_int('id'));if(!$before)throw new RuntimeException('The transfer request is outside the active scope.');$saved=inventory_ops_dispatch_transfer($before,trim(post_string('carrier','Internal fleet')),trim(post_string('tracking_number')),inventory_ops_required_evidence(post_string('evidence_note')));data_add_audit('Inventory Operations','transfer_dispatched','inventory_transfer_request',$saved['id'],$before,$saved,$saved['source_company_id']);inventory_ops_notify((int)$saved['reviewer_id'],(int)$saved['destination_company_id'],'Inventory transfer dispatched',$saved['transfer_number'].' is in transit.','info');flash('success','Transfer dispatched: '.$saved['transfer_number'].'.');inventory_ops_redirect(['transfer_id'=>$saved['id']]);
    }

    if($action==='receive_transfer'){
        require_permission('purchase_orders.approve');$before=inventory_ops_find_transfer(post_int('id'));if(!$before)throw new RuntimeException('The transfer request is outside the active scope.');$saved=inventory_ops_receive_transfer($before,inventory_ops_required_evidence(post_string('evidence_note')));data_add_audit('Inventory Operations','transfer_received','inventory_transfer_request',$saved['id'],$before,$saved,$saved['destination_company_id']);flash('success','Transfer receipt posted: '.$saved['transfer_number'].'.');inventory_ops_redirect(['transfer_id'=>$saved['id']]);
    }

    if($action==='create_cycle_count'){
        require_permission('purchase_orders.create');$locationId=post_int('inventory_location_id');$location=inventory_ops_find_location($locationId);if(!$location||!data_company_within_scope((int)$location['company_id']))throw new RuntimeException('The inventory location is outside the active scope.');$saved=inventory_ops_save_cycle_count(['id'=>null,'inventory_location_id'=>$locationId,'count_reference'=>'CC-'.preg_replace('/[^A-Z0-9]+/','',strtoupper((string)$location['code'])).'-'.date('ymd').'-'.str_pad((string)(count(inventory_ops_cycle_counts())+1),2,'0',STR_PAD_LEFT),'status'=>'open','scheduled_date'=>inventory_ops_valid_date(post_string('scheduled_date',date('Y-m-d',strtotime('+3 days')))),'completed_date'=>null,'assigned_to'=>max(1,post_int('assigned_to',(int)current_user()['id'])),'approved_by'=>null]);inventory_ops_add_event((int)$location['company_id'],$locationId,null,'cycle_count',(int)$saved['id'],'count_scheduled',null,'open','medium',0,0,'Cycle count scheduled with assigned custody owner.');data_add_audit('Inventory Operations','cycle_count_created','cycle_count',$saved['id'],null,$saved,$location['company_id']);flash('success','Cycle count scheduled: '.$saved['count_reference'].'.');inventory_ops_redirect(['count_id'=>$saved['id']]);
    }

    if($action==='save_cycle_line'){
        require_permission('purchase_orders.create');$count=inventory_ops_find_cycle_count(post_int('cycle_count_id'));if(!$count)throw new RuntimeException('The cycle count is outside the active scope.');if(!in_array((string)$count['status'],['planned','open'],true))throw new RuntimeException('Count lines cannot be changed after submission.');$itemId=post_int('item_id');if(!data_find('items',$itemId))throw new RuntimeException('Select a valid item.');$position=inventory_ops_find_position((int)$count['inventory_location_id'],$itemId);$existing=null;foreach(inventory_ops_cycle_count_lines((int)$count['id']) as $line)if((int)$line['item_id']===$itemId){$existing=$line;break;}$saved=inventory_ops_save_cycle_line(['id'=>$existing['id']??null,'cycle_count_id'=>$count['id'],'item_id'=>$itemId,'system_quantity'=>(float)($position['quantity_on_hand']??0),'counted_quantity'=>max(0,(float)post_string('counted_quantity','0')),'variance_reason'=>inventory_ops_required_evidence(post_string('variance_reason'))]);data_add_audit('Inventory Operations',$existing?'cycle_count_line_updated':'cycle_count_line_created','cycle_count_line',$saved['id'],$existing,$saved,inventory_ops_location_company((int)$count['inventory_location_id']));flash('success','Cycle count line saved.');inventory_ops_redirect(['count_id'=>$count['id']]);
    }

    if($action==='submit_cycle_count'){
        require_permission('approvals.submit');$before=inventory_ops_find_cycle_count(post_int('id'));if(!$before)throw new RuntimeException('The cycle count is outside the active scope.');$saved=inventory_ops_submit_cycle_count($before);data_add_audit('Inventory Operations','cycle_count_submitted','cycle_count',$saved['id'],$before,$saved,inventory_ops_location_company((int)$saved['inventory_location_id']));flash('success','Cycle count routed to Reviews & Approvals.');inventory_ops_redirect(['count_id'=>$saved['id']]);
    }

    if($action==='post_cycle_count'){
        require_permission('purchase_orders.approve');$before=inventory_ops_find_cycle_count(post_int('id'));if(!$before)throw new RuntimeException('The cycle count is outside the active scope.');$saved=inventory_ops_post_cycle_count($before,inventory_ops_required_evidence(post_string('evidence_note')));data_add_audit('Inventory Operations','cycle_count_posted','cycle_count',$saved['id'],$before,$saved,inventory_ops_location_company((int)$saved['inventory_location_id']));flash('success','Approved count variance posted: '.$saved['count_reference'].'.');inventory_ops_redirect(['count_id'=>$saved['id']]);
    }

    if($action==='controlled_movement'){
        require_permission('purchase_orders.approve');$locationId=post_int('inventory_location_id');$location=inventory_ops_find_location($locationId);$itemId=post_int('item_id');if(!$location||!data_company_within_scope((int)$location['company_id'])||!data_find('items',$itemId))throw new RuntimeException('Select a valid scoped location and item.');$transaction=inventory_ops_controlled_movement($locationId,$itemId,post_string('movement_type'),max(0,(float)post_string('quantity','0')),post_int('project_workorder_id')?:null,inventory_ops_required_evidence(post_string('evidence_note')));data_add_audit('Inventory Operations','controlled_movement_posted','inventory_transaction',$transaction['id'],null,$transaction,$location['company_id']);flash('success','Controlled inventory movement posted.');inventory_ops_redirect(['transaction_id'=>$transaction['id']]);
    }

    throw new RuntimeException('Unknown inventory-operations action.');
}catch(Throwable $exception){flash('error','The inventory-operations action could not be completed: '.$exception->getMessage());redirect_to(app_url('inventory-operations.php'));}
