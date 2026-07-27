<?php
declare(strict_types=1);

function process_mapping_demo_processes(): array
{
    $rows=[];$now=date('Y-m-d H:i:s');$id=1;foreach(process_mapping_template_catalog() as$code=>$template){$rows[]=['id'=>$id,'process_code'=>strtoupper(substr(hash('sha256',$template['name']),0,8)),'template_code'=>$code,'process_name'=>$template['name'],'purpose'=>$template['purpose'],'category_code'=>$template['category'],'value_chain'=>$template['value_chain'],'owner_entity_id'=>1,'is_template'=>1,'status'=>'published','active_version_id'=>$id,'created_by'=>1,'created_at'=>$now,'updated_at'=>$now];$id++;}return$rows;
}
function process_mapping_demo_versions(): array
{
    $rows=[];$now=date('Y-m-d H:i:s');foreach(process_mapping_demo_processes() as$p)$rows[]=['id'=>$p['id'],'process_id'=>$p['id'],'version_number'=>'1.0','status'=>'published','effective_from'=>date('Y-m-d'),'effective_to'=>null,'prepared_by'=>1,'reviewed_by'=>3,'approved_by'=>6,'manifest_hash'=>hash('sha256',$p['template_code'].'|1.0'),'evidence_note'=>'Seeded governed process template for visual demonstration.','published_at'=>$now,'locked_at'=>$now,'created_at'=>$now,'updated_at'=>$now];return$rows;
}
function process_mapping_demo_lanes(): array
{
    $rows=[];$id=1;$versionId=1;foreach(process_mapping_template_catalog() as$template){foreach($template['lanes'] as$order=>$lane){$entityId=null;if(($lane['entity_code']??null)!==null){foreach(entity_system_all_entities() as$e)if((string)$e['entity_code']===(string)$lane['entity_code']){$entityId=(int)$e['id'];break;}}$rows[]=['id'=>$id++,'version_id'=>$versionId,'lane_code'=>$lane['code'],'lane_name'=>$lane['name'],'participant_type'=>$lane['participant_type'],'entity_id'=>$entityId,'role_code'=>$lane['role_code'],'sort_order'=>$order+1,'color_token'=>'lane-'.(($order%6)+1),'status'=>'active','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];}$versionId++;}return$rows;
}
function process_mapping_demo_steps(): array
{
    $rows=[];$id=1;$lanes=process_mapping_demo_lanes();$versionId=1;foreach(process_mapping_template_catalog() as$template){$laneMap=[];foreach($lanes as$l)if((int)$l['version_id']===$versionId)$laneMap[$l['lane_code']]=(int)$l['id'];foreach($template['steps'] as$order=>$step)$rows[]=['id'=>$id++,'version_id'=>$versionId,'lane_id'=>$laneMap[$step['lane']],'step_code'=>$step['code'],'step_name'=>$step['name'],'step_type'=>$step['type'],'description'=>$step['name'].' within '.$template['name'].'.','position_x'=>(int)$step['x'],'position_y'=>(int)$step['y'],'node_width'=>150,'node_height'=>72,'required_permission'=>$step['permission']??'platform.view','sla_minutes'=>(int)($step['sla']??0),'evidence_required'=>!empty($step['evidence'])?1:0,'canonical_record_type'=>$step['record']??'business_process','integration_event'=>$step['event']??'','automation_mode'=>($step['type']==='integration'?'adapter_gated':'human_supervised'),'sort_order'=>$order+1,'status'=>'active','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];$versionId++;}return$rows;
}
function process_mapping_demo_transitions(): array
{
    $rows=[];$id=1;$steps=process_mapping_demo_steps();$versionId=1;foreach(process_mapping_template_catalog() as$template){$map=[];foreach($steps as$s)if((int)$s['version_id']===$versionId)$map[$s['step_code']]=(int)$s['id'];foreach($template['transitions'] as$order=>$t)$rows[]=['id'=>$id++,'version_id'=>$versionId,'from_step_id'=>$map[$t['from']],'to_step_id'=>$map[$t['to']],'transition_code'=>'T'.($order+1),'condition_label'=>$t['label'],'is_default'=>!empty($t['default'])?1:0,'is_exception_path'=>!empty($t['exception'])?1:0,'sort_order'=>$order+1,'status'=>'active','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];$versionId++;}return$rows;
}
function process_mapping_demo_controls(): array
{
    $rows=[];$id=1;foreach(process_mapping_demo_steps() as$s){if(empty($s['evidence_required'])&&$s['step_type']!=='approval'&&$s['step_type']!=='control')continue;$rows[]=['id'=>$id++,'step_id'=>$s['id'],'control_type'=>$s['step_type']==='approval'?'approval':'evidence','rule_code'=>'CTRL-'.$s['step_code'],'control_description'=>'Require evidence and authorized completion before the process advances.','severity'=>$s['step_type']==='approval'?'high':'medium','separation_role'=>$s['step_type']==='approval'?'preparer':'','evidence_type'=>'record_reference','status'=>'active','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];}return$rows;
}
function process_mapping_demo_integrations(): array
{
    $rows=[];$id=1;foreach(process_mapping_demo_steps() as$s){if($s['integration_event']==='')continue;$rows[]=['id'=>$id++,'step_id'=>$s['id'],'connection_id'=>null,'direction'=>'outbound','event_type'=>$s['integration_event'],'record_type'=>$s['canonical_record_type'],'sync_mode'=>'event','required_acknowledgment'=>1,'timeout_minutes'=>120,'status'=>'active','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];}return$rows;
}
function process_mapping_demo_assignments(): array
{
    $rows=[];$id=1;foreach(process_mapping_demo_processes() as$p){foreach(entity_system_bindings() as$b)$rows[]=['id'=>$id++,'process_id'=>$p['id'],'entity_id'=>$b['entity_id'],'assignment_type'=>'applicable','module_code'=>$p['category_code'],'inheritance_mode'=>'template','status'=>'active','effective_from'=>date('Y-m-d'),'effective_to'=>null,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];}return$rows;
}
function process_mapping_demo_instances(): array
{
    $steps=array_values(array_filter(process_mapping_demo_steps(),fn($s)=>(int)$s['version_id']===1));$current=$steps[4]??$steps[0];return[['id'=>1,'process_id'=>1,'version_id'=>1,'instance_number'=>'P2P-DEMO-001','entity_id'=>101,'canonical_record_type'=>'purchase_order','canonical_record_id'=>2,'instance_title'=>'Communications cable replenishment purchase','status'=>'in_progress','current_step_id'=>$current['id'],'started_by'=>5,'started_at'=>date('Y-m-d H:i:s',strtotime('-2 days')),'due_at'=>date('Y-m-d H:i:s',strtotime('+2 days')),'completed_at'=>null,'cycle_seconds'=>0,'process_data_json'=>json_encode(['supplier_id'=>3,'purchase_order_id'=>2,'company_id'=>1]),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]];
}
function process_mapping_demo_step_instances(): array
{
    $rows=[];$steps=array_values(array_filter(process_mapping_demo_steps(),fn($s)=>(int)$s['version_id']===1));$lanes=process_mapping_demo_lanes();foreach($steps as$i=>$s){$status=$i<4?'completed':($i===4?'active':'pending');$lane=null;foreach($lanes as$l)if((int)$l['id']===(int)$s['lane_id']){$lane=$l;break;}$assigned=null;if($lane&&$lane['participant_type']!=='external')$assigned=in_array($lane['lane_code'],['requesting_business','receiving_business'],true)?101:((int)($lane['entity_id']??0)?:101);$rows[]=['id'=>$i+1,'instance_id'=>1,'step_id'=>$s['id'],'assigned_entity_id'=>$assigned,'assigned_user_id'=>$i<4?5:3,'status'=>$status,'started_at'=>$i<=4?date('Y-m-d H:i:s',strtotime('-'.(48-$i*8).' hours')):null,'due_at'=>$s['sla_minutes']>0?date('Y-m-d H:i:s',strtotime('+'.max(1,$s['sla_minutes']).' minutes')):null,'completed_at'=>$i<4?date('Y-m-d H:i:s',strtotime('-'.(40-$i*8).' hours')):null,'elapsed_seconds'=>$i<4?28800:0,'evidence_note'=>$i<4?'Seeded completion evidence.':'','output_json'=>'{}','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];}return$rows;
}
function process_mapping_demo_exceptions(): array{return[['id'=>1,'instance_id'=>1,'step_instance_id'=>3,'exception_type'=>'budget_variance','severity'=>'medium','status'=>'resolved','owner_id'=>5,'reviewer_id'=>6,'exception_description'=>'Requested quantity exceeded the original quarterly plan.','resolution_note'=>'Business owner approved the documented demand variance.','opened_at'=>date('Y-m-d H:i:s',strtotime('-36 hours')),'resolved_at'=>date('Y-m-d H:i:s',strtotime('-32 hours')),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]];}
function process_mapping_demo_events(): array{return[['id'=>1,'process_id'=>1,'version_id'=>1,'instance_id'=>1,'step_id'=>1,'event_type'=>'instance_started','from_status'=>null,'to_status'=>'in_progress','severity'=>'low','evidence_note'=>'Seeded live process instance.','created_by'=>5,'created_at'=>date('Y-m-d H:i:s',strtotime('-2 days'))]];}
