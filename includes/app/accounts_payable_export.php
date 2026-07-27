<?php
declare(strict_types=1);

function accounts_payable_csv_cell(mixed$value):string
{
    $value=(string)$value;if(preg_match('/^[=+\-@]/',$value))$value="'".$value;return$value;
}
function accounts_payable_export_csv(array$batches,array$schedules,array$credits,array$events):never
{
    require_permission('accounts_payable.export');
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="accounts-payable-governance-'.date('Ymd-His').'.csv"');
    $out=fopen('php://output','wb');fputcsv($out,['Record type','Reference','Company','Supplier','Status','Date','Gross amount','Credit amount','Net amount','Evidence']);
    foreach($batches as$r)fputcsv($out,array_map('accounts_payable_csv_cell',['Payment batch',$r['batch_number'],data_company_name($r['company_id']),'',status_label($r['status']),$r['execution_date'],$r['gross_amount'],$r['credit_amount'],$r['net_amount'],$r['evidence_note']]));
    foreach($schedules as$r)fputcsv($out,array_map('accounts_payable_csv_cell',['Payment schedule',$r['schedule_number'],data_company_name($r['company_id']),data_supplier_name($r['supplier_id']),status_label($r['status']),$r['scheduled_date'],$r['gross_amount'],$r['credit_amount'],$r['net_amount'],$r['evidence_note']]));
    foreach($credits as$r)fputcsv($out,array_map('accounts_payable_csv_cell',['Supplier credit',$r['credit_number'],data_company_name($r['company_id']),data_supplier_name($r['supplier_id']),status_label($r['status']),$r['credit_date'],$r['original_amount'],$r['applied_amount'],$r['remaining_amount'],$r['evidence_note']]));
    foreach($events as$r)fputcsv($out,array_map('accounts_payable_csv_cell',['Governance event',$r['event_type'],data_company_name($r['company_id']),'',status_label($r['to_status']??$r['from_status']??''),$r['created_at'],$r['amount'],0,$r['amount'],$r['evidence_note']]));
    fclose($out);exit;
}
