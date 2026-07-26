<?php
declare(strict_types=1);

/**
 * Production data adapter.
 *
 * All SQL in this file uses prepared statements for mutable operations. Read
 * models normalize the production schema into the same stable arrays consumed
 * by the application pages, which keeps Demo and Production behavior aligned.
 */

function mysql_repo_pdo(): PDO
{
    $pdo = database_connection();
    if (!$pdo) {
        throw new RuntimeException('The production database is unavailable.');
    }
    return $pdo;
}

function mysql_repo_clear_cache(): void
{
    $GLOBALS['gruber_mysql_collection_cache'] = [];
}

function mysql_repo_rows(string $sql, array $params = []): array
{
    try {
        $stmt = mysql_repo_pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        $_SESSION['gruber_db_error'] = $e->getMessage();
        return [];
    }
}

function mysql_repo_row(string $sql, array $params = []): ?array
{
    $rows = mysql_repo_rows($sql, $params);
    return $rows[0] ?? null;
}

function mysql_repo_json(mixed $value, mixed $fallback = []): mixed
{
    if (is_array($value)) return $value;
    if (!is_string($value) || trim($value) === '') return $fallback;
    $decoded = json_decode($value, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $fallback;
}

function mysql_repo_bool(mixed $value): bool
{
    return (bool) ((int) $value);
}

function mysql_repo_collection(string $name): array
{
    $cache =& $GLOBALS['gruber_mysql_collection_cache'];
    if (!is_array($cache ?? null)) $cache = [];
    if (array_key_exists($name, $cache)) return $cache[$name];

    $rows = match ($name) {
        'companies' => mysql_repo_rows(
            'SELECT c.*, cap.status AS admin_status, cap.primary_contact, cap.contact_email, cap.contact_phone,
                    cap.data_owner_user_id, cap.procurement_owner_user_id, cap.accounting_reviewer_user_id,
                    cap.completion_pct, cap.module_settings, cap.retention_days, cap.company_settings
             FROM companies c LEFT JOIN company_admin_profiles cap ON cap.company_id=c.id ORDER BY c.name'
        ),
        'roles' => mysql_repo_rows(
            'SELECT r.*, COUNT(DISTINCT ur.user_id) membership_count,
                    GROUP_CONCAT(DISTINCT p.permission_key ORDER BY p.permission_key SEPARATOR "|") permission_keys
             FROM roles r
             LEFT JOIN user_roles ur ON ur.role_id=r.id
             LEFT JOIN role_permissions rp ON rp.role_id=r.id
             LEFT JOIN permissions p ON p.id=rp.permission_id
             GROUP BY r.id ORDER BY r.name'
        ),
        'users' => mysql_repo_rows(
            'SELECT u.*, up.first_name,up.last_name,up.phone,up.department,up.employment_status,
                    up.password_reset_required,up.administrative_notes,
                    GROUP_CONCAT(DISTINCT cm.company_id ORDER BY cm.company_id SEPARATOR ",") company_ids_csv,
                    GROUP_CONCAT(DISTINCT r.code ORDER BY r.code SEPARATOR ",") role_codes_csv
             FROM users u
             LEFT JOIN user_profiles up ON up.user_id=u.id
             LEFT JOIN company_memberships cm ON cm.user_id=u.id AND cm.membership_status="active"
             LEFT JOIN user_roles ur ON ur.user_id=u.id
             LEFT JOIN roles r ON r.id=ur.role_id AND COALESCE(r.status,"active")="active"
             GROUP BY u.id ORDER BY u.name'
        ),
        'categories' => mysql_repo_rows('SELECT * FROM purchasing_categories ORDER BY name'),
        'suppliers' => mysql_repo_rows(
            'SELECT s.*, pc.name category,
                    GROUP_CONCAT(DISTINCT sc.company_id ORDER BY sc.company_id SEPARATOR ",") company_ids_csv,
                    COALESCE((SELECT SUM(po.total_amount) FROM purchase_orders po WHERE po.supplier_id=s.id),0) annual_spend
             FROM suppliers s
             LEFT JOIN purchasing_categories pc ON pc.id=s.primary_category_id
             LEFT JOIN supplier_companies sc ON sc.supplier_id=s.id AND sc.account_status="active"
             GROUP BY s.id ORDER BY s.normalized_name'
        ),
        'supplier_contacts' => mysql_repo_rows('SELECT * FROM supplier_contacts ORDER BY is_primary DESC,name'),
        'supplier_company_relationships' => mysql_repo_rows('SELECT supplier_id,company_id,account_number,account_status status,company_specific_terms terms FROM supplier_companies ORDER BY supplier_id,company_id'),
        'contracts' => mysql_repo_rows('SELECT id,supplier_id,company_id,contract_number number,title,start_date,end_date,estimated_annual_value annual_value,status FROM supplier_contracts ORDER BY end_date'),
        'items' => mysql_repo_rows(
            'SELECT i.*, GROUP_CONCAT(DISTINCT ic.company_id ORDER BY ic.company_id SEPARATOR ",") company_ids_csv
             FROM items i
             LEFT JOIN item_companies ic ON ic.item_id=i.id
             GROUP BY i.id ORDER BY i.item_number'
        ),
        'purchase_orders' => mysql_repo_rows('SELECT * FROM purchase_orders ORDER BY order_date DESC,id DESC'),
        'purchase_order_lines' => mysql_repo_rows('SELECT * FROM purchase_order_lines ORDER BY purchase_order_id,line_number'),
        'open_commitments' => mysql_repo_rows('SELECT * FROM vw_open_po_commitments ORDER BY days_past_due DESC,total_amount DESC'),
        'inventory_locations' => mysql_repo_rows(
            'SELECT il.*,l.company_id,l.name location_name,l.city,l.state_region FROM inventory_locations il JOIN locations l ON l.id=il.location_id ORDER BY l.company_id,il.name'
        ),
        'inventory_snapshots' => mysql_repo_rows(
            'SELECT ib.id, l.company_id, ib.inventory_location_id, ib.item_id, ib.quantity_on_hand,
                    ib.quantity_allocated,ib.average_unit_cost,ROUND(ib.quantity_on_hand*COALESCE(ib.average_unit_cost,0),2) value,
                    ib.last_receipt_date,ib.last_usage_date,ib.last_count_date,ib.updated_at
             FROM inventory_balances ib JOIN inventory_locations il ON il.id=ib.inventory_location_id
             JOIN locations l ON l.id=il.location_id ORDER BY value DESC'
        ),
        'inventory_aging' => mysql_repo_rows(
            'SELECT inventory_balance_id id,company_id,item_id,inventory_location_name location_name,quantity_on_hand,
                    average_unit_cost,inventory_value value,last_usage_date,aging_status bucket FROM vw_inventory_aging ORDER BY inventory_value DESC'
        ),
        'savings_opportunities' => mysql_repo_rows(
            'SELECT so.*,pc.name category FROM savings_opportunities so LEFT JOIN purchasing_categories pc ON pc.id=so.category_id ORDER BY expected_annual_savings DESC'
        ),
        'supplier_scorecards' => mysql_repo_rows('SELECT * FROM supplier_scorecards ORDER BY period_end DESC,weighted_score DESC'),
        'data_quality_exceptions' => mysql_repo_rows('SELECT * FROM data_quality_exceptions ORDER BY FIELD(severity,"critical","high","medium","low"),created_at DESC'),
        'notifications' => mysql_repo_rows('SELECT * FROM notifications ORDER BY created_at DESC'),
        'workflow_approvals' => mysql_repo_rows('SELECT * FROM workflow_approvals ORDER BY requested_at DESC'),
        'comments' => mysql_repo_rows('SELECT * FROM record_comments ORDER BY created_at DESC'),
        'audit_events' => mysql_repo_rows('SELECT * FROM audit_logs ORDER BY created_at DESC'),
        'import_jobs' => mysql_repo_rows('SELECT * FROM imports ORDER BY created_at DESC'),
        'import_validation_errors' => mysql_repo_rows('SELECT * FROM import_validation_errors ORDER BY import_id,source_row_number,id'),
        'import_receipts' => mysql_repo_rows('SELECT * FROM import_receipts ORDER BY committed_at DESC'),
        'sessions' => mysql_repo_rows('SELECT * FROM user_sessions ORDER BY started_at DESC'),
        'access_requests' => mysql_repo_rows('SELECT * FROM access_requests ORDER BY created_at DESC'),
        'security_events' => mysql_repo_rows('SELECT * FROM security_events ORDER BY occurred_at DESC'),
        'discovery_assignments' => mysql_repo_rows('SELECT * FROM discovery_assignments ORDER BY due_date,status'),
        'discovery_responses' => mysql_repo_rows('SELECT * FROM discovery_responses ORDER BY assignment_id,id'),
        default => [],
    };

    $normalized = array_map(static function (array $r) use ($name): array {
        return match ($name) {
            'companies' => [
                'id'=>(int)$r['id'],'code'=>$r['code'],'name'=>$r['name'],'legal_name'=>$r['legal_name']??'',
                'description'=>$r['description']??'','industry'=>$r['description']??'',
                'status'=>($r['admin_status']??null) ?: (mysql_repo_bool($r['is_active'])?'active':'inactive'),
                'primary_contact'=>$r['primary_contact']??'','contact_email'=>$r['contact_email']??'',
                'email'=>$r['contact_email']??'','phone'=>$r['contact_phone']??'',
                'data_owner_id'=>$r['data_owner_user_id']? (int)$r['data_owner_user_id']:null,
                'procurement_owner_id'=>$r['procurement_owner_user_id']?(int)$r['procurement_owner_user_id']:null,
                'accounting_reviewer_id'=>$r['accounting_reviewer_user_id']?(int)$r['accounting_reviewer_user_id']:null,
                'completion'=>(int)($r['completion_pct']??0),'modules'=>mysql_repo_json($r['module_settings']??null,[]),
                'retention_days'=>(int)($r['retention_days']??2555),'settings'=>mysql_repo_json($r['company_settings']??null,[]),
                'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at'],
            ],
            'roles' => [
                'id'=>(int)$r['id'],'code'=>$r['code'],'name'=>$r['name'],'description'=>$r['description']??'',
                'system'=>in_array($r['code'],['system_administrator','executive','company_administrator','procurement_manager','data_contributor','reviewer','read_only'],true),
                'status'=>$r['status']??'active','permissions'=>array_values(array_filter(explode('|',(string)($r['permission_keys']??'')))),
                'membership_count'=>(int)($r['membership_count']??0),'created_at'=>$r['created_at'],
            ],
            'users' => [
                'id'=>(int)$r['id'],'first_name'=>$r['first_name']?:explode(' ',$r['name'],2)[0],
                'last_name'=>$r['last_name']?:((explode(' ',$r['name'],2)[1]??'')),'name'=>$r['name'],'email'=>$r['email'],
                'phone'=>$r['phone']??'','job_title'=>$r['job_title']??'','department'=>$r['department']??'',
                'status'=>match($r['employment_status']??$r['status']){'inactive','locked'=>'suspended','pending'=>'pending',default=>$r['employment_status']??$r['status']},
                'primary_company_id'=>$r['primary_company_id']?(int)$r['primary_company_id']:null,
                'company_ids'=>array_values(array_filter(array_map('intval',explode(',',(string)($r['company_ids_csv']??''))))),
                'role_codes'=>array_values(array_filter(explode(',',(string)($r['role_codes_csv']??'')))) ?: ['read_only'],
                'last_login'=>$r['last_login_at'],'require_password_reset'=>mysql_repo_bool($r['password_reset_required']??0),
                'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at'],'admin_notes'=>$r['administrative_notes']??'',
            ],
            'categories' => ['id'=>(int)$r['id'],'code'=>$r['code'],'name'=>$r['name'],'description'=>$r['description']??'','status'=>mysql_repo_bool($r['is_active'])?'active':'inactive'],
            'suppliers' => [
                'id'=>(int)$r['id'],'supplier_number'=>$r['supplier_number'],'name'=>$r['normalized_name'],'legal_name'=>$r['legal_name']??$r['normalized_name'],
                'company_ids'=>array_values(array_filter(array_map('intval',explode(',',(string)($r['company_ids_csv']??''))))),
                'category_id'=>$r['primary_category_id']?(int)$r['primary_category_id']:null,'category'=>$r['category']??'Uncategorized',
                'status'=>$r['preferred_status'],'risk'=>$r['risk_rating'],'owner_id'=>$r['owner_user_id']?(int)$r['owner_user_id']:($r['created_by']?(int)$r['created_by']:null),
                'annual_spend'=>(float)$r['annual_spend'],'payment_terms'=>$r['payment_terms']??'','website'=>$r['website']??'',
                'review_status'=>$r['review_status']??'draft','sample'=>false,'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at'],
            ],
            'supplier_contacts' => ['id'=>(int)$r['id'],'supplier_id'=>(int)$r['supplier_id'],'name'=>$r['name'],'title'=>status_label($r['contact_type']),'email'=>$r['email']??'','phone'=>$r['phone']??'','primary'=>mysql_repo_bool($r['is_primary'])],
            'supplier_company_relationships' => array_replace($r,['supplier_id'=>(int)$r['supplier_id'],'company_id'=>(int)$r['company_id']]),
            'contracts' => array_replace($r,['id'=>(int)$r['id'],'supplier_id'=>(int)$r['supplier_id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,'annual_value'=>(float)$r['annual_value']]),
            'items' => [
                'id'=>(int)$r['id'],'item_number'=>$r['item_number'],'sku'=>$r['sku']??'','description'=>$r['normalized_description'],
                'category_id'=>(int)$r['category_id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,
                'company_ids'=>array_values(array_filter(array_map('intval',explode(',',(string)($r['company_ids_csv']??''))))),
                'uom'=>$r['unit_of_measure'],'standard_cost'=>(float)($r['standard_cost']??0),
                'owner_id'=>$r['owner_user_id']?(int)$r['owner_user_id']:($r['created_by']?(int)$r['created_by']:null),
                'status'=>$r['catalog_status']??(mysql_repo_bool($r['active'])?'active':'inactive'),'review_status'=>$r['review_status']??'draft','sample'=>false,
                'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at'],
            ],
            'purchase_orders' => [
                'id'=>(int)$r['id'],'po_number'=>$r['po_number'],'company_id'=>(int)$r['company_id'],'supplier_id'=>(int)$r['supplier_id'],
                'order_date'=>$r['order_date'],'required_date'=>$r['required_date'],'expected_date'=>$r['expected_date'],'status'=>$r['status'],
                'total_amount'=>(float)$r['total_amount'],'buyer_id'=>$r['buyer_user_id']?(int)$r['buyer_user_id']:null,
                'review_status'=>$r['review_status']??($r['status']==='pending_approval'?'submitted':'draft'),'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at'],
            ],
            'purchase_order_lines' => array_replace($r,['id'=>(int)$r['id'],'purchase_order_id'=>(int)$r['purchase_order_id'],'item_id'=>$r['item_id']?(int)$r['item_id']:null,'quantity_ordered'=>(float)$r['quantity_ordered'],'quantity_received'=>(float)$r['quantity_received'],'unit_cost'=>(float)$r['unit_cost'],'line_total'=>(float)$r['line_total']]),
            'open_commitments' => ['id'=>(int)$r['id'],'po_number'=>$r['po_number'],'company_id'=>(int)$r['company_id'],'supplier_id'=>(int)$r['supplier_id'],'expected_date'=>$r['expected_date'],'status'=>$r['status'],'amount'=>(float)$r['total_amount'],'total_amount'=>(float)$r['total_amount'],'days_past_due'=>(int)$r['days_past_due']],
            'inventory_locations' => array_replace($r,['id'=>(int)$r['id'],'location_id'=>(int)$r['location_id'],'company_id'=>(int)$r['company_id'],'status'=>mysql_repo_bool($r['is_active'])?'active':'inactive']),
            'inventory_snapshots' => array_replace($r,['id'=>(int)$r['id'],'company_id'=>(int)$r['company_id'],'inventory_location_id'=>(int)$r['inventory_location_id'],'item_id'=>(int)$r['item_id'],'quantity'=>(float)$r['quantity_on_hand'],'quantity_on_hand'=>(float)$r['quantity_on_hand'],'value'=>(float)$r['value']]),
            'inventory_aging' => array_replace($r,['id'=>(int)$r['id'],'company_id'=>(int)$r['company_id'],'item_id'=>(int)$r['item_id'],'quantity'=>(float)$r['quantity_on_hand'],'value'=>(float)$r['value']]),
            'savings_opportunities' => [
                'id'=>(int)$r['id'],'opportunity_number'=>$r['opportunity_number'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,
                'category_id'=>$r['category_id']?(int)$r['category_id']:null,'supplier_id'=>$r['supplier_id']?(int)$r['supplier_id']:null,
                'title'=>$r['title'],'description'=>$r['description']??'','opportunity_type'=>$r['opportunity_type']??'other',
                'category'=>$r['category']??status_label($r['opportunity_type']??'other'),'owner_id'=>$r['owner_user_id']?(int)$r['owner_user_id']:null,
                'stage'=>$r['pipeline_stage']??match($r['status']){'approved','implementing','completed'=>'approved','analyzing','negotiating'=>'review',default=>'draft'},
                'operational_status'=>$r['status']??'identified','annualized_value'=>(float)($r['expected_annual_savings']??0),
                'current_annual_cost'=>(float)($r['current_annual_cost']??0),'implementation_cost'=>(float)($r['implementation_cost']??0),
                'realized_savings'=>(float)($r['realized_savings']??0),'confidence'=>(int)($r['confidence_pct']??50),
                'risk'=>$r['risk_rating']??'medium','due_date'=>$r['target_date'],'accounting_validation'=>$r['accounting_validation']??'not_requested',
                'next_step'=>$r['next_step']??'','review_status'=>$r['review_status']??'draft','created_at'=>$r['created_at']??null,'updated_at'=>$r['updated_at']??null,
            ],
            'supplier_scorecards' => [
                'id'=>(int)$r['id'],'supplier_id'=>(int)$r['supplier_id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,
                'period'=>date_us($r['period_start']).' – '.date_us($r['period_end']),
                'on_time_delivery'=>(float)($r['on_time_delivery_pct']??0),'quality'=>(float)($r['quality_acceptance_pct']??0),
                'responsiveness'=>(float)($r['responsiveness_score']??0)*20,'cost_competitiveness'=>(float)($r['contract_compliance_pct']??0),
                'overall'=>(float)($r['weighted_score']??0),'status'=>$r['review_status']??'draft','grade'=>$r['grade']??null,
            ],
            'data_quality_exceptions' => ['id'=>(int)$r['id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,'module'=>$r['module'],'issue'=>$r['title'],'description'=>$r['description'],'severity'=>$r['severity'],'status'=>$r['status'],'owner_id'=>$r['owner_user_id']?(int)$r['owner_user_id']:null,'created_at'=>$r['created_at']],
            'notifications' => ['id'=>(int)$r['id'],'user_id'=>(int)$r['user_id'],'company_id'=>null,'module'=>$r['notification_type'],'title'=>$r['title'],'message'=>$r['message'],'severity'=>$r['severity'],'read'=>!empty($r['read_at']),'created_at'=>$r['created_at']],
            'workflow_approvals' => ['id'=>(int)$r['id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,'module'=>$r['module'],'entity_type'=>$r['entity_type'],'entity_id'=>(int)$r['entity_id'],'title'=>$r['title']??status_label($r['entity_type']).' #'.$r['entity_id'],'submitted_by'=>(int)$r['requested_by'],'assigned_to'=>$r['assigned_reviewer_id']?(int)$r['assigned_reviewer_id']:null,'status'=>$r['status'],'submitted_at'=>$r['requested_at'],'due_date'=>$r['due_at']??null,'notes'=>$r['decision_note']??''],
            'comments' => ['id'=>(int)$r['id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,'entity_type'=>$r['entity_type'],'entity_id'=>(int)$r['entity_id'],'user_id'=>(int)$r['author_user_id'],'body'=>$r['comment_text'],'created_at'=>$r['created_at']],
            'audit_events' => ['id'=>(int)$r['id'],'user_id'=>$r['user_id']?(int)$r['user_id']:null,'company_id'=>$r['company_id']?(int)$r['company_id']:null,'module'=>$r['module']??'Platform','action'=>$r['action'],'entity_type'=>$r['entity_type'],'entity_id'=>$r['entity_id']?(int)$r['entity_id']:null,'before'=>mysql_repo_json($r['before_data']??null,null),'after'=>mysql_repo_json($r['after_data']??null,null),'ip_address'=>$r['ip_address']??'','created_at'=>$r['created_at'],'immutable'=>true],
            'import_jobs' => ['id'=>(int)$r['id'],'company_id'=>$r['company_id']?(int)$r['company_id']:null,'type'=>$r['import_type'],'name'=>status_label($r['import_type']).' import','file_name'=>$r['original_filename'],'stored_path'=>$r['stored_path'],'status'=>$r['status'],'rows_total'=>(int)$r['total_rows'],'rows_valid'=>(int)$r['accepted_rows'],'rows_error'=>(int)$r['rejected_rows'],'mapping'=>mysql_repo_json($r['mapping_json']??null,[]),'created_by'=>(int)$r['imported_by'],'created_at'=>$r['created_at']],
            'import_validation_errors' => ['id'=>(int)$r['id'],'import_job_id'=>(int)$r['import_id'],'row_number'=>(int)$r['source_row_number'],'field'=>$r['column_name']??'','value'=>$r['raw_value']??'','message'=>$r['error_message'],'error_code'=>$r['error_code']??'validation','status'=>match($r['resolution_status']){'corrected'=>'resolved','ignored'=>'accepted',default=>'open'}],
            'import_receipts' => ['id'=>(int)$r['id'],'import_job_id'=>(int)$r['import_id'],'receipt_number'=>$r['receipt_number'],'committed_by'=>(int)$r['committed_by'],'committed_at'=>$r['committed_at'],'records_created'=>(int)($r['records_created']??mysql_repo_json($r['summary_json']??null,[])['records_created']??0),'records_updated'=>(int)($r['records_updated']??mysql_repo_json($r['summary_json']??null,[])['records_updated']??0),'records_skipped'=>(int)$r['rejected_rows'],'hash'=>$r['checksum_sha256']??''],
            'sessions' => ['id'=>$r['id'],'user_id'=>(int)$r['user_id'],'ip_address'=>$r['ip_address']??'','device'=>$r['device_label']?:($r['user_agent']??'Unknown'),'started_at'=>$r['started_at'],'last_activity'=>$r['last_activity_at'],'expires_at'=>$r['expires_at'],'current'=>hash_equals((string)$r['id'],hash('sha256',session_id())),'status'=>$r['revoked_at']?'revoked':(strtotime($r['expires_at'])<time()?'expired':'active')],
            'access_requests' => ['id'=>(int)$r['id'],'first_name'=>explode(' ',$r['requester_name'],2)[0],'last_name'=>explode(' ',$r['requester_name'],2)[1]??'','email'=>$r['requester_email'],'job_title'=>'','company_id'=>$r['requested_company_id']?(int)$r['requested_company_id']:null,'requested_role'=>$r['requested_role_code']??'read_only','reason'=>$r['request_reason']??'','status'=>$r['status'],'review_note'=>$r['review_note']??'','requested_at'=>$r['created_at'],'submitted_at'=>$r['created_at'],'reviewed_by'=>$r['reviewed_by']?(int)$r['reviewed_by']:null,'reviewed_at'=>$r['reviewed_at']],
            'security_events' => ['id'=>(int)$r['id'],'user_id'=>$r['user_id']?(int)$r['user_id']:null,'event'=>$r['event_type'],'severity'=>$r['severity'],'ip_address'=>$r['ip_address']??'','details'=>is_string($r['details']??null)?json_encode(mysql_repo_json($r['details'],[])):'','created_at'=>$r['occurred_at']],
            'discovery_assignments' => ['id'=>(int)$r['id'],'company_id'=>(int)$r['company_id'],'title'=>$r['title'],'owner_id'=>$r['owner_user_id']?(int)$r['owner_user_id']:null,'reviewer_id'=>$r['reviewer_user_id']?(int)$r['reviewer_user_id']:null,'due_date'=>$r['due_date'],'status'=>$r['status'],'completion'=>(int)$r['completion_pct'],'priority'=>$r['priority']??'medium','description'=>$r['description']??''],
            'discovery_responses' => ['id'=>(int)$r['id'],'assignment_id'=>(int)$r['assignment_id'],'question'=>$r['question_text'],'response'=>$r['response_text']??'','status'=>$r['response_status'],'answered_by'=>$r['answered_by']?(int)$r['answered_by']:null],
            default => $r,
        };
    }, $rows);
    return $cache[$name] = $normalized;
}

function mysql_repo_find(string $collection, int|string $id): ?array
{
    foreach (mysql_repo_collection($collection) as $row) {
        if ((string)($row['id']??'') === (string)$id) return $row;
    }
    return null;
}

function mysql_repo_next_id(string $collection): int
{
    $ids=array_map(static fn(array $r):int=>(int)($r['id']??0),mysql_repo_collection($collection));
    return ($ids?max($ids):0)+1;
}

function mysql_repo_exec(string $sql, array $params=[]): int
{
    $stmt=mysql_repo_pdo()->prepare($sql);
    $stmt->execute($params);
    mysql_repo_clear_cache();
    return $stmt->rowCount();
}

function mysql_repo_upsert(string $collection, array $record): array
{
    $pdo=mysql_repo_pdo();
    $rawId=$record['id']??null;
    $id=is_numeric($rawId)?(int)$rawId:0;
    $now=date('Y-m-d H:i:s');
    $ownsTransaction=!$pdo->inTransaction();
    if($ownsTransaction)$pdo->beginTransaction();
    try {
        switch($collection){
            case 'users':
                $name=trim(($record['first_name']??'').' '.($record['last_name']??''));
                $status=match($record['status']??'active'){'suspended'=>'locked','archived'=>'inactive','pending'=>'pending',default=>'active'};
                if($id){
                    $stmt=$pdo->prepare('UPDATE users SET primary_company_id=:company,name=:name,email=:email,job_title=:job,status=:status,updated_at=NOW() WHERE id=:id');
                    $stmt->execute(['company'=>$record['primary_company_id']?:null,'name'=>$name,'email'=>$record['email'],'job'=>$record['job_title']??null,'status'=>$status,'id'=>$id]);
                }else{
                    $temporary=password_hash(bin2hex(random_bytes(24)),PASSWORD_DEFAULT);
                    $stmt=$pdo->prepare('INSERT INTO users(primary_company_id,name,email,password_hash,job_title,status) VALUES(:company,:name,:email,:password,:job,:status)');
                    $stmt->execute(['company'=>$record['primary_company_id']?:null,'name'=>$name,'email'=>$record['email'],'password'=>$temporary,'job'=>$record['job_title']??null,'status'=>$status]);
                    $id=(int)$pdo->lastInsertId();
                }
                $pdo->prepare('INSERT INTO user_profiles(user_id,first_name,last_name,phone,department,employment_status,password_reset_required,administrative_notes) VALUES(:id,:first,:last,:phone,:department,:status,:reset,:notes) ON DUPLICATE KEY UPDATE first_name=VALUES(first_name),last_name=VALUES(last_name),phone=VALUES(phone),department=VALUES(department),employment_status=VALUES(employment_status),password_reset_required=VALUES(password_reset_required),administrative_notes=VALUES(administrative_notes)')->execute(['id'=>$id,'first'=>$record['first_name'],'last'=>$record['last_name'],'phone'=>$record['phone']??null,'department'=>$record['department']??null,'status'=>$record['status']??'active','reset'=>!empty($record['require_password_reset'])?1:0,'notes'=>$record['admin_notes']??null]);
                $pdo->prepare('DELETE FROM company_memberships WHERE user_id=?')->execute([$id]);
                $cm=$pdo->prepare('INSERT INTO company_memberships(user_id,company_id,is_primary,membership_status,assigned_by) VALUES(?,?,?,?,?)');
                foreach(array_unique(array_map('intval',$record['company_ids']??[])) as $cid){$cm->execute([$id,$cid,$cid===(int)$record['primary_company_id']?1:0,'active',current_user()['id']??null]);}
                $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
                $ur=$pdo->prepare('INSERT IGNORE INTO user_roles(user_id,role_id,company_id) SELECT ?,id,NULL FROM roles WHERE code=?');
                foreach(array_unique($record['role_codes']??['read_only']) as $code){$ur->execute([$id,$code]);}
                break;
            case 'roles':
                $roleStatus=in_array(($record['status']??'active'),['active','archived'],true)?$record['status']:'active';
                if($id){$pdo->prepare('UPDATE roles SET code=?,name=?,description=?,status=? WHERE id=?')->execute([$record['code'],$record['name'],$record['description']??null,$roleStatus,$id]);}
                else{$pdo->prepare('INSERT INTO roles(code,name,description,status) VALUES(?,?,?,?)')->execute([$record['code'],$record['name'],$record['description']??null,$roleStatus]);$id=(int)$pdo->lastInsertId();}
                $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]);
                $rp=$pdo->prepare('INSERT IGNORE INTO role_permissions(role_id,permission_id,granted_by) SELECT ?,id,? FROM permissions WHERE permission_key=?');
                foreach(array_unique($record['permissions']??[]) as $perm){$rp->execute([$id,current_user()['id']??null,$perm]);}
                break;
            case 'companies':
                $active=($record['status']??'active')==='active'?1:0;
                if($id){$pdo->prepare('UPDATE companies SET code=?,name=?,legal_name=?,description=?,is_active=? WHERE id=?')->execute([$record['code'],$record['name'],$record['legal_name']??null,$record['industry']??($record['description']??null),$active,$id]);}
                else{$pdo->prepare('INSERT INTO companies(code,name,legal_name,description,is_active) VALUES(?,?,?,?,?)')->execute([$record['code'],$record['name'],$record['legal_name']??null,$record['industry']??($record['description']??null),$active]);$id=(int)$pdo->lastInsertId();}
                $pdo->prepare('INSERT INTO company_admin_profiles(company_id,status,primary_contact,contact_email,contact_phone,data_owner_user_id,procurement_owner_user_id,accounting_reviewer_user_id,completion_pct,module_settings,retention_days,company_settings) VALUES(:id,:status,:contact,:email,:phone,:data_owner,:procurement,:accounting,:completion,:modules,:retention,:settings) ON DUPLICATE KEY UPDATE status=VALUES(status),primary_contact=VALUES(primary_contact),contact_email=VALUES(contact_email),contact_phone=VALUES(contact_phone),data_owner_user_id=VALUES(data_owner_user_id),procurement_owner_user_id=VALUES(procurement_owner_user_id),accounting_reviewer_user_id=VALUES(accounting_reviewer_user_id),completion_pct=VALUES(completion_pct),module_settings=VALUES(module_settings),retention_days=VALUES(retention_days),company_settings=VALUES(company_settings)')->execute(['id'=>$id,'status'=>$record['status']??'active','contact'=>$record['primary_contact']??null,'email'=>$record['email']??($record['contact_email']??null),'phone'=>$record['phone']??null,'data_owner'=>($record['data_owner_id']??null)?:null,'procurement'=>($record['procurement_owner_id']??null)?:null,'accounting'=>($record['accounting_reviewer_id']??null)?:null,'completion'=>(int)($record['completion']??0),'modules'=>json_encode($record['modules']??[]),'retention'=>(int)($record['retention_days']??2555),'settings'=>json_encode($record['settings']??[])]);
                break;
            case 'suppliers':
                if($id){$pdo->prepare('UPDATE suppliers SET supplier_number=?,normalized_name=?,legal_name=?,website=?,primary_category_id=?,payment_terms=?,preferred_status=?,risk_rating=?,active=?,owner_user_id=?,review_status=? WHERE id=?')->execute([$record['supplier_number'],$record['name'],$record['legal_name']??$record['name'],$record['website']??null,$record['category_id']?:null,$record['payment_terms']??null,$record['status']??'candidate',$record['risk']??'medium',($record['status']??'')==='blocked'?0:1,$record['owner_id']?:null,$record['review_status']??'draft',$id]);}
                else{$pdo->prepare('INSERT INTO suppliers(supplier_number,normalized_name,legal_name,website,primary_category_id,payment_terms,preferred_status,risk_rating,active,created_by,owner_user_id,review_status) VALUES(?,?,?,?,?,?,?,?,1,?,?,?)')->execute([$record['supplier_number'],$record['name'],$record['legal_name']??$record['name'],$record['website']??null,$record['category_id']?:null,$record['payment_terms']??null,$record['status']??'candidate',$record['risk']??'medium',current_user()['id']??null,$record['owner_id']?:null,$record['review_status']??'draft']);$id=(int)$pdo->lastInsertId();}
                $pdo->prepare('DELETE FROM supplier_companies WHERE supplier_id=?')->execute([$id]);
                $rel=$pdo->prepare('INSERT INTO supplier_companies(supplier_id,company_id,account_status) VALUES(?,?,"active")');
                foreach(array_unique(array_map('intval',$record['company_ids']??[])) as $cid){$rel->execute([$id,$cid]);}
                break;
            case 'items':
                $companyIds=array_values(array_unique(array_filter(array_map('intval',$record['company_ids']??[]))));
                $companyId=count($companyIds)===1?$companyIds[0]:null;
                $catalogStatus=in_array(($record['status']??'active'),['active','draft','inactive'],true)?$record['status']:'active';
                $active=$catalogStatus==='active'?1:0;
                if($id){$pdo->prepare('UPDATE items SET item_number=?,company_id=?,sku=?,normalized_description=?,category_id=?,unit_of_measure=?,standard_cost=?,active=?,catalog_status=?,owner_user_id=?,review_status=? WHERE id=?')->execute([$record['item_number'],$companyId,$record['sku']?:null,$record['description'],$record['category_id'],$record['uom'],$record['standard_cost'],$active,$catalogStatus,$record['owner_id']?:null,$record['review_status']??'draft',$id]);}
                else{$pdo->prepare('INSERT INTO items(item_number,company_id,sku,normalized_description,category_id,unit_of_measure,standard_cost,active,catalog_status,created_by,owner_user_id,review_status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$record['item_number'],$companyId,$record['sku']?:null,$record['description'],$record['category_id'],$record['uom'],$record['standard_cost'],$active,$catalogStatus,current_user()['id']??null,$record['owner_id']?:null,$record['review_status']??'draft']);$id=(int)$pdo->lastInsertId();}
                $pdo->prepare('DELETE FROM item_companies WHERE item_id=?')->execute([$id]);
                $relation=$pdo->prepare('INSERT INTO item_companies(item_id,company_id,is_primary) VALUES(?,?,?)');
                foreach($companyIds as $index=>$cid){$relation->execute([$id,$cid,$index===0?1:0]);}
                break;
            case 'discovery_assignments':
                if($id){$pdo->prepare('UPDATE discovery_assignments SET company_id=?,title=?,owner_user_id=?,reviewer_user_id=?,due_date=?,status=?,completion_pct=?,priority=? WHERE id=?')->execute([$record['company_id'],$record['title'],$record['owner_id']?:null,$record['reviewer_id']?:null,$record['due_date']?:null,$record['status'],$record['completion'],$record['priority']??'medium',$id]);}
                else{$pdo->prepare('INSERT INTO discovery_assignments(company_id,module_code,title,owner_user_id,reviewer_user_id,due_date,status,completion_pct,priority,created_by) VALUES(?,"company_discovery",?,?,?,?,?,?,?,?)')->execute([$record['company_id'],$record['title'],$record['owner_id']?:null,$record['reviewer_id']?:null,$record['due_date']?:null,$record['status'],$record['completion'],$record['priority']??'medium',current_user()['id']??null]);$id=(int)$pdo->lastInsertId();}
                break;
            case 'purchase_orders':
                $business='inventory_replenishment';
                if($id){$pdo->prepare('UPDATE purchase_orders SET po_number=?,company_id=?,supplier_id=?,buyer_user_id=?,order_date=?,required_date=?,expected_date=?,status=?,total_amount=?,subtotal=?,review_status=? WHERE id=?')->execute([$record['po_number'],$record['company_id'],$record['supplier_id'],$record['buyer_id']?:null,$record['order_date'],$record['required_date']?:null,$record['expected_date']?:null,$record['status'],$record['total_amount'],$record['total_amount'],$record['review_status']??'draft',$id]);}
                else{$pdo->prepare('INSERT INTO purchase_orders(po_number,company_id,supplier_id,buyer_user_id,business_purpose,order_date,required_date,expected_date,status,total_amount,subtotal,review_status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$record['po_number'],$record['company_id'],$record['supplier_id'],$record['buyer_id']?:null,$business,$record['order_date'],$record['required_date']?:null,$record['expected_date']?:null,$record['status'],$record['total_amount'],$record['total_amount'],$record['review_status']??'draft']);$id=(int)$pdo->lastInsertId();}
                break;
            case 'savings_opportunities':
                $number=$record['opportunity_number']??('SAV-'.date('Y').'-'.str_pad((string)mysql_repo_next_id('savings_opportunities'),4,'0',STR_PAD_LEFT));
                $pipelineStage=in_array(($record['stage']??'draft'),['draft','submitted','review','validated','approved'],true)?$record['stage']:'draft';
                $operationalStatus=in_array(($record['operational_status']??''),['identified','analyzing','negotiating','approved','implementing','completed','rejected'],true)
                    ? $record['operational_status']
                    : match($pipelineStage){'submitted'=>'analyzing','review'=>'negotiating','validated'=>'approved','approved'=>'implementing',default=>'identified'};
                $values=[
                    $record['company_id']?:null,$record['category_id']?:null,$record['supplier_id']?:null,$record['title'],$record['description']??'',
                    $record['opportunity_type']??'other',(float)($record['current_annual_cost']??0),(float)($record['annualized_value']??0),
                    (float)($record['implementation_cost']??0),(float)($record['realized_savings']??0),$operationalStatus,$record['risk']??'medium',
                    $record['owner_id']?:null,$record['due_date']?:null,$record['accounting_validation']??'not_requested',$record['next_step']??null,
                    (int)($record['confidence']??50),$record['review_status']??'draft',$pipelineStage,
                ];
                if($id){
                    $values[]=$id;
                    $pdo->prepare('UPDATE savings_opportunities SET company_id=?,category_id=?,supplier_id=?,title=?,description=?,opportunity_type=?,current_annual_cost=?,expected_annual_savings=?,implementation_cost=?,realized_savings=?,status=?,risk_rating=?,owner_user_id=?,target_date=?,accounting_validation=?,next_step=?,confidence_pct=?,review_status=?,pipeline_stage=? WHERE id=?')->execute($values);
                }else{
                    array_unshift($values,$number);
                    $pdo->prepare('INSERT INTO savings_opportunities(opportunity_number,company_id,category_id,supplier_id,title,description,opportunity_type,current_annual_cost,expected_annual_savings,implementation_cost,realized_savings,status,risk_rating,owner_user_id,target_date,accounting_validation,next_step,confidence_pct,review_status,pipeline_stage) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);
                    $id=(int)$pdo->lastInsertId();
                }
                break;
            case 'supplier_scorecards':
                $periodStart=date('Y-m-01');$periodEnd=date('Y-m-t');
                if(preg_match('/(20\\d{2}) Q([1-4])/',(string)($record['period']??''),$m)){$month=((int)$m[2]-1)*3+1;$periodStart=sprintf('%04d-%02d-01',(int)$m[1],$month);$periodEnd=date('Y-m-t',strtotime($periodStart.' +2 months'));}
                if($id){$pdo->prepare('UPDATE supplier_scorecards SET supplier_id=?,company_id=?,period_start=?,period_end=?,on_time_delivery_pct=?,quality_acceptance_pct=?,responsiveness_score=?,contract_compliance_pct=?,weighted_score=?,review_status=? WHERE id=?')->execute([$record['supplier_id'],$record['company_id']?:null,$periodStart,$periodEnd,$record['on_time_delivery'],$record['quality'],$record['responsiveness']/20,$record['cost_competitiveness'],$record['overall'],$record['status']??'draft',$id]);}
                else{$pdo->prepare('INSERT INTO supplier_scorecards(supplier_id,company_id,period_start,period_end,on_time_delivery_pct,quality_acceptance_pct,responsiveness_score,contract_compliance_pct,weighted_score,review_status) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$record['supplier_id'],$record['company_id']?:null,$periodStart,$periodEnd,$record['on_time_delivery'],$record['quality'],$record['responsiveness']/20,$record['cost_competitiveness'],$record['overall'],$record['status']??'draft']);$id=(int)$pdo->lastInsertId();}
                break;
            case 'workflow_approvals':
                if($id){$pdo->prepare('UPDATE workflow_approvals SET status=?,assigned_reviewer_id=?,decision_note=?,decided_at=IF(? IN ("approved","declined","changes_requested"),NOW(),decided_at) WHERE id=?')->execute([$record['status'],$record['assigned_to']?:null,$record['notes']??null,$record['status'],$id]);}
                else{$pdo->prepare('INSERT INTO workflow_approvals(company_id,module,entity_type,entity_id,title,requested_action,status,requested_by,assigned_reviewer_id,decision_note,due_at) VALUES(?,?,?,?,?,"review",?,?,?,?,?)')->execute([$record['company_id']?:null,$record['module'],$record['entity_type'],$record['entity_id'],$record['title']??record_name($record),$record['status']??'pending',$record['submitted_by']??current_user()['id'],$record['assigned_to']?:null,$record['notes']??null,$record['due_date']?:null]);$id=(int)$pdo->lastInsertId();}
                break;
            case 'notifications':
                if($id){$pdo->prepare('UPDATE notifications SET read_at=? WHERE id=?')->execute([!empty($record['read'])?$now:null,$id]);}
                else{$pdo->prepare('INSERT INTO notifications(user_id,notification_type,title,message,severity,entity_type,entity_id) VALUES(?,"system",?,?,?,?,?)')->execute([$record['user_id'],$record['title'],$record['message'],$record['severity']??'info',$record['entity_type']??null,$record['entity_id']??null]);$id=(int)$pdo->lastInsertId();}
                break;
            case 'security_events':
                $pdo->prepare('INSERT INTO security_events(user_id,event_type,severity,email,ip_address,user_agent,details) VALUES(?,?,?,?,?,?,?)')->execute([$record['user_id']??null,$record['event']??'administrative_event',$record['severity']??'info',$record['email']??null,$record['ip_address']??current_ip(),current_user_agent(),json_encode(['details'=>$record['details']??''])]);$id=(int)$pdo->lastInsertId();break;
            case 'sessions':
                $sessionId=(string)($rawId??'');
                if($sessionId==='') throw new RuntimeException('Production sessions require an existing session id.');
                $pdo->prepare('UPDATE user_sessions SET revoked_at=IF(?="revoked",NOW(),revoked_at),revoked_by=IF(?="revoked",?,revoked_by),last_activity_at=NOW() WHERE id=?')->execute([$record['status'],$record['status'],current_user()['id']??null,$sessionId]);
                $rawId=$sessionId;break;
            case 'access_requests':
                if($id){$pdo->prepare('UPDATE access_requests SET status=?,review_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$record['status'],$record['review_note']??null,current_user()['id']??null,$id]);}
                else{$pdo->prepare('INSERT INTO access_requests(requester_name,requester_email,requested_company_id,requested_role_code,request_reason,status) VALUES(?,?,?,?,?,?)')->execute([trim(($record['first_name']??'').' '.($record['last_name']??'')),$record['email'],$record['company_id']?:null,$record['requested_role']??'read_only',$record['reason']??null,$record['status']??'pending']);$id=(int)$pdo->lastInsertId();}break;
            case 'import_jobs':
                if($id){$pdo->prepare('UPDATE imports SET status=?,mapping_json=?,total_rows=?,accepted_rows=?,rejected_rows=?,validation_summary=? WHERE id=?')->execute([$record['status'],json_encode($record['mapping']??[]),(int)$record['rows_total'],(int)$record['rows_valid'],(int)$record['rows_error'],json_encode($record['validation_summary']??[]),$id]);}
                else{$pdo->prepare('INSERT INTO imports(company_id,import_type,original_filename,stored_path,status,total_rows,accepted_rows,rejected_rows,mapping_json,imported_by,file_checksum_sha256,mime_type) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$record['company_id']?:null,$record['type'],$record['file_name'],$record['stored_path'],$record['status']??'uploaded',(int)($record['rows_total']??0),(int)($record['rows_valid']??0),(int)($record['rows_error']??0),json_encode($record['mapping']??[]),current_user()['id'],$record['checksum']??null,$record['mime_type']??null]);$id=(int)$pdo->lastInsertId();}break;
            case 'import_validation_errors':
                if($id){$status=match($record['status']){'resolved'=>'corrected','accepted'=>'ignored',default=>'open'};$pdo->prepare('UPDATE import_validation_errors SET resolution_status=?,resolved_by=?,resolved_at=IF(?="open",NULL,NOW()) WHERE id=?')->execute([$status,current_user()['id']??null,$status,$id]);}
                else{$pdo->prepare('INSERT INTO import_validation_errors(import_id,source_row_number,column_name,error_code,error_message,raw_value,resolution_status) VALUES(?,?,?,?,?,?,?)')->execute([$record['import_job_id'],$record['row_number'],$record['field']??null,$record['error_code']??'validation',$record['message'],$record['value']??null,'open']);$id=(int)$pdo->lastInsertId();}break;
            case 'import_receipts':
                $summary=['records_created'=>(int)($record['records_created']??0),'records_updated'=>(int)($record['records_updated']??0)];
                $pdo->prepare('INSERT INTO import_receipts(import_id,receipt_number,committed_by,accepted_rows,rejected_rows,checksum_sha256,summary_json,records_created,records_updated) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$record['import_job_id'],$record['receipt_number'],current_user()['id'],(int)($record['records_created']??0)+(int)($record['records_updated']??0),(int)($record['records_skipped']??0),$record['hash']??null,json_encode($summary),(int)($record['records_created']??0),(int)($record['records_updated']??0)]);$id=(int)$pdo->lastInsertId();break;
            case 'comments':
                $pdo->prepare('INSERT INTO record_comments(company_id,entity_type,entity_id,author_user_id,comment_text) VALUES(?,?,?,?,?)')->execute([$record['company_id']?:null,$record['entity_type'],$record['entity_id'],current_user()['id'],$record['body']]);$id=(int)$pdo->lastInsertId();break;
            default: throw new RuntimeException('Unsupported production collection: '.$collection);
        }
        if($ownsTransaction)$pdo->commit();mysql_repo_clear_cache();
        $resultId=$collection==='sessions'?(string)($rawId??''):$id;
        return mysql_repo_find($collection,$resultId)??array_replace($record,['id'=>$resultId]);
    }catch(Throwable $e){if($ownsTransaction&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function mysql_repo_replace_collection(string $name,array $records):void
{
    foreach($records as $record){mysql_repo_upsert($name,$record);}
}

function mysql_repo_delete(string $collection,int $id):bool
{
    $map=['roles'=>['roles','id'],'users'=>['users','id'],'companies'=>['companies','id']];
    if(!isset($map[$collection])) return false;
    [$table,$key]=$map[$collection];
    return mysql_repo_exec("DELETE FROM {$table} WHERE {$key}=:id",['id'=>$id])>0;
}

function mysql_repo_settings():array
{
    $settings=demo_empty_state()['settings'];
    foreach(mysql_repo_rows('SELECT setting_key,setting_value FROM system_settings') as $row){$settings[$row['setting_key']]=mysql_repo_json($row['setting_value'],null);}
    return $settings;
}

function mysql_repo_save_settings(array $settings):void
{
    $stmt=mysql_repo_pdo()->prepare('INSERT INTO system_settings(setting_key,setting_value,description,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)');
    foreach($settings as $key=>$value){$stmt->execute([$key,json_encode($value,JSON_UNESCAPED_SLASHES),'Managed through Admin Console',current_user()['id']??null]);}
}

function mysql_repo_add_audit(string $module,string $action,string $entityType,int|string|null $entityId,mixed $before=null,mixed $after=null,int|string|null $companyId=null):void
{
    $pdo=mysql_repo_pdo();
    static $hasModuleColumn=null;
    if($hasModuleColumn===null){
        try{
            $check=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='audit_logs' AND COLUMN_NAME='module'");
            $hasModuleColumn=(int)$check->fetchColumn()>0;
        }catch(Throwable){$hasModuleColumn=false;}
    }

    if($hasModuleColumn){
        $stmt=$pdo->prepare('INSERT INTO audit_logs(user_id,company_id,module,action,entity_type,entity_id,before_data,after_data,ip_address,user_agent) VALUES(?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([current_user()['id']??null,$companyId==='enterprise'?null:$companyId,$module,$action,$entityType,$entityId,json_encode($before),json_encode($after),current_ip(),current_user_agent()]);
    }else{
        $afterPayload=is_array($after)?array_replace(['module'=>$module],$after):['module'=>$module,'value'=>$after];
        $stmt=$pdo->prepare('INSERT INTO audit_logs(user_id,company_id,action,entity_type,entity_id,before_data,after_data,ip_address,user_agent) VALUES(?,?,?,?,?,?,?,?,?)');
        $stmt->execute([current_user()['id']??null,$companyId==='enterprise'?null:$companyId,$action,$entityType,$entityId,json_encode($before),json_encode($afterPayload),current_ip(),current_user_agent()]);
    }
    mysql_repo_clear_cache();
}
