<?php
declare(strict_types=1);

function work_management_tables(): array
{
    return ['enterprise_work_items','enterprise_work_item_events','enterprise_work_routing_rules','enterprise_automation_rules','enterprise_automation_rule_versions','enterprise_automation_runs','enterprise_escalation_policies','enterprise_escalation_events'];
}

function work_management_tables_ready(): bool
{
    if (data_is_demo()) return true;
    $pdo=production_database_connection();
    if(!$pdo)return false;
    try{$tables=work_management_tables();$statement=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('.implode(',',array_fill(0,count($tables),'?')).')');$statement->execute($tables);return(int)$statement->fetchColumn()===count($tables);}catch(Throwable){return false;}
}

function work_management_require_tables(): void
{
    if(!work_management_tables_ready())throw new RuntimeException('Import the Section 26 migration before using Production Data work-management writes.');
}

function work_management_demo_collection(string$key,callable$seed):array
{
    if(!isset($_SESSION['gruber_demo_state'][$key]))$_SESSION['gruber_demo_state'][$key]=$seed();
    return array_values($_SESSION['gruber_demo_state'][$key]);
}

function work_management_demo_save(string$key,array$record,callable$seed):array
{
    $rows=work_management_demo_collection($key,$seed);$id=(int)($record['id']??0);
    if($id<=0){$id=max([0,...array_map('intval',array_column($rows,'id'))])+1;$record['id']=$id;$rows[]=$record;}
    else{$found=false;foreach($rows as$index=>$row){if((int)$row['id']!==$id)continue;$rows[$index]=array_replace($row,$record);$record=$rows[$index];$found=true;break;}if(!$found)$rows[]=$record;}
    $_SESSION['gruber_demo_state'][$key]=array_values($rows);return$record;
}

function work_management_raw_rows(string$table,string$key,callable$seed,string$order='id'):array
{
    if(data_is_demo())return work_management_demo_collection($key,$seed);
    if(!work_management_tables_ready())return[];
    return production_database_connection()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();
}

function work_management_visible_origin_entity_ids():array{return entity_system_visible_entity_ids();}
function work_management_items():array
{
    $visible=array_fill_keys(work_management_visible_origin_entity_ids(),true);
    return array_values(array_filter(work_management_raw_rows('enterprise_work_items','enterprise_work_items','work_management_demo_items','priority DESC,due_at,id'),static fn(array$item):bool=>isset($visible[(int)$item['entity_id']])));
}
function work_management_visible_item_ids():array{return array_map('intval',array_column(work_management_items(),'id'));}
function work_management_events(?int$workItemId=null):array
{
    $visible=array_fill_keys(work_management_visible_item_ids(),true);
    $rows=array_values(array_filter(work_management_raw_rows('enterprise_work_item_events','enterprise_work_item_events','work_management_demo_events','created_at DESC,id DESC'),static fn(array$event):bool=>isset($visible[(int)$event['work_item_id']])));
    return$workItemId===null?$rows:array_values(array_filter($rows,static fn(array$event):bool=>(int)$event['work_item_id']===$workItemId));
}
function work_management_routing_rules():array
{
    $visible=array_fill_keys(work_management_visible_origin_entity_ids(),true);
    return array_values(array_filter(work_management_raw_rows('enterprise_work_routing_rules','enterprise_work_routing_rules','work_management_demo_routing_rules','sort_order,id'),static fn(array$rule):bool=>empty($rule['entity_id'])||isset($visible[(int)$rule['entity_id']])));
}
function work_management_automation_rules():array
{
    $visible=array_fill_keys(work_management_visible_origin_entity_ids(),true);
    return array_values(array_filter(work_management_raw_rows('enterprise_automation_rules','enterprise_automation_rules','work_management_demo_automation_rules','rule_name,id'),static fn(array$rule):bool=>empty($rule['entity_id'])||isset($visible[(int)$rule['entity_id']])));
}
function work_management_visible_rule_ids():array{return array_map('intval',array_column(work_management_automation_rules(),'id'));}
function work_management_automation_versions(?int$ruleId=null):array
{
    $visible=array_fill_keys(work_management_visible_rule_ids(),true);
    $rows=array_values(array_filter(work_management_raw_rows('enterprise_automation_rule_versions','enterprise_automation_rule_versions','work_management_demo_automation_versions','rule_id,id DESC'),static fn(array$version):bool=>isset($visible[(int)$version['rule_id']])));
    return$ruleId===null?$rows:array_values(array_filter($rows,static fn(array$version):bool=>(int)$version['rule_id']===$ruleId));
}
function work_management_automation_runs():array
{
    $rules=array_fill_keys(work_management_visible_rule_ids(),true);
    return array_values(array_filter(work_management_raw_rows('enterprise_automation_runs','enterprise_automation_runs','work_management_demo_automation_runs','created_at DESC,id DESC'),static fn(array$run):bool=>isset($rules[(int)$run['rule_id']])));
}
function work_management_escalation_policies():array{return work_management_raw_rows('enterprise_escalation_policies','enterprise_escalation_policies','work_management_demo_escalation_policies','id');}
function work_management_escalation_events():array
{
    $items=array_fill_keys(work_management_visible_item_ids(),true);
    return array_values(array_filter(work_management_raw_rows('enterprise_escalation_events','enterprise_escalation_events','work_management_demo_escalation_events','triggered_at DESC,id DESC'),static fn(array$event):bool=>isset($items[(int)$event['work_item_id']])));
}
function work_management_find(array$rows,int$id):?array{foreach($rows as$row)if((int)$row['id']===$id)return$row;return null;}
function work_management_find_item(int$id):?array{return work_management_find(work_management_items(),$id);}
function work_management_find_rule(int$id):?array{return work_management_find(work_management_automation_rules(),$id);}
function work_management_find_version(int$id):?array{return work_management_find(work_management_automation_versions(),$id);}
function work_management_find_policy(int$id):?array{return work_management_find(work_management_escalation_policies(),$id);}
function work_management_find_item_by_source_key(string$sourceKey):?array{foreach(work_management_items()as$item)if(hash_equals((string)$item['source_key'],$sourceKey))return$item;return null;}

function work_management_save_row(string$table,string$key,callable$seed,array$record,array$fields):array
{
    work_management_require_tables();$record['created_at']=$record['created_at']??date('Y-m-d H:i:s');$record['updated_at']=date('Y-m-d H:i:s');
    if(data_is_demo())return work_management_demo_save($key,$record,$seed);
    $pdo=production_database_connection();if(!$pdo)throw new RuntimeException('Production database connection unavailable.');$id=(int)($record['id']??0);$values=[];
    foreach($fields as$field){$value=$record[$field]??null;if(is_array($value))$value=json_encode($value,JSON_UNESCAPED_SLASHES);$values[]=$value;}
    if($id>0){$values[]=$id;$pdo->prepare('UPDATE '.$table.' SET '.implode(',',array_map(static fn(string$field):string=>$field.'=?',$fields)).',updated_at=NOW() WHERE id=?')->execute($values);}
    else{$pdo->prepare('INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')')->execute($values);$id=(int)$pdo->lastInsertId();}
    $record['id']=$id;return$record;
}

function work_management_save_item(array$record):array
{
    work_management_require_tables();$entity=entity_system_find_entity((int)$record['entity_id']);
    if(!$entity||(int)($entity['company_id']??0)<=0)throw new RuntimeException('Work items require an operating entity within the active company scope.');
    if(!empty($record['assigned_entity_id'])&&!entity_system_find_entity((int)$record['assigned_entity_id']))throw new RuntimeException('The assigned entity is outside the active company scope.');
    $record['company_id']=(int)$entity['company_id'];$record['last_activity_at']=$record['last_activity_at']??date('Y-m-d H:i:s');$record['lock_version']=max(1,(int)($record['lock_version']??1));
    $fields=['work_number','source_key','source_module','source_type','source_id','entity_id','company_id','process_instance_id','step_instance_id','work_type','title','description','priority','status','assigned_entity_id','assigned_user_id','assigned_role_code','reviewer_id','approver_id','required_permission','evidence_required','due_at','warning_at','escalation_level','automation_rule_id','created_by','completed_by','claimed_at','started_at','completed_at','last_activity_at','lock_version'];
    if(data_is_demo()||empty($record['id']))return work_management_save_row('enterprise_work_items','enterprise_work_items','work_management_demo_items',$record,$fields);
    $pdo=production_database_connection();if(!$pdo)throw new RuntimeException('Production database connection unavailable.');$expectedVersion=(int)$record['lock_version'];$record['lock_version']=$expectedVersion+1;$values=[];foreach($fields as$field)$values[]=$record[$field]??null;$values[]=(int)$record['id'];$values[]=$expectedVersion;
    $statement=$pdo->prepare('UPDATE enterprise_work_items SET '.implode(',',array_map(static fn(string$field):string=>$field.'=?',$fields)).',updated_at=NOW() WHERE id=? AND lock_version=?');$statement->execute($values);
    if($statement->rowCount()!==1)throw new RuntimeException('This work item changed in another session. Reload before applying another action.');return$record;
}

function work_management_add_event(int$workItemId,string$eventType,?string$fromStatus,?string$toStatus,string$severity,string$evidenceNote,array$metadata=[]):array
{
    work_management_require_tables();$item=work_management_find_item($workItemId);if(!$item)throw new RuntimeException('Work item not found or outside the active company scope.');
    $record=['id'=>null,'work_item_id'=>$workItemId,'event_type'=>$eventType,'from_status'=>$fromStatus,'to_status'=>$toStatus,'actor_user_id'=>(int)(current_user()['id']??0),'assigned_entity_id'=>$item['assigned_entity_id']??null,'assigned_user_id'=>$item['assigned_user_id']??null,'severity'=>$severity,'evidence_note'=>mb_substr(trim($evidenceNote),0,5000),'metadata_json'=>json_encode($metadata,JSON_UNESCAPED_SLASHES),'created_at'=>date('Y-m-d H:i:s')];
    if(data_is_demo())return work_management_demo_save('enterprise_work_item_events',$record,'work_management_demo_events');
    $pdo=production_database_connection();$pdo->prepare('INSERT INTO enterprise_work_item_events(work_item_id,event_type,from_status,to_status,actor_user_id,assigned_entity_id,assigned_user_id,severity,evidence_note,metadata_json) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$record['work_item_id'],$record['event_type'],$record['from_status'],$record['to_status'],$record['actor_user_id'],$record['assigned_entity_id'],$record['assigned_user_id'],$record['severity'],$record['evidence_note'],$record['metadata_json']]);$record['id']=(int)$pdo->lastInsertId();return$record;
}

function work_management_save_routing_rule(array$record):array{return work_management_save_row('enterprise_work_routing_rules','enterprise_work_routing_rules','work_management_demo_routing_rules',$record,['rule_code','rule_name','source_module','work_type','entity_id','assigned_role_code','shared_service_entity_code','priority_override','sla_minutes','reviewer_role_code','approver_role_code','status','sort_order','evidence_note','prepared_by','reviewed_by','approved_by','effective_from','effective_to','locked_at']);}
function work_management_save_automation_rule(array$record):array
{
    if(!empty($record['entity_id'])&&!entity_system_find_entity((int)$record['entity_id']))throw new RuntimeException('Automation rule entity is outside the active company scope.');
    return work_management_save_row('enterprise_automation_rules','enterprise_automation_rules','work_management_demo_automation_rules',$record,['rule_code','rule_name','trigger_event','source_module','entity_id','status','active_version_id','created_by']);
}
function work_management_save_automation_version(array$record):array
{
    $existing=!empty($record['id'])?work_management_find_version((int)$record['id']):null;
    if($existing&&(($existing['status']??'')==='published'||!empty($existing['locked_at']))){foreach(['rule_id','version_number','condition_json','action_json','idempotency_window_seconds','prepared_by','reviewed_by','approved_by','published_at','locked_at']as$field)if((string)($existing[$field]??'')!==(string)($record[$field]??''))throw new RuntimeException('Published automation-rule versions are immutable; create a new draft version.');}
    return work_management_save_row('enterprise_automation_rule_versions','enterprise_automation_rule_versions','work_management_demo_automation_versions',$record,['rule_id','version_number','status','condition_json','action_json','idempotency_window_seconds','prepared_by','reviewed_by','approved_by','review_note','approval_note','evidence_note','published_at','locked_at']);
}
function work_management_save_automation_run(array$record):array{return work_management_save_row('enterprise_automation_runs','enterprise_automation_runs','work_management_demo_automation_runs',$record,['rule_id','version_id','source_key','idempotency_key','status','matched_count','created_work_item_id','result_json','error_message','started_at','completed_at']);}
function work_management_save_escalation_policy(array$record):array{return work_management_save_row('enterprise_escalation_policies','enterprise_escalation_policies','work_management_demo_escalation_policies',$record,['policy_code','policy_name','priority','warning_minutes','level1_minutes','level2_minutes','level3_minutes','backup_role_code','executive_role_code','status','evidence_note','created_by','reviewed_by','approved_by']);}
function work_management_save_escalation_event(array$record):array
{
    if(!work_management_find_item((int)$record['work_item_id']))throw new RuntimeException('Escalation work item is outside the active company scope.');
    return work_management_save_row('enterprise_escalation_events','enterprise_escalation_events','work_management_demo_escalation_events',$record,['work_item_id','policy_id','from_level','to_level','triggered_at','action_json','idempotency_key','created_by']);
}
