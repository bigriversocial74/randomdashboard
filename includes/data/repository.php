<?php
declare(strict_types=1);


function production_schema_version(): string
{
    if (!production_database_available()) return demo_mode_active() ? '3.0-phase2-demo' : '3.0-phase2-unavailable';
    try {
        $value = production_database_connection()->query('SELECT version FROM schema_migrations ORDER BY applied_at DESC,id DESC LIMIT 1')->fetchColumn();
        return $value ? (string)$value : '2.0-legacy';
    } catch (Throwable) {
        return '2.0-legacy';
    }
}

function data_environment(): string
{
    return demo_mode_active() ? 'demo' : 'production';
}

function data_is_production(): bool { return data_environment()==='production'; }
function data_is_demo(): bool { return !data_is_production(); }

function data_collection(string $name): array
{
    return data_is_demo() ? demo_collection($name) : mysql_repo_collection($name);
}

function data_visible_collection(string $name, ?array $user=null): array
{
    return array_values(array_filter(data_collection($name),static fn(array $record):bool=>data_record_visible($record,$user)));
}

function data_find(string $collection,int|string $id):?array
{
    return data_is_demo()?demo_find($collection,$id):mysql_repo_find($collection,$id);
}

function data_find_by(string $collection,string $field,mixed $value):?array
{
    foreach(data_collection($collection) as $record){if((string)($record[$field]??'')===(string)$value)return $record;}return null;
}

function data_next_id(string $collection):int
{
    return data_is_demo()?demo_next_id($collection):mysql_repo_next_id($collection);
}

function data_upsert(string $collection,array $record):array
{
    return data_is_demo()?demo_upsert($collection,$record):mysql_repo_upsert($collection,$record);
}

function data_replace_collection(string $collection,array $records):void
{
    if(data_is_demo())demo_replace_collection($collection,$records);else mysql_repo_replace_collection($collection,$records);
}

function data_delete(string $collection,int $id):bool
{
    return data_is_demo()?demo_delete($collection,$id):mysql_repo_delete($collection,$id);
}

function data_settings():array
{
    if(data_is_demo()){$state=demo_state();return $state['settings']??demo_empty_state()['settings'];}
    return mysql_repo_settings();
}

function data_save_settings(array $settings):void
{
    if(data_is_demo()){$state=&demo_state();$state['settings']=$settings;return;}
    mysql_repo_save_settings($settings);
}

function data_company_name(int|string|null $id):string
{
    if($id===null||$id===''||$id==='enterprise')return 'Enterprise View';
    return data_find('companies',(int)$id)['name']??'Unknown company';
}
function data_user_name(int|string|null $id):string{return !$id?'Unassigned':(data_find('users',(int)$id)['name']??'Unknown user');}
function data_supplier_name(int|string|null $id):string{return !$id?'Unknown supplier':(data_find('suppliers',(int)$id)['name']??'Unknown supplier');}
function data_item_name(int|string|null $id):string{$i=$id?data_find('items',(int)$id):null;return $i?($i['item_number'].' · '.$i['description']):'Unknown item';}
function data_role_name(string $code):string{return data_find_by('roles','code',$code)['name']??status_label($code);}

function data_record_visible(array $record,?array $user=null):bool
{
    $user ??= current_user();
    if (!$user) return false;

    $selected = current_company_id();
    $permitted = permitted_company_ids($user);
    $hasCompanyId = array_key_exists('company_id', $record);
    $hasCompanyIds = array_key_exists('company_ids', $record);
    $recordCompany = $hasCompanyId && $record['company_id'] !== null && $record['company_id'] !== ''
        ? (int)$record['company_id']
        : null;
    $recordCompanies = $hasCompanyIds && is_array($record['company_ids'])
        ? array_values(array_unique(array_filter(array_map('intval', $record['company_ids']))))
        : [];

    if ($selected === 'enterprise') {
        if (can_use_enterprise_view($user)) return true;
        if ($recordCompanies) return (bool)array_intersect($recordCompanies, $permitted);
        if ($recordCompany !== null) return in_array($recordCompany, $permitted, true);
        return !$hasCompanyId && !$hasCompanyIds;
    }

    $selectedId = (int)$selected;
    if (!in_array($selectedId, $permitted, true)) return false;
    if ($recordCompanies) return in_array($selectedId, $recordCompanies, true);
    if ($recordCompany !== null) return $recordCompany === $selectedId;

    // Explicitly enterprise-scoped records (company_id NULL) are visible only
    // from Enterprise View. Records with no company fields are platform-global.
    if ($hasCompanyId || $hasCompanyIds) return false;
    return true;
}


function data_permitted_companies(?array $user=null): array
{
    $user ??= current_user();
    if (!$user) return [];
    $allowed = permitted_company_ids($user);
    return array_values(array_filter(data_collection('companies'), static fn(array $company): bool => in_array((int)$company['id'], $allowed, true)));
}

function data_company_within_scope(int|string|null $companyId, ?array $user=null): bool
{
    if ($companyId === null || $companyId === '' || $companyId === 'enterprise') return can_use_enterprise_view($user ?? current_user());
    return in_array((int)$companyId, permitted_company_ids($user ?? current_user()), true);
}

function data_user_within_scope(array $account, ?array $user=null): bool
{
    $user ??= current_user();
    if (!$user) return false;
    if (can('users.administer', $user)) return true;
    $permitted = permitted_company_ids($user);
    $memberships = array_values(array_unique(array_map('intval', $account['company_ids'] ?? [])));
    if (!$memberships && !empty($account['primary_company_id'])) $memberships = [(int)$account['primary_company_id']];
    return $memberships !== [] && count(array_diff($memberships, $permitted)) === 0;
}

function data_admin_visible_users(?array $user=null): array
{
    $user ??= current_user();
    return array_values(array_filter(data_collection('users'), static fn(array $account): bool => data_user_within_scope($account, $user)));
}

function data_assignable_role_codes(?array $user=null): array
{
    $user ??= current_user();
    $blocked = can('users.administer', $user) ? [] : ['system_administrator','executive'];
    $codes = [];
    foreach (data_collection('roles') as $role) {
        if (($role['status'] ?? 'active') !== 'active' || in_array($role['code'], $blocked, true)) continue;
        $codes[] = (string)$role['code'];
    }
    return $codes;
}

function data_add_audit(string $module,string $action,string $entityType,int|string|null $entityId,mixed $before=null,mixed $after=null,int|string|null $companyId=null):void
{
    if(data_is_demo())demo_add_audit($module,$action,$entityType,$entityId,$before,$after,$companyId);
    else mysql_repo_add_audit($module,$action,$entityType,$entityId,$before,$after,$companyId);
}

function data_dashboard_metrics():array
{
    $users=data_collection('users');$pos=data_visible_collection('purchase_orders');$inventory=data_visible_collection('inventory_snapshots');$savings=data_visible_collection('savings_opportunities');$approvals=data_visible_collection('workflow_approvals');
    return ['spend'=>array_sum(array_column($pos,'total_amount')),'open_commitments'=>array_sum(array_map(static fn(array $po):float=>in_array($po['status'],['open','past_due','partially_received'],true)?(float)$po['total_amount']:0,$pos)),'inventory_value'=>array_sum(array_column($inventory,'value')),'savings_pipeline'=>array_sum(array_column($savings,'annualized_value')),'pending_approvals'=>count(array_filter($approvals,static fn(array $a):bool=>$a['status']==='pending')),'data_exceptions'=>count(array_filter(data_visible_collection('data_quality_exceptions'),static fn(array $e):bool=>$e['status']!=='resolved')),'active_users'=>count(array_filter($users,static fn(array $u):bool=>$u['status']==='active'))];
}

function data_admin_metrics():array
{
    $users=data_collection('users');$companies=data_collection('companies');
    return ['total_users'=>count($users),'active_users'=>count(array_filter($users,fn($u)=>$u['status']==='active')),'suspended_users'=>count(array_filter($users,fn($u)=>$u['status']==='suspended')),'pending_access_requests'=>count(array_filter(data_collection('access_requests'),fn($r)=>$r['status']==='pending')),'active_sessions'=>count(array_filter(data_collection('sessions'),fn($s)=>$s['status']==='active')),'companies'=>count($companies),'roles'=>count(array_filter(data_collection('roles'),fn($r)=>($r['status']??'active')==='active')),'failed_sign_ins'=>count(array_filter(data_collection('security_events'),fn($e)=>$e['event']==='failed_sign_in')),'password_resets'=>count(array_filter(data_collection('security_events'),fn($e)=>$e['event']==='password_reset_requested')),'data_completeness'=>(int)round(array_sum(array_column($companies,'completion'))/max(1,count($companies))),'pending_approvals'=>count(array_filter(data_collection('workflow_approvals'),fn($a)=>$a['status']==='pending')),'audit_events'=>count(data_collection('audit_events'))];
}

function data_environment_switch(string $target):never
{
    require_permission('platform.administer');
    if(!in_array($target,['demo','production'],true)){flash('error','Unknown data environment.');redirect_to(app_url('admin/environment.php'));}
    if($target==='demo'){
        $productionUser=current_user();
        $settings=data_settings();
        if(empty($settings['demo_mode_available'])){
            flash('error','Demo Mode is disabled in Platform Settings.');
            redirect_to(app_url('admin/environment.php'));
        }
        data_add_audit('Environment','switched_to_demo','environment',null,['production_user'=>$productionUser['id']??null],['authentication_ended'=>true],null);
        // Revoke the production session before intentionally entering the seeded
        // System Administrator demo account. No records cross environments.
        app_logout();
        if(!demo_start_session(1)){flash('error','Demo environment could not be initialized.');redirect_to(root_url('demo.php'));}
        demo_add_audit('Environment','switched_to_demo','environment',null,['production_user'=>$productionUser['id']??null],['demo_user'=>1],null);
        flash('success','Demo Data environment activated. Production data was not changed.');
        redirect_to(app_url('admin/environment.php'));
    }
    if(!config_present()||!production_database_available()){
        flash('error','Production Data requires config.php and a working MySQL connection.');
        redirect_to(app_url('admin/environment.php'));
    }
    $demoUser=current_user();
    data_add_audit('Environment','production_switch_requested','environment',null,['demo_user'=>$demoUser['id']??null],['authentication_required'=>true],null);
    unset($_SESSION['gruber_demo_mode'],$_SESSION['gruber_demo_user_id'],$_SESSION['gruber_demo_company_id'],$_SESSION['gruber_demo_state']);
    $_SESSION['gruber_pending_environment']='production';
    session_regenerate_id(true);
    flash('info','Authenticate with a production administrator account to activate Production Data.');
    redirect_to(app_url('login.php'));
}
