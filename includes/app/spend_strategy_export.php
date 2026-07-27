<?php
declare(strict_types=1);
function spend_strategy_export_csv(array $cube): never
{
    require_permission('strategy.export');
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="enterprise-spend-strategy-'.date('Ymd-His').'.csv"');
    $out=fopen('php://output','wb');fputcsv($out,['Company','Category','Supplier','Ordered spend','Received spend','Invoiced spend','Open commitments','Contracted spend','Off-contract spend','Emergency spend','Addressable spend','High-risk spend']);
    foreach($cube as $r)fputcsv($out,array_map(static fn($v)=>spend_strategy_csv_cell((string)$v),[data_company_name($r['company_id']),data_find('categories',(int)($r['category_id']??0))['name']??'Unclassified',data_supplier_name($r['supplier_id']),number_format((float)$r['ordered_spend'],2,'.',''),number_format((float)$r['received_spend'],2,'.',''),number_format((float)$r['invoiced_spend'],2,'.',''),number_format((float)$r['open_commitments'],2,'.',''),number_format((float)$r['contracted_spend'],2,'.',''),number_format((float)$r['off_contract_spend'],2,'.',''),number_format((float)$r['emergency_spend'],2,'.',''),number_format((float)$r['addressable_spend'],2,'.',''),number_format((float)$r['high_risk_spend'],2,'.','')]));
    fclose($out);exit;
}
