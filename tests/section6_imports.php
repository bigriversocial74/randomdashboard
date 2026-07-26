<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$_SERVER['SCRIPT_NAME']='/gruber/app/imports.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
require $root.'/includes/app/bootstrap.php';
require_once $root.'/includes/data/import_engine.php';
require_once $root.'/includes/data/actions.php';
if(!demo_start_session(1)){fwrite(STDERR,"Could not start demo session.\n");exit(1);} 
$tmp=tempnam(sys_get_temp_dir(),'gruber-import-');
file_put_contents($tmp,"supplier_number,name,company_id,payment_terms,status,risk\nSUP-QA-900,Quality Test Supplier,1,Net 30,approved,low\n");
$job=import_receive_upload(['error'=>UPLOAD_ERR_OK,'size'=>filesize($tmp),'name'=>'supplier-quality.csv','tmp_name'=>$tmp],'supplier_master',1);
if(($job['status']??'')!=='ready'||(int)($job['rows_valid']??0)!==1){fwrite(STDERR,"Demo import did not validate.\n");exit(1);} 
$receipt=import_commit_job((int)$job['id']);
if(empty($receipt['receipt_number'])||!data_find_by('suppliers','supplier_number','SUP-QA-900')){fwrite(STDERR,"Demo import did not commit records and receipt.\n");exit(1);} 
$issues=import_mapping_issues(['Supplier'=>'name','Vendor'=>'name'],'supplier_master');
if(!$issues){fwrite(STDERR,"Duplicate mapping was not rejected.\n");exit(1);} 
$csv=import_read_csv((function(){ $p=tempnam(sys_get_temp_dir(),'gruber-bom-');file_put_contents($p,"\xEF\xBB\xBFsupplier_number,name\nSUP-1,One\n");return $p;})(),10);
if(($csv['headers'][0]??'')!=='supplier_number'){fwrite(STDERR,"CSV BOM was not removed.\n");exit(1);} 
$source=file_get_contents($root.'/includes/demo/actions.php');
foreach(["case 'upload_import'","import_validate_job(","import_commit_job("] as $needle){if(!str_contains($source,$needle)){fwrite(STDERR,"Missing demo import parity: {$needle}\n");exit(1);}}
$export=file_get_contents($root.'/app/import-errors-export.php');
if(!str_contains($export,"preg_match('/^[=+\\-@]/'")){fwrite(STDERR,"CSV formula injection guard missing.\n");exit(1);} 
fwrite(STDOUT,"Section 6 import and data-quality gates passed.\n");
