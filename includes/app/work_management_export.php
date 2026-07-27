<?php
declare(strict_types=1);

function work_management_spreadsheet_safe(mixed $value): string
{
    $value=(string)$value;
    if($value!==''&&preg_match('/^[=+\-@]/',$value))return"'".$value;
    return$value;
}

function work_management_export_csv(string$type='items'):never
{
    work_management_require('export');$rows=[];
    if($type==='automation'){
        $rows[]=['Rule code','Rule name','Trigger','Source module','Entity','Status','Version','Prepared by','Reviewed by','Approved by','Locked'];
        foreach(work_management_automation_rules()as$rule){$version=work_management_rule_version($rule);$rows[]=array_map('work_management_spreadsheet_safe',[$rule['rule_code'],$rule['rule_name'],$rule['trigger_event'],$rule['source_module'],$rule['entity_id']?(work_management_entity((int)$rule['entity_id'])['entity_name']??''):'Enterprise',$rule['status'],$version['version_number']??'',data_user_name($version['prepared_by']??null),data_user_name($version['reviewed_by']??null),data_user_name($version['approved_by']??null),$version['locked_at']??'']);}
    }elseif($type==='events'){
        $rows[]=['Work number','Event','From status','To status','Actor','Assigned entity','Assigned user','Severity','Evidence','Created'];
        foreach(work_management_events()as$event){$item=work_management_find_item((int)$event['work_item_id']);$entity=!empty($event['assigned_entity_id'])?work_management_entity((int)$event['assigned_entity_id']):null;$rows[]=array_map('work_management_spreadsheet_safe',[$item['work_number']??'',$event['event_type'],$event['from_status']??'',$event['to_status']??'',data_user_name($event['actor_user_id']??null),$entity['entity_name']??'',data_user_name($event['assigned_user_id']??null),$event['severity'],$event['evidence_note'],$event['created_at']]);}
    }else{
        $rows[]=['Work number','Company','Origin entity','Assigned entity','Source','Source type','Source ID','Work type','Title','Priority','Status','Owner','Role','Reviewer','Approver','Permission','Due','Escalation level','Created','Completed'];
        foreach(work_management_items()as$item){$origin=work_management_entity((int)$item['entity_id']);$assigned=!empty($item['assigned_entity_id'])?work_management_entity((int)$item['assigned_entity_id']):null;$rows[]=array_map('work_management_spreadsheet_safe',[$item['work_number'],data_company_name((int)$item['company_id']),$origin['entity_name']??'',$assigned['entity_name']??'',$item['source_module'],$item['source_type'],$item['source_id'],$item['work_type'],$item['title'],$item['priority'],$item['status'],data_user_name($item['assigned_user_id']??null),$item['assigned_role_code'],data_user_name($item['reviewer_id']??null),data_user_name($item['approver_id']??null),$item['required_permission'],$item['due_at'],$item['escalation_level'],$item['created_at'],$item['completed_at']??'']);}
    }
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="section26-'.$type.'-'.date('Ymd').'.csv"');$output=fopen('php://output','wb');foreach($rows as$row)fputcsv($output,$row);fclose($output);exit;
}
