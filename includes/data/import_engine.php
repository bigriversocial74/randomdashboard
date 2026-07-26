<?php
declare(strict_types=1);

function import_storage_root(): string
{
    $configured = app_config()['app']['import_storage_path'] ?? null;
    $path = is_string($configured) && $configured !== '' ? $configured : PROJECT_ROOT . '/storage/imports';
    if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RuntimeException('Import storage could not be created.');
    }
    return $path;
}

function import_normalize_header(string $header): string
{
    $header = strtolower(trim($header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
    return trim($header, '_');
}


function import_target_fields(string $type): array
{
    return [
        'supplier_master'=>['supplier_number','name','legal_name','website','category_id','payment_terms','status','risk','company_id'],
        'item_master'=>['item_number','sku','description','category_id','company_id','uom','standard_cost','status'],
        'purchase_orders'=>['po_number','company_id','supplier_id','order_date','required_date','expected_date','status','total_amount','buyer_id'],
        'inventory_snapshot'=>['company_id','inventory_location_id','item_id','quantity_on_hand','average_unit_cost','last_usage_date'],
    ][$type] ?? [];
}

function import_identity_fields(string $type): array
{
    return match ($type) {
        'supplier_master' => ['supplier_number'],
        'item_master' => ['item_number'],
        'purchase_orders' => ['po_number'],
        'inventory_snapshot' => ['company_id','inventory_location_id','item_id'],
        default => [],
    };
}

function import_mapping_issues(array $mapping, string $type): array
{
    $issues = [];
    $allowed = import_target_fields($type);
    $targets = array_values(array_filter(array_map('strval', $mapping), static fn(string $value): bool => $value !== ''));
    foreach ($targets as $target) {
        if (!in_array($target, $allowed, true)) $issues[] = 'Unsupported target column: ' . $target . '.';
    }
    foreach (array_count_values($targets) as $target => $count) {
        if ($count > 1) $issues[] = 'Target column ' . $target . ' is mapped more than once.';
    }
    foreach (import_required_fields($type) as $required) {
        if (!in_array($required, $targets, true)) $issues[] = 'Required target column ' . $required . ' is not mapped.';
    }
    return array_values(array_unique($issues));
}

function import_valid_iso_date(string $value): bool
{
    if ($value === '') return true;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

function import_duplicate_header_issues(array $headers): array
{
    $normalized = array_map('import_normalize_header', $headers);
    $issues = [];
    foreach ($normalized as $index => $header) {
        if ($header === '') $issues[] = 'Column ' . ($index + 1) . ' has a blank header.';
    }
    foreach (array_count_values(array_filter($normalized)) as $header => $count) {
        if ($count > 1) $issues[] = 'Duplicate header detected: ' . $header . '.';
    }
    return $issues;
}

function import_read_csv(string $path, int $limit): array
{
    $handle = fopen($path, 'rb');
    if (!$handle) throw new RuntimeException('The uploaded CSV could not be opened.');
    $first = fgets($handle);
    if ($first === false) { fclose($handle); throw new RuntimeException('The uploaded file is empty.'); }
    $delimiter = substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',';
    rewind($handle);
    $headers = fgetcsv($handle, 0, $delimiter);
    if (!$headers) { fclose($handle); throw new RuntimeException('The uploaded file does not contain a header row.'); }
    $headers = array_map(static fn($v): string => trim((string)$v), $headers);
    if (isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
    $headerIssues = import_duplicate_header_issues($headers);
    if ($headerIssues) { fclose($handle); throw new RuntimeException(implode(' ', $headerIssues)); }
    $rows = [];
    $truncated = false;
    while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (count(array_filter($values, static fn($v): bool => trim((string)$v) !== '')) === 0) continue;
        if (count($rows) >= $limit) { $truncated = true; break; }
        if (count($values) > count($headers)) { fclose($handle); throw new RuntimeException('A CSV row contains more values than the header row.'); }
        $values = array_pad($values, count($headers), '');
        $rows[] = array_combine($headers, array_slice($values, 0, count($headers))) ?: [];
    }
    fclose($handle);
    return ['headers'=>$headers,'rows'=>$rows,'truncated'=>$truncated];
}

function import_xlsx_column_index(string $letters): int
{
    $result = 0;
    foreach (str_split($letters) as $char) $result = $result * 26 + (ord($char) - 64);
    return $result - 1;
}

function import_read_xlsx(string $path, int $limit): array
{
    if (!class_exists('ZipArchive')) throw new RuntimeException('XLSX support requires the PHP zip extension. Upload CSV instead.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('The XLSX archive could not be opened.');
    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        if ($xml) foreach ($xml->si as $si) {
            $parts=[]; foreach ($si->xpath('.//t') ?: [] as $t) $parts[]=(string)$t;
            $shared[] = implode('', $parts);
        }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) throw new RuntimeException('The first XLSX worksheet could not be found.');
    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) throw new RuntimeException('The XLSX worksheet is invalid XML.');
    $matrix=[]; $truncated=false;
    foreach ($sheet->sheetData->row as $row) {
        $values=[];
        foreach ($row->c as $cell) {
            $ref=(string)$cell['r']; preg_match('/([A-Z]+)/',$ref,$m); $index=import_xlsx_column_index($m[1]??'A');
            $type=(string)$cell['t']; $value=(string)$cell->v;
            if($type==='s') $value=$shared[(int)$value]??'';
            elseif($type==='inlineStr') { $parts=[]; foreach($cell->xpath('.//t') ?: [] as $t) $parts[]=(string)$t; $value=implode('',$parts); }
            $values[$index]=$value;
        }
        if($values){ksort($values);$max=max(array_keys($values));$matrix[]=array_map('strval',array_replace(array_fill(0,$max+1,''),$values));}
        if(count($matrix)>$limit+1){$truncated=true;break;}
    }
    if(!$matrix) throw new RuntimeException('The XLSX worksheet is empty.');
    $headers=array_map('trim',array_shift($matrix));
    if (isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
    $headerIssues=import_duplicate_header_issues($headers); if($headerIssues) throw new RuntimeException(implode(' ',$headerIssues));
    $rows=[]; foreach(array_slice($matrix,0,$limit) as $values){$values=array_pad($values,count($headers),'');$rows[]=array_combine($headers,array_slice($values,0,count($headers)))?:[];}
    return ['headers'=>$headers,'rows'=>$rows,'truncated'=>$truncated];
}

function import_auto_mapping(array $headers, string $type): array
{
    $targets = import_target_fields($type);
    $aliases=['supplier'=>'supplier_id','supplier_name'=>'name','supplier_code'=>'supplier_number','description'=>'description','item_description'=>'description','unit_cost'=>'standard_cost','unit_of_measure'=>'uom','quantity'=>'quantity_on_hand','location'=>'inventory_location_id','company'=>'company_id'];
    $mapping=[];
    foreach($headers as $header){$normalized=import_normalize_header((string)$header);$candidate=$aliases[$normalized]??$normalized;if(in_array($candidate,$targets,true))$mapping[$header]=$candidate;}
    return $mapping;
}

function import_required_fields(string $type): array
{
    return match($type){
        'supplier_master'=>['supplier_number','name'],
        'item_master'=>['item_number','description','category_id','uom'],
        'purchase_orders'=>['po_number','company_id','supplier_id','order_date','total_amount'],
        'inventory_snapshot'=>['inventory_location_id','item_id','quantity_on_hand'],
        default=>[],
    };
}

function import_security_error_codes(): array
{
    return ['company_required','company_scope','supplier_scope','buyer_scope','location_scope','item_scope'];
}

function import_job_within_scope(array $job): bool
{
    return data_record_visible($job);
}

function import_store_staging_rows(int $jobId,array $rows):void
{
    if(data_is_demo()){
        $state=&demo_state();$state['import_staging_rows']=array_values(array_filter($state['import_staging_rows']??[],fn($r)=>(int)$r['import_job_id']!==$jobId));
        foreach($rows as $i=>$row)$state['import_staging_rows'][]=['id'=>count($state['import_staging_rows'])+1,'import_job_id'=>$jobId,'row_number'=>$i+2,'row'=>$row,'status'=>'staged'];
        return;
    }
    $pdo=mysql_repo_pdo();$pdo->prepare('DELETE FROM import_staging_rows WHERE import_id=?')->execute([$jobId]);
    $stmt=$pdo->prepare('INSERT INTO import_staging_rows(import_id,source_row_number,row_data,row_status) VALUES(?,?,?,"staged")');
    foreach($rows as $i=>$row)$stmt->execute([$jobId,$i+2,json_encode($row,JSON_UNESCAPED_SLASHES)]);
}

function import_staging_rows(int $jobId):array
{
    if(data_is_demo())return array_values(array_filter(demo_state()['import_staging_rows']??[],fn($r)=>(int)$r['import_job_id']===$jobId));
    return array_map(static fn($r)=>['id'=>(int)$r['id'],'import_job_id'=>(int)$r['import_id'],'row_number'=>(int)$r['source_row_number'],'row'=>mysql_repo_json($r['row_data'],[]),'status'=>$r['row_status']],mysql_repo_rows('SELECT * FROM import_staging_rows WHERE import_id=? ORDER BY source_row_number',[$jobId]));
}

function import_clear_errors(int $jobId):void
{
    if(data_is_demo()){
        demo_replace_collection('import_validation_errors',array_values(array_filter(demo_collection('import_validation_errors'),fn($e)=>(int)$e['import_job_id']!==$jobId)));return;
    }
    mysql_repo_exec('DELETE FROM import_validation_errors WHERE import_id=:id',['id'=>$jobId]);
}

function import_validate_job(int $jobId,array $mapping):array
{
    $job=data_find('import_jobs',$jobId);if(!$job||!import_job_within_scope($job))throw new RuntimeException('Import job not found or unavailable in the current company scope.');
    import_clear_errors($jobId);$required=import_required_fields($job['type']);$errors=[];$valid=0;$seen=[];
    $mappingIssues=import_mapping_issues($mapping,(string)$job['type']);
    foreach($mappingIssues as $message){$errors[]=data_upsert('import_validation_errors',['id'=>null,'import_job_id'=>$jobId,'row_number'=>1,'field'=>'mapping','value'=>'','message'=>$message,'error_code'=>'mapping_invalid','status'=>'open']);}
    foreach(import_staging_rows($jobId) as $staged){
        $normalized=[];foreach($mapping as $source=>$target){if($target!=='')$normalized[$target]=trim((string)($staged['row'][$source]??''));}
        $rowErrors=[];
        foreach($required as $field)if(!isset($normalized[$field])||$normalized[$field]==='')$rowErrors[]=['field'=>$field,'value'=>'','message'=>'Required value is missing.','code'=>'required','status'=>'open'];
        foreach(['company_id','supplier_id','category_id','buyer_id','inventory_location_id','item_id'] as $field)if(isset($normalized[$field])&&$normalized[$field]!==''&&!ctype_digit((string)$normalized[$field]))$rowErrors[]=['field'=>$field,'value'=>$normalized[$field],'message'=>'Expected a numeric database identifier.','code'=>'invalid_id','status'=>'open'];
        foreach(['total_amount','standard_cost','quantity_on_hand','average_unit_cost'] as $field)if(isset($normalized[$field])&&$normalized[$field]!==''&&(!is_numeric($normalized[$field])||(float)$normalized[$field]<0))$rowErrors[]=['field'=>$field,'value'=>$normalized[$field],'message'=>'Expected a nonnegative numeric value.','code'=>'invalid_number','status'=>'open'];
        foreach(['order_date','required_date','expected_date','last_usage_date'] as $field)if(isset($normalized[$field])&&!import_valid_iso_date((string)$normalized[$field]))$rowErrors[]=['field'=>$field,'value'=>$normalized[$field],'message'=>'Expected a valid date in YYYY-MM-DD format.','code'=>'invalid_date','status'=>'open'];
        if(!empty($normalized['order_date'])){foreach(['required_date','expected_date'] as $field)if(!empty($normalized[$field])&&$normalized[$field]<$normalized['order_date'])$rowErrors[]=['field'=>$field,'value'=>$normalized[$field],'message'=>'Date cannot be earlier than the order date.','code'=>'invalid_chronology','status'=>'open'];}
        $allowedStatuses=match((string)$job['type']){'supplier_master'=>['draft','approved','inactive'],'item_master'=>['active','draft','inactive'],'purchase_orders'=>['draft','pending_approval','open','partially_received','received','past_due','canceled','closed'],default=>[]};
        if(isset($normalized['status'])&&$normalized['status']!==''&&$allowedStatuses&&!in_array($normalized['status'],$allowedStatuses,true))$rowErrors[]=['field'=>'status','value'=>$normalized['status'],'message'=>'Status is not valid for this import type.','code'=>'invalid_enum','status'=>'open'];
        if(isset($normalized['risk'])&&$normalized['risk']!==''&&!in_array($normalized['risk'],['low','medium','high','critical'],true))$rowErrors[]=['field'=>'risk','value'=>$normalized['risk'],'message'=>'Risk must be low, medium, high, or critical.','code'=>'invalid_enum','status'=>'open'];
        $rowCompanyId = (int)(($normalized['company_id'] ?? '') ?: ($job['company_id'] ?? 0));
        if ($rowCompanyId <= 0) $rowErrors[]=['field'=>'company_id','value'=>'','message'=>'A permitted company is required for each enterprise import row.','code'=>'company_required','status'=>'open'];
        elseif (!data_company_within_scope($rowCompanyId)) $rowErrors[]=['field'=>'company_id','value'=>(string)$rowCompanyId,'message'=>'This company is outside the current user scope.','code'=>'company_scope','status'=>'open'];
        if (!empty($normalized['supplier_id'])) {$supplier=data_find('suppliers',(int)$normalized['supplier_id']);if(!$supplier||!data_record_visible($supplier))$rowErrors[]=['field'=>'supplier_id','value'=>$normalized['supplier_id'],'message'=>'Supplier is unavailable in the selected company scope.','code'=>'supplier_scope','status'=>'open'];}
        if (!empty($normalized['buyer_id'])) {$buyer=data_find('users',(int)$normalized['buyer_id']);if(!$buyer||!data_user_within_scope($buyer))$rowErrors[]=['field'=>'buyer_id','value'=>$normalized['buyer_id'],'message'=>'Buyer is unavailable in the selected company scope.','code'=>'buyer_scope','status'=>'open'];}
        if (!empty($normalized['inventory_location_id'])) {$location=data_find('inventory_locations',(int)$normalized['inventory_location_id']);if(!$location||!data_record_visible($location))$rowErrors[]=['field'=>'inventory_location_id','value'=>$normalized['inventory_location_id'],'message'=>'Inventory location is unavailable in the selected company scope.','code'=>'location_scope','status'=>'open'];}
        if (!empty($normalized['item_id'])) {$item=data_find('items',(int)$normalized['item_id']);if(!$item||!data_record_visible($item))$rowErrors[]=['field'=>'item_id','value'=>$normalized['item_id'],'message'=>'Item is unavailable in the selected company scope.','code'=>'item_scope','status'=>'open'];}
        $identity=[];foreach(import_identity_fields((string)$job['type']) as $field)$identity[]=(string)($normalized[$field]??($field==='company_id'?$rowCompanyId:''));
        if($identity && !in_array('', $identity, true)){$key=implode('|',$identity);if(isset($seen[$key]))$rowErrors[]=['field'=>implode(', ',import_identity_fields((string)$job['type'])),'value'=>$key,'message'=>'Duplicate business key also appears on source row '.$seen[$key].'.','code'=>'duplicate_in_file','status'=>'open'];else $seen[$key]=(int)$staged['row_number'];}
        foreach($rowErrors as $error){$errors[]=data_upsert('import_validation_errors',['id'=>null,'import_job_id'=>$jobId,'row_number'=>$staged['row_number'],'field'=>$error['field'],'value'=>$error['value'],'message'=>$error['message'],'error_code'=>$error['code'],'status'=>$error['status']]);}
        if(!$rowErrors&&!$mappingIssues)$valid++;
    }
    $job['mapping']=$mapping;$job['rows_total']=count(import_staging_rows($jobId));$job['rows_valid']=$valid;$job['rows_error']=count(array_unique(array_map(static fn(array $error):int=>(int)$error['row_number'],$errors)));$job['status']=$errors?'validating':'ready';
    $job['validation_summary']=['validated_at'=>date('c'),'valid_rows'=>$valid,'error_rows'=>$job['rows_error'],'mapping_issues'=>count($mappingIssues),'checksum'=>$job['checksum']??null];
    data_upsert('import_jobs',$job);data_add_audit('Imports','validated','import_job',$jobId,null,$job,$job['company_id']);return $job;
}

function import_receive_upload(array $file,string $type,int|string|null $companyId):array
{
    data_require_action_permission('imports.create');
    if($companyId==='enterprise'){if(!can_use_enterprise_view()||current_company_id()!=='enterprise')throw new RuntimeException('Enterprise imports require Enterprise View.');}
    elseif(!data_company_within_scope($companyId))throw new RuntimeException('The selected import company is outside the current user scope.');
    if(!isset($file['error'])||$file['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Select a CSV or XLSX file to upload.');
    $settings=data_settings();$maxBytes=(int)($settings['upload_limit_mb']??25)*1024*1024;
    if((int)$file['size']>$maxBytes)throw new RuntimeException('The upload exceeds the configured size limit.');
    $original=basename((string)$file['name']);$extension=strtolower(pathinfo($original,PATHINFO_EXTENSION));
    $allowed=array_map('strtolower',$settings['allowed_file_types']??['csv','xlsx']);if(!in_array($extension,$allowed,true))throw new RuntimeException('This file type is not allowed.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])?:'application/octet-stream';
    $allowedMimes=$extension==='xlsx'?['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/octet-stream']:['text/plain','text/csv','application/csv','application/vnd.ms-excel','application/octet-stream'];
    if(!in_array($mime,$allowedMimes,true))throw new RuntimeException('The uploaded file content does not match the selected file type.');
    $subdir=date('Y/m');$directory=import_storage_root().'/'.$subdir;if(!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory))throw new RuntimeException('Import storage is not writable.');
    $storedName=bin2hex(random_bytes(16)).'.'.$extension;$destination=$directory.'/'.$storedName;
    if(!move_uploaded_file($file['tmp_name'],$destination)&&!rename($file['tmp_name'],$destination))throw new RuntimeException('The uploaded file could not be stored.');
    chmod($destination,0640);$checksum=hash_file('sha256',$destination);
    $limit=(int)($settings['import_row_limit']??25000);$parsed=$extension==='xlsx'?import_read_xlsx($destination,$limit):import_read_csv($destination,$limit);
    if(!empty($parsed['truncated']))throw new RuntimeException('The source exceeds the configured row limit. Split it into smaller files before importing.');
    $mapping=import_auto_mapping($parsed['headers'],$type);
    $job=data_upsert('import_jobs',['id'=>null,'company_id'=>$companyId==='enterprise'?null:(int)$companyId,'type'=>$type,'name'=>status_label($type).' import','file_name'=>$original,'stored_path'=>$subdir.'/'.$storedName,'status'=>'mapping','rows_total'=>count($parsed['rows']),'rows_valid'=>0,'rows_error'=>0,'mapping'=>$mapping,'checksum'=>$checksum,'mime_type'=>$mime,'created_at'=>date('Y-m-d H:i:s')]);
    import_store_staging_rows((int)$job['id'],$parsed['rows']);
    import_validate_job((int)$job['id'],$mapping);
    data_add_audit('Imports','uploaded','import_job',$job['id'],null,['filename'=>$original,'checksum'=>$checksum,'rows'=>count($parsed['rows'])],$companyId);
    return data_find('import_jobs',(int)$job['id'])??$job;
}

function import_normalized_row(array $staged,array $mapping):array
{
    $normalized=[];foreach($mapping as $source=>$target){if($target!=='')$normalized[$target]=trim((string)($staged['row'][$source]??''));}return $normalized;
}

function import_commit_job(int $jobId):array
{
    $job=data_find('import_jobs',$jobId);if(!$job||!import_job_within_scope($job))throw new RuntimeException('Import job not found or unavailable in the current company scope.');
    $open=count(array_filter(data_collection('import_validation_errors'),fn($e)=>(int)$e['import_job_id']===$jobId&&in_array($e['status'],['open','review'],true)));
    if($open)throw new RuntimeException('Resolve or accept every validation error before committing.');
    if(data_is_demo()){
        $created=0;$updated=0;$skipped=0;
        foreach(import_staging_rows($jobId) as $staged){$row=import_normalized_row($staged,$job['mapping']);$rowCompanyId=(int)(($row['company_id']??'')?:($job['company_id']??0));
            switch($job['type']){
                case 'supplier_master':
                    $existing=data_find_by('suppliers','supplier_number',$row['supplier_number']);
                    $record=['id'=>$existing['id']??null,'supplier_number'=>$row['supplier_number'],'name'=>$row['name'],'legal_name'=>$row['legal_name']??$row['name'],'company_ids'=>[$rowCompanyId],'category_id'=>(int)(($row['category_id']??'')?:1),'status'=>$row['status']??'approved','risk'=>$row['risk']??'medium','owner_id'=>current_user()['id'],'payment_terms'=>$row['payment_terms']??'Net 30','website'=>$row['website']??'','review_status'=>'draft'];
                    data_upsert('suppliers',$record);$existing?$updated++:$created++;break;
                case 'item_master':
                    $existing=data_find_by('items','item_number',$row['item_number']);
                    $record=['id'=>$existing['id']??null,'item_number'=>$row['item_number'],'sku'=>$row['sku']??'','description'=>$row['description'],'category_id'=>(int)(($row['category_id']??'')?:1),'company_ids'=>[$rowCompanyId],'uom'=>$row['uom'],'standard_cost'=>(float)($row['standard_cost']??0),'owner_id'=>current_user()['id'],'status'=>$row['status']??'active','review_status'=>'draft'];
                    data_upsert('items',$record);$existing?$updated++:$created++;break;
                case 'purchase_orders':
                    $existing=data_find_by('purchase_orders','po_number',$row['po_number']);
                    $record=['id'=>$existing['id']??null,'po_number'=>$row['po_number'],'company_id'=>$rowCompanyId,'supplier_id'=>(int)$row['supplier_id'],'order_date'=>$row['order_date'],'required_date'=>$row['required_date']??null,'expected_date'=>$row['expected_date']??null,'status'=>$row['status']??'draft','total_amount'=>(float)$row['total_amount'],'buyer_id'=>(int)($row['buyer_id']??current_user()['id']),'review_status'=>'draft'];
                    data_upsert('purchase_orders',$record);$existing?$updated++:$created++;break;
                case 'inventory_snapshot':
                    $existing=null;foreach(data_collection('inventory_snapshots') as $candidate)if((int)$candidate['company_id']===$rowCompanyId&&(int)($candidate['location_id']??0)===(int)$row['inventory_location_id']&&(int)$candidate['item_id']===(int)$row['item_id']){$existing=$candidate;break;}
                    $quantity=(float)$row['quantity_on_hand'];$unitCost=(float)($row['average_unit_cost']??0);
                    $record=['id'=>$existing['id']??null,'company_id'=>$rowCompanyId,'location_id'=>(int)$row['inventory_location_id'],'item_id'=>(int)$row['item_id'],'snapshot_date'=>date('Y-m-d'),'quantity_on_hand'=>$quantity,'allocated'=>(float)($existing['allocated']??0),'available'=>max(0,$quantity-(float)($existing['allocated']??0)),'unit_cost'=>$unitCost,'value'=>$quantity*$unitCost,'last_usage_date'=>$row['last_usage_date']??null];
                    data_upsert('inventory_snapshots',$record);$existing?$updated++:$created++;break;
                default:$skipped++;
            }
        }
        $job['status']='committed';data_upsert('import_jobs',$job);
        $receipt=data_upsert('import_receipts',['id'=>null,'import_job_id'=>$jobId,'receipt_number'=>'DEMO-'.date('Ymd').'-'.str_pad((string)$jobId,4,'0',STR_PAD_LEFT),'committed_by'=>current_user()['id'],'committed_at'=>date('Y-m-d H:i:s'),'records_created'=>$created,'records_updated'=>$updated,'records_skipped'=>$skipped,'hash'=>hash('sha256','demo-'.$jobId.'-'.$created.'-'.$updated.'-'.$skipped)]);
        data_add_audit('Imports','committed','import_job',$jobId,$job,['receipt'=>$receipt['receipt_number'],'records_created'=>$created,'records_updated'=>$updated,'records_skipped'=>$skipped],$job['company_id']);return $receipt;
    }
    $pdo=mysql_repo_pdo();$pdo->beginTransaction();$created=0;$updated=0;$skipped=0;
    try{
        foreach(import_staging_rows($jobId) as $staged){$row=import_normalized_row($staged,$job['mapping']);
            $rowCompanyId = (int)(($row['company_id'] ?? '') ?: ($job['company_id'] ?? 0));
            if ($rowCompanyId <= 0 || !data_company_within_scope($rowCompanyId)) throw new RuntimeException('An import row contains an unavailable company identifier.');
            switch($job['type']){
                case 'supplier_master':
                    $existing=mysql_repo_row('SELECT id FROM suppliers WHERE supplier_number=?',[$row['supplier_number']]);
                    $record=['id'=>$existing['id']??null,'supplier_number'=>$row['supplier_number'],'name'=>$row['name'],'legal_name'=>$row['legal_name']??$row['name'],'company_ids'=>[$rowCompanyId],'category_id'=>(int)(($row['category_id'] ?? '') ?: 1),'status'=>$row['status']??'approved','risk'=>$row['risk']??'medium','owner_id'=>current_user()['id'],'payment_terms'=>$row['payment_terms']??'Net 30','website'=>$row['website']??'','review_status'=>'draft'];
                    mysql_repo_upsert('suppliers',$record);$existing?$updated++:$created++;break;
                case 'item_master':
                    $existing=mysql_repo_row('SELECT id FROM items WHERE item_number=?',[$row['item_number']]);
                    $record=['id'=>$existing['id']??null,'item_number'=>$row['item_number'],'sku'=>$row['sku']??'','description'=>$row['description'],'category_id'=>(int)(($row['category_id'] ?? '') ?: 1),'company_ids'=>[$rowCompanyId],'uom'=>$row['uom'],'standard_cost'=>(float)($row['standard_cost']??0),'owner_id'=>current_user()['id'],'status'=>$row['status']??'active','review_status'=>'draft'];
                    mysql_repo_upsert('items',$record);$existing?$updated++:$created++;break;
                case 'purchase_orders':
                    $supplier=data_find('suppliers',(int)($row['supplier_id']??0));
                    $supplierCompanies=array_map('intval',$supplier['company_ids']??[]);
                    if(!$supplier||!in_array($rowCompanyId,$supplierCompanies,true))throw new RuntimeException('A purchase-order import row references a supplier outside its company.');
                    $buyer=data_find('users',(int)($row['buyer_id']??current_user()['id']));
                    $buyerCompanies=array_map('intval',$buyer['company_ids']??[]);
                    if(!$buyer||!in_array($rowCompanyId,$buyerCompanies,true))throw new RuntimeException('A purchase-order import row references a buyer outside its company.');
                    $existing=mysql_repo_row('SELECT id FROM purchase_orders WHERE po_number=?',[$row['po_number']]);
                    $record=['id'=>$existing['id']??null,'po_number'=>$row['po_number'],'company_id'=>$rowCompanyId,'supplier_id'=>(int)($row['supplier_id'] ?? 0),'order_date'=>$row['order_date'],'required_date'=>$row['required_date']??null,'expected_date'=>$row['expected_date']??null,'status'=>$row['status']??'draft','total_amount'=>(float)$row['total_amount'],'buyer_id'=>(int)($row['buyer_id']??current_user()['id']),'review_status'=>'draft'];
                    mysql_repo_upsert('purchase_orders',$record);$existing?$updated++:$created++;break;
                case 'inventory_snapshot':
                    $location=data_find('inventory_locations',(int)($row['inventory_location_id']??0));
                    $item=data_find('items',(int)($row['item_id']??0));
                    if(!$location||(int)($location['company_id']??0)!==$rowCompanyId)throw new RuntimeException('An inventory import row references a location outside its company.');
                    if(!$item||!in_array($rowCompanyId,array_map('intval',$item['company_ids']??[]),true))throw new RuntimeException('An inventory import row references an item outside its company.');
                    $existing=mysql_repo_row('SELECT id FROM inventory_balances WHERE inventory_location_id=? AND item_id=?',[(int)$row['inventory_location_id'],(int)$row['item_id']]);
                    if($existing){$pdo->prepare('UPDATE inventory_balances SET quantity_on_hand=?,average_unit_cost=?,last_usage_date=?,updated_at=NOW() WHERE id=?')->execute([(float)$row['quantity_on_hand'],isset($row['average_unit_cost'])?(float)$row['average_unit_cost']:null,$row['last_usage_date']??null,$existing['id']]);$updated++;}
                    else{$pdo->prepare('INSERT INTO inventory_balances(inventory_location_id,item_id,quantity_on_hand,average_unit_cost,last_usage_date) VALUES(?,?,?,?,?)')->execute([(int)$row['inventory_location_id'],(int)$row['item_id'],(float)$row['quantity_on_hand'],isset($row['average_unit_cost'])?(float)$row['average_unit_cost']:null,$row['last_usage_date']??null]);$created++;}break;
                default:$skipped++;
            }
        }
        $pdo->prepare('UPDATE imports SET status="completed",accepted_rows=?,rejected_rows=?,started_at=COALESCE(started_at,NOW()),completed_at=NOW() WHERE id=?')->execute([$created+$updated,$skipped,$jobId]);
        $receiptNumber='IMP-'.date('Ymd').'-'.str_pad((string)$jobId,5,'0',STR_PAD_LEFT);$checksum=hash('sha256',$receiptNumber.'|'.$created.'|'.$updated.'|'.$skipped.'|'.($job['file_name']??''));
        $pdo->prepare('INSERT INTO import_receipts(import_id,receipt_number,committed_by,accepted_rows,rejected_rows,checksum_sha256,summary_json,records_created,records_updated) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$jobId,$receiptNumber,current_user()['id'],$created+$updated,$skipped,$checksum,json_encode(['records_created'=>$created,'records_updated'=>$updated,'records_skipped'=>$skipped]),$created,$updated]);
        $receiptId=(int)$pdo->lastInsertId();$pdo->commit();mysql_repo_clear_cache();
        mysql_repo_add_audit('Imports','committed','import_job',$jobId,$job,['records_created'=>$created,'records_updated'=>$updated,'records_skipped'=>$skipped,'receipt'=>$receiptNumber],$job['company_id']);
        return mysql_repo_find('import_receipts',$receiptId)??['id'=>$receiptId,'receipt_number'=>$receiptNumber];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
