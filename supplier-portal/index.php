<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/supplier_portal.php';
require_once dirname(__DIR__).'/includes/app/supplier_portal_public.php';
$account=supplier_portal_require_account();$supplier=data_find('suppliers',(int)$account['supplier_id']);
$tab=query_string('tab','dashboard');if(!array_key_exists($tab,supplier_portal_public_tabs()))$tab='dashboard';
$pos=supplier_portal_account_purchase_orders($account);$selectedPoId=query_int('po_id',(int)($pos[0]['id']??0));$selectedPo=supplier_portal_account_po($account,$selectedPoId)??($pos[0]??null);$selectedPoId=(int)($selectedPo['id']??0);$poLines=$selectedPo?supplier_portal_account_po_lines($account,$selectedPoId):[];
$metrics=supplier_portal_account_metrics($account);$responses=supplier_portal_account_records($account,'po_responses');$asns=supplier_portal_account_records($account,'asns');$invoices=supplier_portal_account_records($account,'invoices');$documents=supplier_portal_account_records($account,'documents');$sourcing=supplier_portal_account_records($account,'sourcing');$quality=supplier_portal_account_records($account,'quality');$messages=supplier_portal_account_records($account,'messages');
supplier_portal_render_start(status_label($tab),$tab,$account);
?>
<?php require __DIR__.'/views/summary.php'; require __DIR__.'/views/'.$tab.'.php'; supplier_portal_render_end(); ?>
