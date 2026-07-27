<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/work-management.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Section 26 render test';
require $root.'/includes/app/bootstrap.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);}
ob_start();require $root.'/app/work-management.php';$html=(string)ob_get_clean();
foreach(['<!doctype html>','Enterprise Work Command Center','What needs attention now','Command Center','Automation','SLA compliance','Analyze in Agent']as$needle){if(!str_contains($html,$needle)){fwrite(STDERR,"Section 26 render missing {$needle}.\n");exit(1);}}
if(preg_match('/(?:Fatal error|Uncaught (?:Error|Exception)|Warning:|Notice:)/i',$html)){fwrite(STDERR,"Section 26 workspace rendered a PHP error.\n");exit(1);}
echo "Section 26 work-management workspace rendered successfully.\n";
