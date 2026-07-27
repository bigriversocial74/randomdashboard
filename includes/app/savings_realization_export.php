<?php
declare(strict_types=1);

function savings_realization_csv_cell(mixed $value): string
{
    $value=(string)$value;
    if ($value!=='' && in_array($value[0],['=','+','-','@'],true)) $value="'".$value;
    return $value;
}

function savings_realization_export_csv(array $opportunity,array $metrics,array $baselines,array $periods,array $evidence,array $validations,array $leakage,array $events): never
{
    require_permission('reports.export');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="savings-realization-'.$opportunity['opportunity_number'].'.csv"');
    $out=fopen('php://output','wb');
    fputcsv($out,['Section','Reference','Status','Period / date','Gross / amount','Net / recovered','Owner / reviewer','Evidence']);
    foreach ($baselines as $row) fputcsv($out,array_map('savings_realization_csv_cell',['Baseline','Version '.$row['version_number'],$row['status'],$row['period_start'].' to '.$row['period_end'],$row['baseline_total_cost'],$row['baseline_unit_cost'],data_user_name($row['reviewer_id']),$row['evidence_note']]));
    foreach ($periods as $row) fputcsv($out,array_map('savings_realization_csv_cell',['Realization period',$row['fiscal_period'],$row['status'],$row['period_start'].' to '.$row['period_end'],$row['gross_realized_value'],$row['net_realized_value'],data_user_name($row['reviewer_id']),$row['evidence_note']]));
    foreach ($evidence as $row) fputcsv($out,array_map('savings_realization_csv_cell',['Evidence',$row['evidence_reference'],$row['status'],$row['evidence_date'],$row['evidence_amount'],'',data_user_name($row['verified_by']??null),$row['evidence_note']]));
    foreach ($validations as $row) fputcsv($out,array_map('savings_realization_csv_cell',['Finance validation',$row['validation_number'],$row['decision'],$row['decided_at'],$row['validated_net_value'],$row['completeness_score'].'%',data_user_name($row['reviewer_id']),$row['comments']]));
    foreach ($leakage as $row) fputcsv($out,array_map('savings_realization_csv_cell',['Leakage',status_label($row['leakage_type']),$row['status'],$row['detected_date'],$row['amount'],$row['recovered_amount'],data_user_name($row['owner_id']),$row['evidence_note']]));
    foreach ($events as $row) fputcsv($out,array_map('savings_realization_csv_cell',['Event',status_label($row['event_type']),$row['to_status'],$row['created_at'],$row['value_amount'],'',data_user_name($row['created_by']),$row['evidence_note']]));
    fputcsv($out,['Portfolio metric','Finance validated','','',$metrics['finance_validated'],'','','']);
    fclose($out);
    exit;
}
