<?php
declare(strict_types=1);
function entity_system_export_csv():never
{
    require_permission('reports.export');$rows=[['Entity code','Entity name','Type','Parent','Company ID','Status','Currency','Timezone']];$all=entity_system_entities();foreach($all as$e){$parent=$e['parent_entity_id']?entity_system_find($all,(int)$e['parent_entity_id']):null;$rows[]=[spreadsheet_safe($e['entity_code']),spreadsheet_safe($e['entity_name']),spreadsheet_safe($e['entity_type']),spreadsheet_safe($parent['entity_name']??''),(string)($e['company_id']??''),spreadsheet_safe($e['status']),spreadsheet_safe($e['base_currency']),spreadsheet_safe($e['timezone'])];}header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="business-entities-'.date('Ymd').'.csv"');$o=fopen('php://output','wb');foreach($rows as$r)fputcsv($o,$r);fclose($o);exit;
}
