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
    return $version;
}
