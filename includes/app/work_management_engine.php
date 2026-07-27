<?php
declare(strict_types=1);

function work_management_can(string $capability,?array $user=null):bool
{
    $user??=current_user();if(!$user)return false;if(can('work_management.'.$capability,$user))return true;
    $fallback=match($capability){'view','analyze'=>'approvals.view','create','execute'=>'approvals.submit','assign','review'=>'approvals.review','approve'=>'approvals.approve','automate','administer'=>'platform.administer','export'=>'reports.export',default=>''};
    return$fallback!==''&&can($fallback,$user);
}
function work_management_require(string$capability):void{if(!work_management_can($capability))throw new RuntimeException('You do not have permission to '.$capability.' enterprise work.');}
function work_management_active_user(int$userId):?array{foreach(data_collection('users')as$user)if((int)$user['id']===$userId&&($user['status']??'')==='active')return$user;return null;}
function work_management_entity(int$entityId):?array{return entity_system_find(entity_system_all_entities(),$entityId);}
function work_management_origin_company_id(int$entityId):?int{$entity=work_management_entity($entityId);$companyId=(int)($entity['company_id']??0);return$companyId>0?$companyId:null;}
function work_management_user_can_receive(array$user,int$originEntityId,string$permission='platform.view'):bool
{
    if(($user['status']??'')!=='active')return false;if(in_array('system_administrator',current_role_codes($user),true))return true;
    $companyId=work_management_origin_company_id($originEntityId);if($companyId===null||!in_array($companyId,array_map('intval',$user['company_ids']??[]),true))return false;
    return$permission===''||can($permission,$user);
}
function work_management_users_for_role(string$roleCode,int$originEntityId,string$permission='platform.view'):array
{
    $users=array_values(array_filter(data_collection('users'),static fn(array$user):bool=>in_array($roleCode,$user['role_codes']??[],true)&&work_management_user_can_receive($user,$originEntityId,$permission)));
    usort($users,static fn(array$a,array$b):int=>(int)($a['primary_company_id']??0)<=>(int)($b['primary_company_id']??0)?:strcmp((string)$a['name'],(string)$b['name']));return$users;
}
function work_management_find_entity_by_code(string$code):?array{foreach(entity_system_all_entities()as$entity)if(strcasecmp((string)$entity['entity_code'],$code)===0)return$entity;return null;}
function work_management_routing_rule(string$sourceModule,string$workType,int$originEntityId):?array
{
    $matches=[];foreach(work_management_routing_rules()as$rule){if(($rule['status']??'')!=='active')continue;if(!empty($rule['effective_from'])&&(string)$rule['effective_from']>date('Y-m-d'))continue;if(!empty($rule['effective_to'])&&(string)$rule['effective_to']<date('Y-m-d'))continue;if((string)$rule['source_module']!==$sourceModule||(string)$rule['work_type']!==$workType)continue;if(!empty($rule['entity_id'])&&(int)$rule['entity_id']!==$originEntityId)continue;$matches[]=$rule;}
    usort($matches,static fn(array$a,array$b):int=>(int)$a['sort_order']<=>(int)$b['sort_order']);return$matches[0]??null;
}
function work_management_route(string$sourceModule,string$workType,int$originEntityId,string$requiredPermission,string$priority,?int$preferredEntityId=null,?int$preferredUserId=null,?string$preferredRole=null,?int$slaMinutes=null):array
{
    $origin=entity_system_find_entity($originEntityId);if(!$origin||(int)($origin['company_id']??0)<=0)throw new RuntimeException('Work routing requires an operating entity within the active company scope.');
    $rule=work_management_routing_rule($sourceModule,$workType,$originEntityId);$assignedEntityId=$preferredEntityId;$assignedRole=$preferredRole?:(string)($rule['assigned_role_code']??'data_contributor');
    if(!$assignedEntityId&&!empty($rule['shared_service_entity_code'])){$service=work_management_find_entity_by_code((string)$rule['shared_service_entity_code']);$assignedEntityId=$service?(int)$service['id']:null;}
    $assignedEntityId??=$originEntityId;if(!entity_system_find_entity($assignedEntityId))throw new RuntimeException('The routed entity is outside the active company scope.');
    $assignedUserId=$preferredUserId;if($assignedUserId){$target=work_management_active_user($assignedUserId);if(!$target||!work_management_user_can_receive($target,$originEntityId,$requiredPermission))throw new RuntimeException('The selected assignee cannot receive this company-scoped work item.');}
    else{$users=work_management_users_for_role($assignedRole,$originEntityId,$requiredPermission);$assignedUserId=isset($users[0])?(int)$users[0]['id']:null;}
    $priorityOverride=trim((string)($rule['priority_override']??''));if($priorityOverride!=='')$priority=$priorityOverride;$slaMinutes??=max(30,(int)($rule['sla_minutes']??480));
    $reviewers=work_management_users_for_role((string)($rule['reviewer_role_code']??'reviewer'),$originEntityId,'work_management.review');$approverRole=(string)($rule['approver_role_code']??'');$approvers=$approverRole!==''?work_management_users_for_role($approverRole,$originEntityId,'work_management.approve'):[];
    $reviewerId=null;foreach($reviewers as$reviewer)if((int)$reviewer['id']!==(int)$assignedUserId){$reviewerId=(int)$reviewer['id'];break;}
    $approverId=null;foreach($approvers as$approver)if(!in_array((int)$approver['id'],array_filter([(int)$assignedUserId,(int)$reviewerId]),true)){$approverId=(int)$approver['id'];break;}
    return['rule'=>$rule,'assigned_entity_id'=>$assignedEntityId,'assigned_user_id'=>$assignedUserId,'assigned_role_code'=>$assignedRole,'priority'=>$priority,'sla_minutes'=>$slaMinutes,'reviewer_id'=>$reviewerId,'approver_id'=>$approverId];
}
function work_management_source_key(string$sourceModule,string$sourceType,int$sourceId,string$workType,int$entityId):string{return hash('sha256',implode('|',[$sourceModule,$sourceType,$sourceId,$workType,$entityId]));}
function work_management_next_number():string
{
    if(data_is_demo()){$max=0;foreach(work_management_items()as$item)if(preg_match('/(\d+)$/',(string)$item['work_number'],$matches))$max=max($max,(int)$matches[1]);return'WORK-'.date('Y').'-'.str_pad((string)($max+1),4,'0',STR_PAD_LEFT);}
    return'WORK-'.date('Ymd-His').'-'.strtoupper(substr(hash('sha256',microtime(true).'|'.random_int(1,PHP_INT_MAX)),0,6));
}
function work_management_create_item(array$payload,?int$automationRuleId=null):array
{
    work_management_require('create');$entityId=(int)($payload['entity_id']??0);$entity=entity_system_find_entity($entityId);if(!$entity||(int)($entity['company_id']??0)<=0)throw new RuntimeException('Select an operating entity within the active company scope.');
    $sourceModule=trim((string)($payload['source_module']??'manual'));$sourceType=trim((string)($payload['source_type']??'manual_work'));$sourceId=max(0,(int)($payload['source_id']??0));$workType=trim((string)($payload['work_type']??'general'));
    $sourceKey=(string)($payload['source_key']??work_management_source_key($sourceModule,$sourceType,$sourceId,$workType,$entityId));$existing=work_management_find_item_by_source_key($sourceKey);if($existing&&!in_array((string)$existing['status'],['completed','cancelled'],true))return$existing;
    $title=trim((string)($payload['title']??''));$description=trim((string)($payload['description']??''));if($title===''||$description==='')throw new RuntimeException('Work title and evidence-based description are required.');
    $requiredPermission=trim((string)($payload['required_permission']??'platform.view'));$priority=strtolower(trim((string)($payload['priority']??'medium')));if(!in_array($priority,['low','medium','high','critical'],true))$priority='medium';
    $route=work_management_route($sourceModule,$workType,$entityId,$requiredPermission,$priority,isset($payload['assigned_entity_id'])?(int)$payload['assigned_entity_id']:null,isset($payload['assigned_user_id'])?(int)$payload['assigned_user_id']:null,isset($payload['assigned_role_code'])?(string)$payload['assigned_role_code']:null,isset($payload['sla_minutes'])?(int)$payload['sla_minutes']:null);
    $now=date('Y-m-d H:i:s');$dueAt=$payload['due_at']??date('Y-m-d H:i:s',time()+((int)$route['sla_minutes']*60));$warningAt=$payload['warning_at']??date('Y-m-d H:i:s',strtotime((string)$dueAt)-min(240,max(30,(int)$route['sla_minutes']/4))*60);
    $item=work_management_save_item(['id'=>null,'work_number'=>work_management_next_number(),'source_key'=>$sourceKey,'source_module'=>$sourceModule,'source_type'=>$sourceType,'source_id'=>$sourceId,'entity_id'=>$entityId,'company_id'=>(int)$entity['company_id'],'process_instance_id'=>$payload['process_instance_id']??null,'step_instance_id'=>$payload['step_instance_id']??null,'work_type'=>$workType,'title'=>mb_substr($title,0,255),'description'=>mb_substr($description,0,5000),'priority'=>$route['priority'],'status'=>'open','assigned_entity_id'=>$route['assigned_entity_id'],'assigned_user_id'=>$route['assigned_user_id'],'assigned_role_code'=>$route['assigned_role_code'],'reviewer_id'=>$payload['reviewer_id']??$route['reviewer_id'],'approver_id'=>$payload['approver_id']??$route['approver_id'],'required_permission'=>$requiredPermission,'evidence_required'=>!empty($payload['evidence_required'])?1:0,'due_at'=>$dueAt,'warning_at'=>$warningAt,'escalation_level'=>0,'automation_rule_id'=>$automationRuleId,'created_by'=>(int)(current_user()['id']??0),'completed_by'=>null,'claimed_at'=>null,'started_at'=>null,'completed_at'=>null,'last_activity_at'=>$now,'lock_version'=>1,'created_at'=>$now,'updated_at'=>$now]);
    work_management_add_event((int)$item['id'],'work_created',null,'open',(string)$item['priority'],'Work created from '.$sourceModule.' '.$sourceType.' evidence.',['source_key'=>$sourceKey,'routing_rule_id'=>$route['rule']['id']??null,'automation_rule_id'=>$automationRuleId]);return$item;
}
function work_management_source_link(array$item):string
{
    $module=(string)$item['source_module'];$sourceId=(int)$item['source_id'];return match($module){'purchase_orders'=>app_url('purchase-orders.php?q='.rawurlencode((string)$sourceId)),'process_mapping'=>app_url('process-maps.php?tab=live&instance_id='.(int)($item['process_instance_id']??0)),'approvals'=>app_url('approvals.php?approval_id='.$sourceId.'#approval-'.$sourceId),'inventory'=>app_url('inventory-operations.php'),'contracts'=>app_url('contracts.php'),'accounts_payable'=>app_url('accounts-payable.php'),'supplier_portal'=>app_url('supplier-portal.php'),'scorecards'=>app_url('performance.php'),'savings'=>app_url('savings-realization.php'),'data_collection'=>app_url('data-collection.php'),default=>app_url('work-management.php?tab=team')};
}
function work_management_item_age_seconds(array$item):int{return max(0,time()-strtotime((string)$item['created_at']));}
function work_management_item_overdue(array$item):bool{return!in_array((string)$item['status'],['completed','cancelled'],true)&&!empty($item['due_at'])&&strtotime((string)$item['due_at'])<time();}
function work_management_item_can_act(array$item,?array$user=null):bool
{
    $user??=current_user();if(!$user||!work_management_user_can_receive($user,(int)$item['entity_id'],(string)$item['required_permission']))return false;if(work_management_can('administer',$user))return true;if((int)($item['assigned_user_id']??0)===(int)$user['id'])return true;return empty($item['assigned_user_id'])&&in_array((string)$item['assigned_role_code'],current_role_codes($user),true);
}
