<?php
declare(strict_types=1);

function data_require_action_permission(string $permission): void
{
    if (!can($permission)) {
        flash('error', 'Your current role does not allow that action in this environment.');
        redirect_to(safe_return_to(post_string('return_to')));
    }
}

function data_action_result(string $type, string $message, string $fallback = 'dashboard.php'): never
{
    flash($type, $message);
    redirect_to(safe_return_to(post_string('return_to'), $fallback));
}

function data_input_array(string $key): array
{
    $value = $_POST[$key] ?? [];
    return is_array($value) ? array_values(array_filter(array_map('strval', $value), static fn(string $v): bool => $v !== '')) : [];
}

function data_require_company_scope(int|string|null $companyId, string $fallback = 'dashboard.php'): void
{
    if (!data_company_within_scope($companyId)) {
        data_action_result('error', 'The selected company is outside your permitted scope.', $fallback);
    }
}

function data_require_company_ids_scope(array $companyIds, string $fallback = 'dashboard.php'): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $companyIds))));
    if ($ids === []) {
        data_action_result('error', 'At least one permitted company is required.', $fallback);
    }
    foreach ($ids as $companyId) {
        data_require_company_scope($companyId, $fallback);
    }
}

function data_require_record_scope(?array $record, string $fallback = 'dashboard.php'): void
{
    if (!$record || !data_record_visible($record)) {
        data_action_result('error', 'That record is unavailable in your current company scope.', $fallback);
    }
}

function data_require_user_scope(?array $account, string $fallback = 'admin/users.php'): void
{
    if (!$account || !data_user_within_scope($account)) {
        data_action_result('error', 'That user is outside your administrative scope.', $fallback);
    }
}

function data_require_user_id_scope(int $userId, string $fallback = 'admin/users.php'): void
{
    if ($userId <= 0) return;
    data_require_user_scope(data_find('users', $userId), $fallback);
}

function data_require_role_assignment_scope(array $roleCodes, string $fallback = 'admin/users.php'): void
{
    $allowed = data_assignable_role_codes();
    foreach (array_unique($roleCodes) as $code) {
        if (!in_array((string)$code, $allowed, true)) {
            data_action_result('error', 'One or more requested roles cannot be assigned by your account.', $fallback);
        }
    }
}

function data_require_supplier_scope(int $supplierId, string $fallback): void
{
    if ($supplierId <= 0) return;
    data_require_record_scope(data_find('suppliers', $supplierId), $fallback);
}

function data_default_company_id(array $user): int
{
    $permitted = permitted_company_ids($user);
    if (current_company_id() !== 'enterprise') return (int)current_company_id();
    $primary = (int)($user['primary_company_id'] ?? 0);
    return in_array($primary, $permitted, true) ? $primary : (int)($permitted[0] ?? 0);
}

function data_require_import_scope(?array $job, string $fallback = 'imports.php'): void
{
    if (!$job) data_action_result('error', 'That import job could not be found.', $fallback);
    if (array_key_exists('company_id', $job) && $job['company_id'] === null) {
        if (!can_use_enterprise_view(current_user()) || current_company_id() !== 'enterprise') {
            data_action_result('error', 'Enterprise import jobs require Enterprise View.', $fallback);
        }
        return;
    }
    if (!data_record_visible($job)) {
        data_action_result('error', 'That import job is unavailable in your current company scope.', $fallback);
    }
}

function data_handle_action(string $action): never
{
    $user = require_app_user();

    switch ($action) {
        case 'switch_environment':
            $target = post_string('environment');
            $confirmation = post_string('confirmation');
            if ($confirmation !== 'SWITCH') {
                data_action_result('error', 'Type SWITCH to confirm the environment change.', 'admin/environment.php');
            }
            data_environment_switch($target);

        case 'switch_company':
            $requested = post_string('company_id', 'enterprise');
            if ($requested === 'enterprise') {
                if (!can_use_enterprise_view($user)) data_action_result('error', 'Enterprise View is not available for this role.');
                if (data_is_demo()) $_SESSION['gruber_demo_company_id'] = 'enterprise';
                else $_SESSION['gruber_production_company_id'] = 'enterprise';
            } else {
                $companyId = (int) $requested;
                if (!in_array($companyId, permitted_company_ids($user), true)) data_action_result('error', 'That company is outside this account’s scope.');
                if (data_is_demo()) $_SESSION['gruber_demo_company_id'] = $companyId;
                else $_SESSION['gruber_production_company_id'] = $companyId;
            }
            data_add_audit('Workspace', 'company_scope_changed', 'company', $requested, null, ['selected' => $requested,'environment'=>data_environment()], $requested);
            data_action_result('success', 'Company scope changed to ' . data_company_name($requested) . '.');

        case 'mark_notifications_read':
            $notifications = data_collection('notifications');
            foreach ($notifications as &$notification) {
                if ((int) ($notification['user_id'] ?? 0) === (int) $user['id']) {
                    $notification['read'] = true;
                }
            }
            unset($notification);
            data_replace_collection('notifications', $notifications);
            data_add_audit('Notifications', 'marked_all_read', 'notification', null, null, ['user_id' => $user['id']], current_company_id());
            data_action_result('success', 'Notifications marked as read.');

        case 'mark_notification_read':
            $id = post_int('id');
            $before = data_find('notifications', $id);
            if ($before && (int) $before['user_id'] === (int) $user['id']) {
                $before['read'] = true;
                data_upsert('notifications', $before);
                data_add_audit('Notifications', 'marked_read', 'notification', $id, ['read' => false], ['read' => true], $before['company_id'] ?? null);
            }
            data_action_result('success', 'Notification updated.', 'notifications.php');

        case 'reset_demo_data':
            if (!data_is_demo()) data_action_result('error', 'Demo reset controls are unavailable in Production Data.', 'admin/environment.php');
            demo_restore_defaults(true);
            data_action_result('success', 'Demo records were reset. Your selected account and company scope were preserved.');

        case 'restore_demo_defaults':
            if (!data_is_demo()) data_action_result('error', 'Demo restore controls are unavailable in Production Data.', 'admin/environment.php');
            demo_restore_defaults(false);
            demo_start_session((int) ($user['id'] ?? 1));
            data_action_result('success', 'The complete default demo dataset was restored.');

        case 'clear_demo_session':
            if (!data_is_demo()) data_action_result('error', 'The current session is not a Demo session.', 'admin/environment.php');
            app_logout();
            flash('success', 'Demo session cleared.');
            redirect_to(root_url('demo.php'));

        case 'save_user':
            data_require_action_permission(post_int('id') ? 'users.edit' : 'users.create');
            $id = post_int('id');
            $before = $id ? data_find('users', $id) : null;
            if ($id) data_require_user_scope($before, 'admin/users.php');
            $first = post_string('first_name');
            $last = post_string('last_name');
            $email = strtolower(post_string('email'));
            if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                data_action_result('error', 'First name, last name, and a valid email are required.', 'admin/users.php');
            }
            foreach (data_collection('users') as $existing) {
                if (strtolower((string) $existing['email']) === $email && (int) $existing['id'] !== $id) {
                    data_action_result('error', 'That email address is already assigned to another user.', 'admin/users.php');
                }
            }
            $companyIds = array_values(array_unique(array_map('intval', data_input_array('company_ids'))));
            $roleCodes = data_input_array('role_codes') ?: ['read_only'];
            $primaryCompany = post_int('primary_company_id');
            $permittedCompanyIds = permitted_company_ids($user);
            $defaultCompany = current_company_id() === 'enterprise'
                ? (int)($user['primary_company_id'] ?? ($permittedCompanyIds[0] ?? 0))
                : (int)current_company_id();
            if (!$companyIds) $companyIds = [$primaryCompany ?: $defaultCompany];
            if (!$primaryCompany) $primaryCompany = (int)($companyIds[0] ?? $defaultCompany);
            if ($primaryCompany && !in_array($primaryCompany, $companyIds, true)) $companyIds[] = $primaryCompany;
            data_require_company_ids_scope($companyIds, 'admin/users.php');
            data_require_company_scope($primaryCompany, 'admin/users.php');
            data_require_role_assignment_scope($roleCodes, 'admin/users.php');
            $status = post_string('status', 'active');
            if (!in_array($status, ['active','suspended','archived'], true)) {
                data_action_result('error', 'The selected user status is invalid.', 'admin/users.php');
            }
            $record = [
                'id' => $id ?: null,
                'first_name' => $first,
                'last_name' => $last,
                'name' => trim($first . ' ' . $last),
                'email' => $email,
                'phone' => post_string('phone'),
                'job_title' => post_string('job_title'),
                'department' => post_string('department'),
                'status' => $status,
                'primary_company_id' => $primaryCompany,
                'company_ids' => $companyIds,
                'role_codes' => $roleCodes,
                'last_login' => $before['last_login'] ?? null,
                'require_password_reset' => !empty($_POST['require_password_reset']),
                'created_at' => $before['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'admin_notes' => post_string('admin_notes'),
            ];
            $saved = data_upsert('users', $record);
            data_add_audit('Admin', $before ? 'user_updated' : 'user_created', 'user', $saved['id'], $before, $saved, $saved['primary_company_id']);
            data_action_result('success', ($before ? 'User updated: ' : 'User created: ') . $saved['name'] . '.', 'admin/users.php');

        case 'user_status':
            data_require_action_permission('users.edit');
            $id = post_int('id');
            $status = post_string('status');
            $allowed = ['active','suspended','archived'];
            $record = data_find('users', $id);
            if ($record) data_require_user_scope($record, 'admin/users.php');
            if (!$record || !in_array($status, $allowed, true)) {
                data_action_result('error', 'The user status action is not valid.', 'admin/users.php');
            }
            if ($id === (int) $user['id'] && $status !== 'active') {
                data_action_result('error', 'You cannot suspend or archive the account currently in use.', 'admin/users.php');
            }
            $before = $record;
            $record['status'] = $status;
            $record['updated_at'] = date('Y-m-d H:i:s');
            data_upsert('users', $record);
            data_add_audit('Admin', 'user_' . $status, 'user', $id, ['status'=>$before['status']], ['status'=>$status], $record['primary_company_id']);
            data_action_result('success', $record['name'] . ' is now ' . status_label($status) . '.', 'admin/users.php');

        case 'require_password_reset':
            data_require_action_permission('users.administer');
            $id = post_int('id');
            $record = data_find('users', $id);
            data_require_user_scope($record, 'admin/users.php');
            $record['require_password_reset'] = true;
            $record['updated_at'] = date('Y-m-d H:i:s');
            data_upsert('users', $record);
            data_upsert('security_events', [
                'id'=>null,'user_id'=>$id,'event'=>'password_reset_requested','severity'=>'info',
                'ip_address'=>current_ip(),'details'=>'Administrator required a password change at the next successful sign-in. No password was emailed.','created_at'=>date('Y-m-d H:i:s')
            ]);
            data_add_audit('Security', 'password_reset_required', 'user', $id, null, ['require_password_reset'=>true], $record['primary_company_id']);
            data_action_result('success', 'A password change is now required at the next successful sign-in. No password was emailed.', 'admin/users.php');

        case 'revoke_user_sessions':
            data_require_action_permission('users.administer');
            $id = post_int('id');
            data_require_user_id_scope($id, 'admin/users.php');
            $sessions = data_collection('sessions');
            $count = 0;
            foreach ($sessions as &$session) {
                if ((int) $session['user_id'] === $id && empty($session['current']) && $session['status'] === 'active') {
                    $session['status'] = 'revoked';
                    $count++;
                }
            }
            unset($session);
            data_replace_collection('sessions', $sessions);
            data_add_audit('Security', 'user_sessions_revoked', 'user', $id, null, ['count'=>$count], data_find('users', $id)['primary_company_id'] ?? null);
            data_action_result('success', $count . ' session(s) revoked.', 'admin/users.php');

        case 'save_role':
            data_require_action_permission(post_int('id') ? 'roles.edit' : 'roles.create');
            $id = post_int('id');
            $before = $id ? data_find('roles', $id) : null;
            if ($id && !$before) data_action_result('error', 'Role not found.', 'admin/roles.php');
            $code = strtolower((string) preg_replace('/[^a-z0-9]+/', '_', post_string('code')));
            $code = trim($code, '_');
            $name = trim(post_string('name'));
            $status = post_string('status', 'active');
            if ($code === '' || $name === '') data_action_result('error', 'Role code and name are required.', 'admin/roles.php');
            if (!preg_match('/^[a-z][a-z0-9_]{1,79}$/', $code)) data_action_result('error', 'Role code must begin with a letter and contain only lowercase letters, numbers, and underscores.', 'admin/roles.php');
            if (!in_array($status, ['active','archived'], true)) data_action_result('error', 'Role status is invalid.', 'admin/roles.php');
            if ($before && !empty($before['system']) && ($code !== $before['code'] || $status !== 'active')) data_action_result('error', 'Seeded system role codes and active status cannot be changed.', 'admin/roles.php');
            foreach (data_collection('roles') as $candidate) {
                if ((int)($candidate['id'] ?? 0) !== $id && (string)($candidate['code'] ?? '') === $code) data_action_result('error', 'That role code is already in use.', 'admin/roles.php');
            }
            $requestedPermissions = data_input_array('permissions');
            $permissions = admin_normalize_permissions($requestedPermissions);
            if (array_diff(array_values(array_unique($requestedPermissions)), $permissions)) data_action_result('error', 'One or more selected permissions are not recognized.', 'admin/roles.php');
            $record = [
                'id'=>$id ?: null,'code'=>$code,'name'=>mb_substr($name,0,120),'description'=>mb_substr(post_string('description'),0,500),
                'system'=>$before['system'] ?? false,'status'=>$status,'permissions'=>$permissions,
            ];
            $saved = data_upsert('roles', $record);
            data_add_audit('Admin', $before ? 'role_updated' : 'role_created', 'role', $saved['id'], $before, $saved, null);
            data_action_result('success', 'Role saved: ' . $saved['name'] . '.', 'admin/roles.php');

        case 'clone_role':
            data_require_action_permission('roles.create');
            $id = post_int('id');
            $source = data_find('roles', $id);
            if (!$source) {
                data_action_result('error', 'Source role not found.', 'admin/roles.php');
            }
            $clone = $source;
            $clone['id'] = null;
            $clone['code'] = $source['code'] . '_copy_' . data_next_id('roles');
            $clone['name'] = $source['name'] . ' Copy';
            $clone['system'] = false;
            $clone['status'] = 'active';
            $saved = data_upsert('roles', $clone);
            data_add_audit('Admin', 'role_cloned', 'role', $saved['id'], ['source_role_id'=>$id], $saved, null);
            data_action_result('success', 'Custom role cloned from ' . $source['name'] . '.', 'admin/roles.php');

        case 'archive_role':
            data_require_action_permission('roles.delete');
            $id = post_int('id');
            $role = data_find('roles', $id);
            if (!$role || !empty($role['system'])) {
                data_action_result('error', 'Seeded system roles cannot be archived.', 'admin/roles.php');
            }
            $before = $role;
            $role['status'] = 'archived';
            data_upsert('roles', $role);
            data_add_audit('Admin', 'role_archived', 'role', $id, $before, $role, null);
            data_action_result('success', 'Custom role archived.', 'admin/roles.php');

        case 'save_company':
            data_require_action_permission('companies.edit');
            $id = post_int('id');
            $before = data_find('companies', $id);
            if (!$before) data_action_result('error', 'Company not found.', 'admin/companies.php');
            data_require_company_scope($id, 'admin/companies.php');
            $name = trim(post_string('name', (string)$before['name']));
            $code = strtoupper(trim(post_string('code', (string)$before['code'])));
            $status = post_string('status', (string)$before['status']);
            $email = strtolower(trim(post_string('email', (string)($before['email'] ?? $before['contact_email'] ?? ''))));
            $retentionDays = post_int('retention_days', (int)$before['retention_days']);
            $modules = admin_normalize_modules(data_input_array('modules'));
            if ($name === '' || !preg_match('/^[A-Z0-9][A-Z0-9_-]{1,15}$/', $code)) data_action_result('error', 'Company name and a valid 2–16 character company code are required.', 'admin/companies.php');
            if (!in_array($status, ['active','inactive'], true)) data_action_result('error', 'Company status is invalid.', 'admin/companies.php');
            if ($email !== '' && !admin_email_valid($email)) data_action_result('error', 'Enter a valid company contact email.', 'admin/companies.php');
            if ($retentionDays < 30 || $retentionDays > 36500) data_action_result('error', 'Retention must be between 30 and 36,500 days.', 'admin/companies.php');
            if (!$modules) data_action_result('error', 'At least one supported company module must remain enabled.', 'admin/companies.php');
            foreach (data_collection('companies') as $candidate) {
                if ((int)($candidate['id'] ?? 0) !== $id && strtoupper((string)($candidate['code'] ?? '')) === $code) data_action_result('error', 'That company code is already in use.', 'admin/companies.php');
            }
            $record = array_replace($before, [
                'name'=>mb_substr($name,0,160),'code'=>$code,'status'=>$status,
                'industry'=>mb_substr(post_string('industry',(string)($before['industry']??$before['description']??'')),0,240),
                'phone'=>mb_substr(post_string('phone',(string)($before['phone']??'')),0,40),'email'=>$email,
                'data_owner_id'=>post_int('data_owner_id',(int)$before['data_owner_id']),
                'procurement_owner_id'=>post_int('procurement_owner_id',(int)$before['procurement_owner_id']),
                'accounting_reviewer_id'=>post_int('accounting_reviewer_id',(int)$before['accounting_reviewer_id']),
                'retention_days'=>$retentionDays,'modules'=>$modules,
            ]);
            data_require_user_id_scope((int)$record['data_owner_id'], 'admin/companies.php');
            data_require_user_id_scope((int)$record['procurement_owner_id'], 'admin/companies.php');
            data_require_user_id_scope((int)$record['accounting_reviewer_id'], 'admin/companies.php');
            data_upsert('companies', $record);
            data_add_audit('Admin', 'company_updated', 'company', $id, $before, $record, $id);
            data_action_result('success', $record['name'] . ' settings updated.', 'admin/companies.php');

        case 'revoke_session':
            data_require_action_permission('security.administer');
            $id = post_string('id');
            $session = data_find('sessions', $id);
            if (!$session) {
                data_action_result('error', 'Session not found.', 'admin/sessions.php');
            }
            if (!empty($session['current'])) {
                data_action_result('error', 'The current session cannot be revoked from this action.', 'admin/sessions.php');
            }
            $before = $session;
            $session['status'] = 'revoked';
            data_upsert('sessions', $session);
            data_add_audit('Security', 'session_revoked', 'session', $id, $before, $session, null);
            data_action_result('success', 'Session revoked.', 'admin/sessions.php');

        case 'revoke_other_sessions':
            data_require_action_permission('security.administer');
            $sessions = data_collection('sessions');
            $count = 0;
            foreach ($sessions as &$session) {
                if (empty($session['current']) && $session['status'] === 'active') {
                    $session['status'] = 'revoked';
                    $count++;
                }
            }
            unset($session);
            data_replace_collection('sessions', $sessions);
            data_add_audit('Security', 'all_other_sessions_revoked', 'session', null, null, ['count'=>$count], null);
            data_action_result('success', $count . ' other active session(s) revoked.', 'admin/sessions.php');

        case 'access_request_review':
            data_require_action_permission('users.administer');
            $id = post_int('id');
            $decision = post_string('decision');
            $request = data_find('access_requests', $id);
            if (!$request || !in_array($decision, ['approved','declined'], true)) {
                data_action_result('error', 'Access request action is invalid.', 'admin/access-requests.php');
            }
            $before = $request;
            $request['status'] = $decision;
            $request['requested_role'] = post_string('role_code', $request['requested_role']);
            $request['company_id'] = post_int('company_id', $request['company_id']);
            data_require_role_assignment_scope([$request['requested_role']], 'admin/access-requests.php');
            data_require_company_scope($request['company_id'], 'admin/access-requests.php');
            $request['review_note'] = post_string('review_note');
            $request['reviewed_by'] = $user['id'];
            $request['reviewed_at'] = date('Y-m-d H:i:s');
            data_upsert('access_requests', $request);
            if ($decision === 'approved') {
                data_upsert('users', [
                    'id'=>null,'first_name'=>$request['first_name'],'last_name'=>$request['last_name'],
                    'name'=>$request['first_name'].' '.$request['last_name'],'email'=>strtolower($request['email']),
                    'phone'=>'','job_title'=>$request['job_title'],'department'=>'',
                    'status'=>'active','primary_company_id'=>$request['company_id'],'company_ids'=>[$request['company_id']],
                    'role_codes'=>[$request['requested_role']],'last_login'=>null,'require_password_reset'=>true,
                    'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),
                    'admin_notes'=>'Created from approved access request #'.$id,
                ]);
            }
            data_add_audit('Admin', 'access_request_' . $decision, 'access_request', $id, $before, $request, $request['company_id']);
            data_action_result('success', 'Access request ' . $decision . '.', 'admin/access-requests.php');

        case 'save_security_settings':
            data_require_action_permission('security.administer');
            $settings = data_settings();
            $before = $settings;
            $passwordMin = post_int('password_min_length', 12);
            $attemptLimit = post_int('login_attempt_limit', 5);
            $lockoutMinutes = post_int('lockout_minutes', 15);
            $sessionLifetime = post_int('session_lifetime', 120);
            $resetLifetime = post_int('password_reset_lifetime', 60);
            if ($passwordMin < 8 || $passwordMin > 128) data_action_result('error', 'Password minimum must be between 8 and 128 characters.', 'admin/security.php');
            if ($attemptLimit < 3 || $attemptLimit > 20) data_action_result('error', 'Login attempt limit must be between 3 and 20.', 'admin/security.php');
            if ($lockoutMinutes < 5 || $lockoutMinutes > 1440 || $sessionLifetime < 15 || $sessionLifetime > 1440 || $resetLifetime < 15 || $resetLifetime > 1440) data_action_result('error', 'Security time limits are outside the permitted range.', 'admin/security.php');
            $settings['password_min_length'] = $passwordMin;
            $settings['password_require_mixed_case'] = !empty($_POST['password_require_mixed_case']);
            $settings['password_require_number'] = !empty($_POST['password_require_number']);
            $settings['password_require_symbol'] = !empty($_POST['password_require_symbol']);
            $settings['login_attempt_limit'] = $attemptLimit;
            $settings['lockout_minutes'] = $lockoutMinutes;
            $settings['session_lifetime'] = $sessionLifetime;
            $settings['password_reset_lifetime'] = $resetLifetime;
            data_save_settings($settings);
            data_add_audit('Security', 'security_settings_updated', 'settings', null, $before, $settings, null);
            data_action_result('success', 'Security settings updated for '.data_environment().'.', 'admin/security.php');

        case 'save_platform_settings':
            data_require_action_permission('settings.administer');
            $settings = data_settings();
            $before = $settings;
            $platformName = trim(post_string('platform_name', (string)$settings['platform_name']));
            $supportContact = strtolower(trim(post_string('support_contact', (string)$settings['support_contact'])));
            $emailSender = strtolower(trim(post_string('email_sender', (string)$settings['email_sender'])));
            $timezone = post_string('default_timezone', (string)$settings['default_timezone']);
            $dateFormat = post_string('date_format', (string)$settings['date_format']);
            $currency = post_string('currency', 'USD');
            $importLimit = post_int('import_row_limit', (int)$settings['import_row_limit']);
            $uploadLimit = post_int('upload_limit_mb', (int)$settings['upload_limit_mb']);
            $retentionDays = post_int('data_retention_days', (int)$settings['data_retention_days']);
            if ($platformName === '' || mb_strlen($platformName) > 120) data_action_result('error', 'Platform name is required and must be 120 characters or fewer.', 'admin/settings.php');
            if (!admin_email_valid($supportContact) || !admin_email_valid($emailSender)) data_action_result('error', 'Support contact and email sender must be valid email addresses.', 'admin/settings.php');
            if (!in_array($timezone, timezone_identifiers_list(), true)) data_action_result('error', 'Default timezone is invalid.', 'admin/settings.php');
            if (!in_array($dateFormat, ['M j, Y','m/d/Y'], true) || $currency !== 'USD') data_action_result('error', 'Date or currency formatting is invalid.', 'admin/settings.php');
            if ($importLimit < 100 || $importLimit > 1000000 || $uploadLimit < 1 || $uploadLimit > 100 || $retentionDays < 30 || $retentionDays > 36500) data_action_result('error', 'Import, upload, or retention limits are outside the permitted range.', 'admin/settings.php');
            $requestedTypes = preg_split('/\s*,\s*/', strtolower(post_string('allowed_file_types','csv,xlsx'))) ?: [];
            $types = array_values(array_unique(array_filter($requestedTypes, static fn(string $type): bool => in_array($type, ['csv','xlsx'], true))));
            if (!$types || count($types) !== count(array_values(array_unique(array_filter($requestedTypes))))) data_action_result('error', 'Allowed file types may contain only csv and xlsx.', 'admin/settings.php');
            $settings = array_replace($settings, [
                'platform_name'=>$platformName,'support_contact'=>$supportContact,'default_timezone'=>$timezone,
                'date_format'=>$dateFormat,'currency'=>$currency,'email_sender'=>$emailSender,
                'import_row_limit'=>$importLimit,'upload_limit_mb'=>$uploadLimit,'data_retention_days'=>$retentionDays,
                'allowed_file_types'=>$types,'demo_mode_available'=>!empty($_POST['demo_mode_available']),
                'maintenance_mode'=>!empty($_POST['maintenance_mode']),'agent_workspace_access'=>!empty($_POST['agent_workspace_access']),
                'approved_data_only_agent'=>!empty($_POST['approved_data_only_agent']),
            ]);
            data_save_settings($settings);
            data_add_audit('Admin', 'platform_settings_updated', 'settings', null, $before, $settings, null);
            data_action_result('success', 'Platform settings updated for '.data_environment().'.', 'admin/settings.php');

        case 'save_supplier':
            data_require_action_permission(post_int('id') ? 'suppliers.edit' : 'suppliers.create');
            $id = post_int('id');
            $before = $id ? data_find('suppliers', $id) : null;
            if ($id) data_require_record_scope($before, 'suppliers.php');
            $name = post_string('name');
            if ($name === '') {
                data_action_result('error', 'Supplier name is required.', 'suppliers.php');
            }
            $categoryId = post_int('category_id', 1);
            $category = data_find('categories', $categoryId);
            $companyIds = array_values(array_unique(array_map('intval', data_input_array('company_ids'))));
            if (!$companyIds) $companyIds = [data_default_company_id($user)];
            data_require_company_ids_scope($companyIds, 'suppliers.php');
            $ownerId = post_int('owner_id', (int)$user['id']);
            data_require_user_id_scope($ownerId, 'suppliers.php');
            $supplierStatus = post_string('status', 'candidate');
            $supplierRisk = post_string('risk', 'medium');
            if (!in_array($supplierStatus, ['preferred','approved','candidate','conditional','blocked'], true)
                || !in_array($supplierRisk, ['low','medium','high','critical'], true)) {
                data_action_result('error', 'The supplier status or risk value is invalid.', 'suppliers.php');
            }
            $website = post_string('website');
            if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
                data_action_result('error', 'Enter a valid supplier website URL.', 'suppliers.php');
            }
            $supplierNumber = post_string('supplier_number','SUP-'.str_pad((string)data_next_id('suppliers'),4,'0',STR_PAD_LEFT));
            if ($supplierNumber === '') data_action_result('error', 'Supplier number is required.', 'suppliers.php');
            $record = [
                'id'=>$id ?: null,'supplier_number'=>$supplierNumber,
                'name'=>$name,'legal_name'=>post_string('legal_name',$name),'company_ids'=>$companyIds,
                'category_id'=>$categoryId,'category'=>$category['name'] ?? 'Uncategorized','status'=>$supplierStatus,
                'risk'=>$supplierRisk,'owner_id'=>$ownerId,
                'annual_spend'=>max(0,(float)post_string('annual_spend','0')),'payment_terms'=>post_string('payment_terms','Net 30'),
                'website'=>$website,'review_status'=>$before['review_status'] ?? 'draft','sample'=>true,
            ];
            $saved = data_upsert('suppliers',$record);
            data_add_audit('Suppliers',$before?'updated':'created','supplier',$saved['id'],$before,$saved,current_company_id());
            data_action_result('success','Supplier saved: '.$saved['name'].'.','suppliers.php');

        case 'save_item':
            data_require_action_permission(post_int('id') ? 'items.edit' : 'items.create');
            $id=post_int('id'); $before=$id?data_find('items',$id):null;
            if ($id) data_require_record_scope($before, 'items.php');
            $description=post_string('description');
            if ($description==='') data_action_result('error','Item description is required.','items.php');
            $companyIds = array_values(array_unique(array_map('intval', data_input_array('company_ids'))));
            if (!$companyIds) $companyIds = [data_default_company_id($user)];
            data_require_company_ids_scope($companyIds, 'items.php');
            $ownerId = post_int('owner_id', (int)$user['id']);
            data_require_user_id_scope($ownerId, 'items.php');
            $itemStatus = post_string('status', 'active');
            if (!in_array($itemStatus, ['active','draft','inactive'], true)) {
                data_action_result('error', 'The item catalog status is invalid.', 'items.php');
            }
            $itemNumber = post_string('item_number','ITEM-'.str_pad((string)data_next_id('items'),4,'0',STR_PAD_LEFT));
            $uom = strtoupper(post_string('uom','EA'));
            if ($itemNumber === '' || $uom === '') data_action_result('error','Item number and unit of measure are required.','items.php');
            $record=[
                'id'=>$id?:null,'item_number'=>$itemNumber,
                'sku'=>post_string('sku'),'description'=>$description,'category_id'=>post_int('category_id',1),
                'company_ids'=>$companyIds,
                'uom'=>$uom,'standard_cost'=>max(0,(float)post_string('standard_cost','0')),
                'owner_id'=>$ownerId,'status'=>$itemStatus,
                'review_status'=>$before['review_status']??'draft','sample'=>true,
            ];
            $saved=data_upsert('items',$record);
            data_add_audit('Items',$before?'updated':'created','item',$saved['id'],$before,$saved,current_company_id());
            data_action_result('success','Item saved: '.$saved['item_number'].'.','items.php');

        case 'save_discovery':
            data_require_action_permission(post_int('id') ? 'discovery.edit' : 'discovery.create');
            $id=post_int('id'); $before=$id?data_find('discovery_assignments',$id):null;
            if ($id) data_require_record_scope($before, 'discovery.php');
            $companyId = post_int('company_id', data_default_company_id($user));
            $ownerId = post_int('owner_id', (int)$user['id']);
            $reviewerId = post_int('reviewer_id', (int)$user['id']);
            data_require_company_scope($companyId, 'discovery.php');
            data_require_user_id_scope($ownerId, 'discovery.php');
            data_require_user_id_scope($reviewerId, 'discovery.php');
            $discoveryStatus = post_string('status', 'not_started');
            $priority = post_string('priority', 'medium');
            if (!in_array($discoveryStatus, ['draft','not_started','assigned','in_progress'], true)
                || !in_array($priority, ['low','medium','high','critical'], true)) {
                data_action_result('error', 'The discovery status or priority is invalid.', 'discovery.php');
            }
            $record=[
                'id'=>$id?:null,'company_id'=>$companyId,
                'title'=>post_string('title'),'owner_id'=>$ownerId,
                'reviewer_id'=>$reviewerId,'due_date'=>post_string('due_date'),
                'status'=>$discoveryStatus,'completion'=>min(100,max(0,post_int('completion',0))),
                'priority'=>$priority
            ];
            if ($record['title']==='') data_action_result('error','Assignment title is required.','discovery.php');
            $saved=data_upsert('discovery_assignments',$record);
            data_add_audit('Discovery',$before?'updated':'created','discovery_assignment',$saved['id'],$before,$saved,$saved['company_id']);
            data_action_result('success','Discovery assignment saved.','discovery.php');

        case 'save_purchase_order':
            data_require_action_permission(post_int('id') ? 'purchase_orders.edit' : 'purchase_orders.create');
            $id=post_int('id'); $before=$id?data_find('purchase_orders',$id):null;
            if ($id) data_require_record_scope($before, 'purchase-orders.php');
            $companyId = post_int('company_id', data_default_company_id($user));
            $supplierId = post_int('supplier_id', 0);
            $buyerId = post_int('buyer_id', (int)$user['id']);
            data_require_company_scope($companyId, 'purchase-orders.php');
            data_require_supplier_scope($supplierId, 'purchase-orders.php');
            data_require_user_id_scope($buyerId, 'purchase-orders.php');
            $poStatus = post_string('status', 'draft');
            if (!in_array($poStatus, ['draft','pending_approval','open','partially_received','received','past_due','canceled','closed'], true)) {
                data_action_result('error', 'The purchase-order status is invalid.', 'purchase-orders.php');
            }
            $poNumber = post_string('po_number','PO-'.str_pad((string)data_next_id('purchase_orders'),5,'0',STR_PAD_LEFT));
            $orderDate = post_string('order_date',date('Y-m-d'));
            $requiredDate = post_string('required_date');
            $expectedDate = post_string('expected_date');
            foreach (['order date'=>$orderDate,'required date'=>$requiredDate,'expected date'=>$expectedDate] as $label=>$dateValue) {
                $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
                if (!$parsed || $parsed->format('Y-m-d') !== $dateValue) data_action_result('error', 'Enter a valid '.$label.'.', 'purchase-orders.php');
            }
            if ($poNumber === '') data_action_result('error', 'Purchase-order number is required.', 'purchase-orders.php');
            if ($requiredDate < $orderDate || $expectedDate < $orderDate) data_action_result('error', 'Required and expected dates cannot be earlier than the order date.', 'purchase-orders.php');
            $record=[
                'id'=>$id?:null,'po_number'=>$poNumber,
                'company_id'=>$companyId,
                'supplier_id'=>$supplierId,'order_date'=>$orderDate,
                'required_date'=>$requiredDate,'expected_date'=>$expectedDate,
                'status'=>$poStatus,'total_amount'=>max(0,(float)post_string('total_amount','0')),
                'buyer_id'=>$buyerId,'review_status'=>$before['review_status']??'draft'
            ];
            $saved=data_upsert('purchase_orders',$record);
            data_add_audit('Purchase Orders',$before?'updated':'created','purchase_order',$saved['id'],$before,$saved,$saved['company_id']);
            data_action_result('success','Purchase order saved: '.$saved['po_number'].'.','purchase-orders.php');
        case 'save_savings':
            data_require_action_permission(post_int('id') ? 'savings.edit' : 'savings.create');
            $id=post_int('id'); $before=$id?data_find('savings_opportunities',$id):null;
            if ($id) data_require_record_scope($before, 'savings.php');
            $companyId = post_int('company_id', data_default_company_id($user));
            $ownerId = post_int('owner_id', (int)$user['id']);
            $supplierId = post_int('supplier_id', 0);
            data_require_company_scope($companyId, 'savings.php');
            data_require_user_id_scope($ownerId, 'savings.php');
            data_require_supplier_scope($supplierId, 'savings.php');
            $pipelineStage = post_string('stage', 'draft');
            $opportunityType = post_string('opportunity_type', 'other');
            $risk = post_string('risk', 'medium');
            $accountingValidation = post_string('accounting_validation', 'not_requested');
            $operationalStatus = post_string('operational_status');
            $allowedStages = ['draft','submitted','review','validated','approved'];
            $allowedTypes = ['price','supplier_consolidation','freight','payment_terms','inventory_reduction','transfer','process','quality','warranty_recovery','specification','other'];
            $allowedRisk = ['low','medium','high','critical'];
            $allowedAccounting = ['not_requested','pending','validated','rejected'];
            $allowedOperational = ['identified','analyzing','negotiating','approved','implementing','completed','rejected'];
            if (!in_array($pipelineStage, $allowedStages, true) || !in_array($opportunityType, $allowedTypes, true)
                || !in_array($risk, $allowedRisk, true) || !in_array($accountingValidation, $allowedAccounting, true)) {
                data_action_result('error', 'One or more savings opportunity values are invalid.', 'savings.php');
            }
            if (!in_array($operationalStatus, $allowedOperational, true)) {
                $operationalStatus = match($pipelineStage){'submitted'=>'analyzing','review'=>'negotiating','validated'=>'approved','approved'=>'implementing',default=>'identified'};
            }
            $realizedSavings = max(0, (float)post_string('realized_savings', (string)($before['realized_savings']??0)));
            if ($accountingValidation === 'validated' && $realizedSavings > 0 && $pipelineStage === 'approved') $operationalStatus = 'completed';
            $categoryId = post_int('category_id', (int)($before['category_id']??1));
            $category = data_find('categories', $categoryId);
            $record=[
                'id'=>$id?:null,'opportunity_number'=>$before['opportunity_number']??null,'company_id'=>$companyId,'category_id'=>$categoryId,
                'supplier_id'=>$supplierId?:null,'title'=>post_string('title'),'description'=>post_string('description'),
                'opportunity_type'=>$opportunityType,'category'=>$category['name']??status_label($opportunityType),'owner_id'=>$ownerId,'stage'=>$pipelineStage,
                'operational_status'=>$operationalStatus,'annualized_value'=>max(0,(float)post_string('annualized_value','0')),
                'current_annual_cost'=>max(0,(float)post_string('current_annual_cost','0')),'implementation_cost'=>max(0,(float)post_string('implementation_cost','0')),
                'realized_savings'=>$realizedSavings,'confidence'=>min(100,max(0,post_int('confidence',50))),'risk'=>$risk,
                'due_date'=>post_string('due_date'),'accounting_validation'=>$accountingValidation,'next_step'=>post_string('next_step'),
                'review_status'=>$before['review_status']??'draft'
            ];
            if ($record['title']==='') data_action_result('error','Savings opportunity title is required.','savings.php');
            if ($record['description']==='' || $record['next_step']==='') data_action_result('error','Description and next step are required for an accountable savings record.','savings.php');
            if (!import_valid_iso_date((string)$record['due_date'])) data_action_result('error','Enter a valid savings target date.','savings.php');
            if ($record['current_annual_cost']>0 && $record['annualized_value']>$record['current_annual_cost']) data_action_result('error','Expected annual savings cannot exceed the current annual cost baseline.','savings.php');
            if ($record['accounting_validation']==='validated' && ($record['stage']!=='approved' || $record['realized_savings']<=0)) data_action_result('error','Accounting validation requires the realization stage and a realized-savings amount.','savings.php');
            $saved=data_upsert('savings_opportunities',$record);
            data_add_audit('Savings',$before?'updated':'created','savings_opportunity',$saved['id'],$before,$saved,$saved['company_id']);
            data_action_result('success','Savings opportunity saved.','savings.php');

        case 'advance_savings_stage':
            data_require_action_permission('savings.edit');
            $id=post_int('id'); $record=data_find('savings_opportunities',$id);
            data_require_record_scope($record, 'savings.php');
            $next=['draft'=>'submitted','submitted'=>'review','review'=>'validated','validated'=>'approved'][(string)($record['stage']??'draft')]??null;
            if(!$next) data_action_result('error','This opportunity is already in the realization stage.','savings.php');
            if(trim((string)($record['description']??''))===''||trim((string)($record['next_step']??''))===''||(float)($record['annualized_value']??0)<=0||!import_valid_iso_date((string)($record['due_date']??''))) data_action_result('error','Complete the description, expected value, target date, and next step before advancing this opportunity.','savings.php');
            $before=$record;
            $record['stage']=$next;
            $record['operational_status']=match($next){'submitted'=>'analyzing','review'=>'negotiating','validated'=>'approved','approved'=>'implementing',default=>'identified'};
            if($next==='validated' && ($record['accounting_validation']??'not_requested')==='not_requested') $record['accounting_validation']='pending';
            $saved=data_upsert('savings_opportunities',$record);
            data_add_audit('Savings','pipeline_stage_advanced','savings_opportunity',$id,$before,$saved,$saved['company_id']??null);
            data_action_result('success','Savings opportunity moved to '.status_label($next).'.','savings.php');

        case 'save_scorecard':
            data_require_action_permission(post_int('id') ? 'scorecards.edit' : 'scorecards.create');
            $id=post_int('id'); $before=$id?data_find('supplier_scorecards',$id):null;
            if ($id) data_require_record_scope($before, 'scorecards.php');
            $companyId = post_int('company_id', data_default_company_id($user));
            $supplierId = post_int('supplier_id', 0);
            data_require_company_scope($companyId, 'scorecards.php');
            data_require_supplier_scope($supplierId, 'scorecards.php');
            $metrics=['on_time_delivery','quality','responsiveness','cost_competitiveness'];
            $values=[]; foreach($metrics as $metric){$values[$metric]=min(100,max(0,(float)post_string($metric,'0')));}
            $overall=round(array_sum($values)/4,1);
            $period = post_string('period');
            if ($period === '') data_action_result('error', 'Scorecard period is required.', 'scorecards.php');
            $record=array_merge([
                'id'=>$id?:null,'supplier_id'=>$supplierId,
                'company_id'=>$companyId,
                'period'=>$period,'overall'=>$overall,
                'status'=>$before['status']??'draft'
            ],$values);
            $saved=data_upsert('supplier_scorecards',$record);
            data_add_audit('Scorecards',$before?'updated':'created','supplier_scorecard',$saved['id'],$before,$saved,$saved['company_id']);
            data_action_result('success','Supplier scorecard saved.','scorecards.php');

        case 'workflow_transition':
            $collection = post_string('collection');
            $id = post_int('id');
            $transition = post_string('transition');
            $map = [
                'suppliers'=>['permission'=>'suppliers','field'=>'review_status','entity'=>'supplier','module'=>'Suppliers'],
                'items'=>['permission'=>'items','field'=>'review_status','entity'=>'item','module'=>'Items'],
                'purchase_orders'=>['permission'=>'purchase_orders','field'=>'review_status','entity'=>'purchase_order','module'=>'Purchase Orders'],
                'savings_opportunities'=>['permission'=>'savings','field'=>'review_status','entity'=>'savings_opportunity','module'=>'Savings'],
                'supplier_scorecards'=>['permission'=>'scorecards','field'=>'status','entity'=>'supplier_scorecard','module'=>'Scorecards'],
                'discovery_assignments'=>['permission'=>'discovery','field'=>'status','entity'=>'discovery_assignment','module'=>'Discovery'],
                'workflow_approvals'=>['permission'=>'approvals','field'=>'status','entity'=>'workflow_approval','module'=>'Approvals'],
            ];
            if (!isset($map[$collection])) data_action_result('error', 'Unsupported workflow record.');
            if ($collection === 'workflow_approvals' && $transition === 'validated') {
                data_action_result('error', 'Approval requests may be approved or returned for changes; validation applies to source records.', 'approvals.php');
            }
            $actionPermission = in_array($transition, ['approved','validated'], true)
                ? 'approve'
                : (in_array($transition, ['changes_requested','in_review'], true) ? 'review' : 'submit');
            data_require_action_permission($map[$collection]['permission'] . '.' . $actionPermission);
            $record = data_find($collection, $id);
            data_require_record_scope($record);
            $field = $map[$collection]['field'];
            $current = (string)($record[$field] ?? 'draft');
            $allowed = [
                'draft'=>['submitted'],
                'not_started'=>['submitted'],
                'in_progress'=>['submitted'],
                'changes_requested'=>['submitted'],
                'submitted'=>['changes_requested','validated'],
                'pending'=>['changes_requested','approved'],
                'in_review'=>['changes_requested','validated','approved'],
                'validated'=>['changes_requested','approved'],
            ];
            if (!in_array($transition, $allowed[$current] ?? [], true)) {
                data_action_result('error', 'That workflow transition is not allowed from ' . status_label($current) . '.');
            }
            $before = $record;
            $record[$field] = $transition;
            if ($collection === 'discovery_assignments' && $transition === 'submitted') $record['completion'] = 100;
            data_upsert($collection, $record);

            $companyId = $record['company_id'] ?? null;
            if (!$companyId && is_array($record['company_ids'] ?? null)) {
                $selectedCompany = current_company_id();
                $recordCompanyIds = array_map('intval', $record['company_ids']);
                $companyId = $selectedCompany !== 'enterprise' && in_array((int)$selectedCompany, $recordCompanyIds, true)
                    ? (int)$selectedCompany
                    : (int)($recordCompanyIds[0] ?? data_default_company_id($user));
            }
            $companyId = (int)($companyId ?: data_default_company_id($user));
            data_require_company_scope($companyId);

            if ($transition === 'submitted' && $collection !== 'workflow_approvals') {
                $existingApproval=null;foreach(data_collection('workflow_approvals') as $candidateApproval){if(($candidateApproval['entity_type']??'')===$map[$collection]['entity']&&(int)($candidateApproval['entity_id']??0)===$id&&in_array((string)($candidateApproval['status']??''),['pending','in_review'],true)){$existingApproval=$candidateApproval;break;}}
                if(!$existingApproval){
                    $reviewerId=(int)$user['id'];foreach(data_admin_visible_users() as $candidate){if(($candidate['status']??'')==='active'&&can('approvals.review',$candidate)){$reviewerId=(int)$candidate['id'];break;}}
                    data_upsert('workflow_approvals',['id'=>null,'company_id'=>$companyId,'module'=>$map[$collection]['permission'],'entity_type'=>$map[$collection]['entity'],'entity_id'=>$id,'title'=>record_name($record),'submitted_by'=>$user['id'],'assigned_to'=>$reviewerId,'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+3 days')),'notes'=>post_string('note')]);
                }
            }

            if ($collection === 'workflow_approvals' && in_array($transition, ['approved','changes_requested'], true)) {
                $sourceMap = [
                    'supplier'=>['collection'=>'suppliers','field'=>'review_status'],
                    'item'=>['collection'=>'items','field'=>'review_status'],
                    'purchase_order'=>['collection'=>'purchase_orders','field'=>'review_status'],
                    'savings_opportunity'=>['collection'=>'savings_opportunities','field'=>'review_status'],
                    'supplier_scorecard'=>['collection'=>'supplier_scorecards','field'=>'status'],
                    'discovery_assignment'=>['collection'=>'discovery_assignments','field'=>'status'],
                ];
                $sourceInfo = $sourceMap[$record['entity_type'] ?? ''] ?? null;
                if ($sourceInfo) {
                    $source = data_find($sourceInfo['collection'], (int)($record['entity_id'] ?? 0));
                    if ($source && data_record_visible($source)) {
                        $source[$sourceInfo['field']] = $transition;
                        data_upsert($sourceInfo['collection'], $source);
                    }
                }
            }

            data_add_audit($map[$collection]['module'], $transition, $map[$collection]['entity'], $id, $before, $record, $companyId);
            data_action_result('success', 'Workflow status changed to ' . status_label($transition) . '.');

        case 'upload_import':
            data_require_action_permission('imports.create');
            $type = post_string('import_type', 'other');
            $allowedTypes = ['supplier_master','item_master','purchase_orders','inventory_snapshot'];
            if (!in_array($type, $allowedTypes, true)) data_action_result('error', 'Select a supported import type.', 'imports.php');
            $company = post_string('company_id', (string)current_company_id());
            if ($company === 'enterprise') {
                if (!can_use_enterprise_view($user) || current_company_id() !== 'enterprise') {
                    data_action_result('error', 'Enterprise imports require Enterprise View.', 'imports.php');
                }
            } else {
                data_require_company_scope((int)$company, 'imports.php');
            }
            $job = import_receive_upload($_FILES['import_file'] ?? [], $type, $company);
            data_action_result('success', 'Import uploaded and validated: '.$job['file_name'].'.', 'imports.php');

        case 'save_import_mapping':
            data_require_action_permission('imports.edit');
            $id=post_int('id'); $job=data_find('import_jobs',$id);
            data_require_import_scope($job, 'imports.php');
            $source=data_input_array('source_columns'); $targets=data_input_array('target_columns');
            $mapping=[]; foreach($source as $index=>$column){if(!empty($targets[$index]))$mapping[$column]=$targets[$index];}
            $validated=import_validate_job($id,$mapping);
            data_action_result('success','Column mapping saved. '.number_format($validated['rows_valid']).' row(s) are valid and '.number_format($validated['rows_error']).' row(s) require review.','imports.php');

        case 'import_error_status':
            data_require_action_permission('imports.review');
            $id=post_int('id'); $status=post_string('status'); $error=data_find('import_validation_errors',$id);
            if(!$error||!in_array($status,['open','review','accepted','resolved'],true))data_action_result('error','Import error action is invalid.','imports.php');
            data_require_import_scope(data_find('import_jobs', (int)$error['import_job_id']), 'imports.php');
            if(in_array((string)($error['error_code']??''),import_security_error_codes(),true)&&in_array($status,['accepted','resolved'],true)){
                data_action_result('error','Security and company-scope errors cannot be accepted. Correct the source data or mapping and validate again.','imports.php');
            }
            $before=$error; $error['status']=$status; data_upsert('import_validation_errors',$error);
            data_add_audit('Imports','validation_error_'.$status,'import_validation_error',$id,$before,$error,null);
            data_action_result('success','Import validation error updated.','imports.php');

        case 'commit_import':
            data_require_action_permission('imports.approve');
            $id=post_int('id');
            data_require_import_scope(data_find('import_jobs', $id), 'imports.php');
            $receipt=import_commit_job($id);
            data_action_result('success','Import committed. Receipt '.$receipt['receipt_number'].' created.','imports.php');

        case 'add_comment':
            data_require_action_permission('approvals.review');
            $body=post_string('body'); if($body==='')data_action_result('error','Comment text is required.','approvals.php');
            $entityType = post_string('entity_type', 'workflow_approval');
            $entityId = post_int('entity_id');
            $companyId = post_int('company_id', data_default_company_id($user));
            if ($entityType === 'workflow_approval') {
                $approval = data_find('workflow_approvals', $entityId);
                data_require_record_scope($approval, 'approvals.php');
                $companyId = (int)($approval['company_id'] ?? data_default_company_id($user));
            }
            data_require_company_scope($companyId, 'approvals.php');
            $record=data_upsert('comments',[
                'id'=>null,'company_id'=>$companyId,
                'entity_type'=>$entityType,'entity_id'=>$entityId,
                'user_id'=>$user['id'],'body'=>$body,'created_at'=>date('Y-m-d H:i:s')
            ]);
            data_add_audit('Approvals','comment_added',$record['entity_type'],$record['entity_id'],null,$record,$record['company_id']);
            data_action_result('success','Comment added.','approvals.php');

        default:
            data_action_result('error', 'Unknown application action.');
    }
}
