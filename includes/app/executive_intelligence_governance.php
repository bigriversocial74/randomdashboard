<?php
declare(strict_types=1);
require_once __DIR__.'/executive_intelligence.php';

function executive_intelligence_review_version(int$versionId,string$note):array
{
    require_permission('executive_intelligence.review');
    $version=executive_intelligence_find_version($versionId);
    if(!$version||($version['status']??'')!=='draft')throw new RuntimeException('KPI version is not awaiting independent review.');
    if((int)$version['reviewed_by']!==(int)(current_user()['id']??0))throw new RuntimeException('Only the assigned independent reviewer may review this KPI version.');
    if(in_array((int)current_user()['id'],[(int)$version['prepared_by'],(int)$version['approved_by']],true))throw new RuntimeException('KPI review must remain separate from preparation and approval.');
    if(trim($note)==='')throw new RuntimeException('KPI review evidence is required.');
    $version['status']='reviewed';
    $version['review_note']=mb_substr(trim($note),0,5000);
    return executive_intelligence_save_version($version);
}

function executive_intelligence_assert_publishable_version(int$versionId):array
{
    $version=executive_intelligence_find_version($versionId);
    if(!$version||($version['status']??'')!=='reviewed')throw new RuntimeException('KPI version requires independent review before publication.');
    if(trim((string)($version['review_note']??''))==='')throw new RuntimeException('KPI review evidence is missing.');
    if((int)$version['approved_by']!==(int)(current_user()['id']??0))throw new RuntimeException('Only the assigned independent approver may publish this KPI version.');
    executive_intelligence_separated_users([(int)$version['prepared_by'],(int)$version['reviewed_by'],(int)$version['approved_by']]);
    return $version;
}

function executive_intelligence_publish_governed_version(int$versionId,string$note):array
{
    executive_intelligence_assert_publishable_version($versionId);
    if(trim($note)==='')throw new RuntimeException('KPI approval evidence is required.');
    return executive_intelligence_publish_version($versionId,$note);
}

function executive_intelligence_create_goal_link(array$record):array
{
    $goal=executive_intelligence_find_goal((int)($record['goal_id']??0));
    if(!$goal)throw new RuntimeException('Goal link is outside the active scope.');
    $type=(string)($record['link_type']??'');
    $sourceId=(int)($record['source_id']??0);
    if($sourceId<=0)throw new RuntimeException('Goal links require a valid governed source record.');
    if($type==='kpi_definition'){
        $definition=executive_intelligence_find_definition($sourceId);
        if(!$definition)throw new RuntimeException('KPI definition is outside the active scope.');
        $goalCompany=(int)($goal['company_id']??0);$definitionCompany=(int)($definition['company_id']??0);
        if($goalCompany>0&&$definitionCompany>0&&$goalCompany!==$definitionCompany)throw new RuntimeException('A company goal cannot link to a sibling-company KPI.');
    }elseif($type==='process'){
        executive_intelligence_validate_process_binding($sourceId,null);
    }elseif($type==='process_step'){
        $step=process_mapping_find_step($sourceId);
        if(!$step)throw new RuntimeException('Process step is outside the active scope.');
    }else throw new RuntimeException('Unsupported governed goal-link type.');
    return executive_intelligence_save_goal_link($record);
}

function executive_intelligence_create_governed_review(int$scorecardId,int$entityId,string$reviewType,string$start,string$end,string$agenda,int$reviewerId,int$approverId):array
{
    $scorecard=executive_intelligence_find_scorecard($scorecardId);
    if(!$scorecard)throw new RuntimeException('Scorecard is outside the active scope.');
    $scorecardEntity=(int)($scorecard['entity_id']??0);
    if($scorecardEntity>0&&$scorecardEntity!==$entityId)throw new RuntimeException('A company scorecard cannot be reviewed under another operating entity.');
    return executive_intelligence_create_review($scorecardId,$entityId,$reviewType,$start,$end,$agenda,$reviewerId,$approverId);
}

function executive_intelligence_record_governed_decision(int$reviewId,?int$goalId,?int$definitionId,string$type,string$title,string$description,int$ownerId,string$dueAt,bool$createWork):array
{
    $review=executive_intelligence_find_review($reviewId);
    if(!$review)throw new RuntimeException('Executive review is outside the active scope.');
    $userId=(int)(current_user()['id']??0);
    $participants=array_map('intval',[(int)$review['prepared_by'],(int)$review['reviewed_by'],(int)$review['approved_by']]);
    if(!in_array($userId,$participants,true)&&!can('executive_intelligence.administer'))throw new RuntimeException('Only a governed review participant may record its decisions.');
    $reviewCompany=(int)($review['company_id']??0);
    if($goalId){$goal=executive_intelligence_find_goal($goalId);if(!$goal)throw new RuntimeException('Decision goal is outside the active scope.');$goalCompany=(int)($goal['company_id']??0);if($goalCompany>0&&$goalCompany!==$reviewCompany)throw new RuntimeException('Decision goal belongs to a different operating company.');}
    if($definitionId){$definition=executive_intelligence_find_definition($definitionId);if(!$definition)throw new RuntimeException('Decision KPI is outside the active scope.');$definitionCompany=(int)($definition['company_id']??0);if($definitionCompany>0&&$definitionCompany!==$reviewCompany)throw new RuntimeException('Decision KPI belongs to a different operating company.');}
    if(in_array($ownerId,array_map('intval',[(int)$review['reviewed_by'],(int)$review['approved_by']]),true))throw new RuntimeException('Decision execution ownership must remain separate from review and approval.');
    if(trim($title)===''||trim($description)==='')throw new RuntimeException('Decision title and evidence are required.');
    if(strtotime($dueAt)<=time())throw new RuntimeException('Decision due date must be in the future.');
    return executive_intelligence_record_decision($reviewId,$goalId,$definitionId,$type,$title,$description,$ownerId,$dueAt,$createWork);
}

function executive_intelligence_assert_published_scorecard_immutable(array$before,array$after):void
{
    if(($before['status']??'')!=='published'&&empty($before['locked_at']))return;
    foreach(['scorecard_number','scorecard_name','entity_id','company_id','period_start','period_end','status','on_target_count','warning_count','critical_count','results_json','executive_summary','prepared_by','reviewed_by','approved_by','review_note','approval_note','published_at','locked_at']as$field){
        if((string)($before[$field]??'')!==(string)($after[$field]??''))throw new RuntimeException('Published scorecards are immutable.');
    }
}

function executive_intelligence_assert_published_review_immutable(array$before,array$after):void
{
    if(($before['status']??'')!=='published'&&empty($before['locked_at']))return;
    foreach(['review_number','scorecard_id','entity_id','company_id','review_type','meeting_start','meeting_end','status','agenda_json','summary_text','prepared_by','reviewed_by','approved_by','review_note','approval_note','calendar_event_id','published_at','locked_at']as$field){
        if((string)($before[$field]??'')!==(string)($after[$field]??''))throw new RuntimeException('Published executive reviews are immutable.');
    }
}
