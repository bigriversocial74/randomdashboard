<?php
declare(strict_types=1);
$root=dirname(__DIR__);$_SERVER['SCRIPT_NAME']='/gruber/supplier-portal/index.php';$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_USER_AGENT']='Supplier portal render test';
require $root.'/includes/app/bootstrap.php';$_SESSION['gruber_demo_mode']=true;$_SESSION['gruber_demo_state']=demo_default_state();$_SESSION['gruber_supplier_account_id']=1;$_SESSION['gruber_supplier_session_started_at']=time();$_SESSION['gruber_supplier_last_activity']=time();
ob_start();require $root.'/supplier-portal/index.php';$html=(string)ob_get_clean();
if($html===''||!str_contains($html,'<!doctype html>')||!str_contains($html,'Restricted Supplier Portal')){fwrite(STDERR,"Supplier portal did not render a complete page.\n");exit(1);}if(str_contains($html,'Desert Electrical Distribution')||str_contains($html,'Supplier Comparison')){fwrite(STDERR,"Supplier portal exposed cross-supplier or internal sourcing data.\n");exit(1);}if(preg_match('/(?:Fatal error|Uncaught (?:Error|Exception)|Warning:|Notice:)/i',$html)){fwrite(STDERR,"Supplier portal rendered a PHP error.\n");exit(1);}echo "Rendered isolated Supplier Portal successfully.\n";
