<?php
declare(strict_types=1);
require_once __DIR__.'/knowledge_adoption.php';

function knowledge_publish_governed_version(int$id,string$note):array{
 $row=knowledge_find_version($id);if(!$row||$row['status']!=='reviewed')throw new RuntimeException('Policy version requires independent review before publication.');
 foreach(knowledge_versions((int)$row['document_id'])as$prior){
  if((int)$prior['id']===(int)$row['id']||$prior['status']!=='published')continue;
  $prior['status']='superseded';$prior['effective_to']=date('Y-m-d');$prior['locked_at']=$prior['locked_at']?:date('Y-m-d H:i:s');
  knowledge_save_row('enterprise_policy_versions','enterprise_policy_versions','knowledge_demo_versions',$prior,['document_id','version_number','status','effective_from','effective_to','content','change_summary','content_hash','source_initiative_id','process_id','process_version_id','process_step_id','prepared_by','reviewed_by','approved_by','review_note','approval_note','submitted_at','reviewed_at','published_at','locked_at']);
 }
 return knowledge_publish_version($id,$note);
}
function knowledge_create_governed_campaign(array$record):array{
 $version=knowledge_find_version((int)($record['version_id']??0));if(!$version)throw new RuntimeException('Training policy version is outside the active scope.');
 $entityId=(int)($record['entity_id']??0);$approved=array_values(array_filter(knowledge_impacts((int)$version['id']),static fn(array$impact):bool=>(int)$impact['entity_id']===$entityId&&$impact['status']==='approved'&&!empty($impact['locked_at'])));
 if(!$approved)throw new RuntimeException('Training launch requires an approved, locked change-impact assessment for the target entity.');
 return knowledge_save_campaign($record);
}
function knowledge_validate_governed_attestation(int$id,string$note):array{
 $row=knowledge_find_attestation($id);$assignment=$row?knowledge_find_assignment((int)$row['assignment_id']):null;$campaign=$assignment?knowledge_find_campaign((int)$assignment['campaign_id']):null;$version=$campaign?knowledge_find_version((int)$campaign['version_id']):null;
 if(!$row||!$version||!hash_equals((string)$version['content_hash'],(string)$row['policy_hash']))throw new RuntimeException('Attestation policy hash no longer matches the assigned published version.');
 return knowledge_validate_attestation($id,$note);
}
function knowledge_generate_governed_manifest(int$versionId,string$note):array{
 $version=knowledge_find_version($versionId);if(!$version||$version['status']!=='published'||empty($version['locked_at']))throw new RuntimeException('Only immutable published versions may generate evidence manifests.');
 foreach(knowledge_campaigns($versionId)as$campaign)knowledge_snapshot_adoption((int)$campaign['id'],'Manifest-aligned adoption snapshot. '.trim($note));
 return knowledge_generate_manifest($versionId,$note);
}
