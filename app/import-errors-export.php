<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_permission('imports.review');
$jobs=data_visible_collection('import_jobs');$jobIds=array_map('intval',array_column($jobs,'id'));
$jobFilter=query_int('job');$status=query_string('status','all');
if($jobFilter>0&&!in_array($jobFilter,$jobIds,true)){http_response_code(404);exit('Import job not found.');}
$errors=array_values(array_filter(data_collection('import_validation_errors'),static function(array $error)use($jobIds,$jobFilter,$status):bool{if(!in_array((int)$error['import_job_id'],$jobIds,true))return false;if($jobFilter>0&&(int)$error['import_job_id']!==$jobFilter)return false;if($status!=='all'&&(string)($error['status']??'open')!==$status)return false;return true;}));
function import_csv_cell(mixed $value):string{$value=(string)$value;if(preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value;}
$filename='gruber-import-validation-'.date('Ymd-His').'.csv';
header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$filename.'"');header('X-Content-Type-Options: nosniff');
$out=fopen('php://output','wb');fputcsv($out,['Import','Source row','Field','Raw value','Error code','Message','Status']);
foreach($errors as $error){$job=data_find('import_jobs',(int)$error['import_job_id']);fputcsv($out,array_map('import_csv_cell',[$job['name']??('Import #'.$error['import_job_id']),$error['row_number']??'', $error['field']??'', $error['value']??'', $error['error_code']??'', $error['message']??'', $error['status']??'open']));}
fclose($out);exit;
