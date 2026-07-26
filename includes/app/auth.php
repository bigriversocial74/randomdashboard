<?php
declare(strict_types=1);

function demo_mode_active(): bool
{
    return !empty($_SESSION['gruber_demo_mode']);
}

function current_user(): ?array
{
    if (demo_mode_active()) {
        $id = (int) ($_SESSION['gruber_demo_user_id'] ?? 0);
        return demo_find('users', $id);
    }

    $id = (int) ($_SESSION['gruber_production_user_id'] ?? 0);
    if ($id <= 0 || !database_available()) return null;
    try {
        $statement = database_connection()->prepare(
            'SELECT u.*, up.first_name,up.last_name,up.phone,up.department,up.employment_status,
                    up.password_reset_required,up.administrative_notes,c.name AS primary_company_name
             FROM users u
             LEFT JOIN user_profiles up ON up.user_id=u.id
             LEFT JOIN companies c ON c.id=u.primary_company_id
             WHERE u.id=:id LIMIT 1'
        );
        $statement->execute(['id'=>$id]);
        $user=$statement->fetch();
        if(!$user) {
            unset($_SESSION['gruber_production_user_id'], $_SESSION['gruber_production_company_id']);
            return null;
        }
        $accountStatus=(string)($user['status']??'inactive');
        $employmentStatus=(string)($user['employment_status']??'active');
        if($accountStatus!=='active' || $employmentStatus!=='active') {
            unset($_SESSION['gruber_production_user_id'], $_SESSION['gruber_production_company_id']);
            return null;
        }
        $roles=database_connection()->prepare('SELECT DISTINCT r.code FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=:id');
        $roles->execute(['id'=>$id]);
        $user['role_codes']=array_column($roles->fetchAll(),'code')?:['read_only'];
        $members=database_connection()->prepare('SELECT company_id FROM company_memberships WHERE user_id=:id AND membership_status="active" ORDER BY is_primary DESC,company_id');
        $members->execute(['id'=>$id]);
        $user['company_ids']=array_map('intval',$members->fetchAll(PDO::FETCH_COLUMN));
        if(!$user['company_ids'] && $user['primary_company_id']) $user['company_ids']=[(int)$user['primary_company_id']];
        $user['id']=(int)$user['id'];
        $user['primary_company_id']=$user['primary_company_id']?(int)$user['primary_company_id']:null;
        $user['first_name']=$user['first_name']?:explode(' ',$user['name'],2)[0];
        $user['last_name']=$user['last_name']?:((explode(' ',$user['name'],2)[1]??''));
        $user['require_password_reset']=!empty($user['password_reset_required']);
        return $user;
    } catch(Throwable $e){$_SESSION['gruber_db_error']=$e->getMessage();return null;}
}

function current_role_codes(?array $user=null):array
{
    $user??=current_user();if(!$user)return [];
    if(!empty($user['role_codes'])&&is_array($user['role_codes']))return $user['role_codes'];
    if(!empty($user['role_code']))return[(string)$user['role_code']];
    return ['read_only'];
}

function current_permissions(?array $user=null):array
{
    $user ??= current_user();
    if (!$user) return [];

    if (demo_mode_active()) {
        $permissions=[];
        $defaults=role_permission_defaults();
        foreach(current_role_codes($user) as $roleCode){
            $permissions=array_merge($permissions,$defaults[$roleCode]??[]);
            $role=demo_find_by('roles','code',$roleCode);
            if($role&&!empty($role['permissions'])&&is_array($role['permissions']))$permissions=array_merge($permissions,$role['permissions']);
        }
        return array_values(array_unique(array_filter($permissions)));
    }

    if (database_available()) {
        try {
            $stmt=database_connection()->prepare('SELECT DISTINCT p.permission_key FROM user_roles ur JOIN roles r ON r.id=ur.role_id JOIN role_permissions rp ON rp.role_id=ur.role_id JOIN permissions p ON p.id=rp.permission_id WHERE ur.user_id=:id');
            $stmt->execute(['id'=>$user['id']]);
            return array_values(array_unique(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN))));
        } catch(Throwable $e) {
            $_SESSION['gruber_db_error']=$e->getMessage();
            return [];
        }
    }

    return [];
}


function can(string $permission,?array $user=null):bool{return in_array($permission,current_permissions($user),true);}
function require_permission(string $permission):void{if(!can($permission)){http_response_code(403);render_access_denied($permission);exit;}}

function current_company_id():int|string
{
    if(demo_mode_active())return $_SESSION['gruber_demo_company_id']??'enterprise';
    $selected=$_SESSION['gruber_production_company_id']??null;
    $user=current_user();
    if($selected==='enterprise'&&can_use_enterprise_view($user))return 'enterprise';
    if($selected && in_array((int)$selected,permitted_company_ids($user),true))return (int)$selected;
    return (int)($user['primary_company_id']??0);
}

function permitted_company_ids(?array $user=null):array
{
    $user??=current_user();if(!$user)return [];
    if(demo_mode_active()){
        $roles=current_role_codes($user);
        if(array_intersect($roles,['system_administrator','executive']))return array_column(demo_collection('companies'),'id');
        return array_map('intval',$user['company_ids']??[]);
    }
    if(can('companies.administer',$user)){
        try{return array_map('intval',database_connection()->query('SELECT id FROM companies WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN));}catch(Throwable){}
    }
    return array_values(array_unique(array_map('intval',$user['company_ids']??[$user['primary_company_id']??0])));
}

function can_use_enterprise_view(?array $user=null):bool{return(bool)array_intersect(current_role_codes($user),['system_administrator','executive']);}

function require_app_user(): array
{
    $user = current_user();
    if ($user) {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (!demo_mode_active()
            && !empty($user['require_password_reset'])
            && !in_array($script, ['change-password.php', 'logout.php'], true)) {
            redirect_to(app_url('change-password.php'));
        }
        return $user;
    }
    if (demo_mode_active()) redirect_to(root_url('demo.php'));
    if (!database_available()) {
        render_environment_gate();
        exit;
    }
    redirect_to(app_url('login.php'));
}

function runtime_security_settings(): array
{
    $configured = app_config()['security'] ?? [];
    $settings = [
        'password_min_length' => 12,
        'password_require_mixed_case' => true,
        'password_require_number' => true,
        'password_require_symbol' => true,
        'login_attempt_limit' => (int)($configured['max_login_attempts'] ?? 5),
        'lockout_minutes' => (int)($configured['lockout_minutes'] ?? 15),
        'session_lifetime' => (int)($configured['session_lifetime_minutes'] ?? 120),
        'session_absolute_lifetime' => (int)($configured['session_absolute_lifetime_minutes'] ?? 720),
        'password_reset_lifetime' => (int)($configured['password_reset_lifetime_minutes'] ?? 60),
    ];
    if (function_exists('data_settings') && database_available()) {
        try {
            $settings = array_replace($settings, data_settings());
        } catch (Throwable) {
            // Fall back to config.php while the settings table is unavailable.
        }
    }
    return $settings;
}

function password_meets_runtime_policy(string $password, ?array $settings = null): bool
{
    $settings ??= runtime_security_settings();
    if (strlen($password) < max(8, (int)($settings['password_min_length'] ?? 12))) return false;
    if (!empty($settings['password_require_mixed_case']) && (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password))) return false;
    if (!empty($settings['password_require_number']) && !preg_match('/\d/', $password)) return false;
    if (!empty($settings['password_require_symbol']) && !preg_match('/[^A-Za-z0-9]/', $password)) return false;
    return true;
}

function production_login_error(): string
{
    return (string) ($_SESSION['gruber_login_error'] ?? 'Sign-in failed. Check the email address and password.');
}

function record_security_event(
    PDO $pdo,
    string $eventType,
    string $severity,
    ?string $email,
    ?int $userId,
    array $details = []
): void {
    try {
        $event = $pdo->prepare(
            'INSERT INTO security_events(user_id,event_type,severity,email,ip_address,user_agent,details)
             VALUES(?,?,?,?,?,?,?)'
        );
        $event->execute([
            $userId,
            $eventType,
            $severity,
            $email,
            current_ip(),
            current_user_agent(),
            json_encode($details, JSON_UNESCAPED_SLASHES),
        ]);
    } catch (Throwable $loggingError) {
        $_SESSION['gruber_db_error'] = $loggingError->getMessage();
    }
}

function production_failed_login_count(PDO $pdo, string $email, string $ipAddress, int $windowMinutes): ?int
{
    $minutes = min(1440, max(1, $windowMinutes));
    try {
        $statement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM security_events
             WHERE event_type='failed_sign_in'
               AND occurred_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)
               AND (LOWER(email)=:email OR ip_address=:ip_address)"
        );
        $statement->execute(['email' => $email, 'ip_address' => $ipAddress]);
        return (int) $statement->fetchColumn();
    } catch (Throwable $exception) {
        $_SESSION['gruber_db_error'] = $exception->getMessage();
        return null;
    }
}

function production_login(string $email, string $password): bool
{
    unset($_SESSION['gruber_login_error']);
    if (!database_available()) {
        $_SESSION['gruber_login_error'] = 'The production database is unavailable. Check config.php and the database credentials.';
        return false;
    }

    $email = strtolower(trim($email));
    if ($email === '' || $password === '') {
        $_SESSION['gruber_login_error'] = 'Enter both the email address and password.';
        return false;
    }

    $security = runtime_security_settings();
    $limit = max(1, (int) ($security['login_attempt_limit'] ?? 5));
    $windowMinutes = max(1, (int) ($security['lockout_minutes'] ?? 15));
    $ipAddress = current_ip();
    $attemptKey = 'gruber_login_attempts_v3_' . hash('sha256', $email . '|' . $ipAddress);
    $sessionAttempts = $_SESSION[$attemptKey] ?? ['count' => 0, 'first' => time()];
    $windowSeconds = $windowMinutes * 60;
    if (time() - (int) ($sessionAttempts['first'] ?? 0) > $windowSeconds) {
        $sessionAttempts = ['count' => 0, 'first' => time()];
    }

    try {
        $pdo = database_connection();
        if (!$pdo instanceof PDO) throw new RuntimeException('Database connection unavailable.');

        $databaseAttempts = production_failed_login_count($pdo, $email, $ipAddress, $windowMinutes);
        $effectiveAttempts = max((int) ($sessionAttempts['count'] ?? 0), (int) ($databaseAttempts ?? 0));
        if ($effectiveAttempts >= $limit) {
            $_SESSION['gruber_login_error'] = 'Too many failed attempts. Wait for the login window to expire before trying again.';
            record_security_event($pdo, 'sign_in_throttled', 'warning', $email, null, ['window_minutes' => $windowMinutes]);
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT u.*,up.employment_status,
                    COALESCE(up.password_reset_required,0) AS password_reset_required
             FROM users u
             LEFT JOIN user_profiles up ON up.user_id=u.id
             WHERE LOWER(u.email)=:email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        $valid = $user
            && (string) ($user['status'] ?? '') === 'active'
            && (string) ($user['employment_status'] ?? 'active') === 'active'
            && password_verify($password, (string) ($user['password_hash'] ?? ''));

        if (!$valid) {
            $sessionAttempts['count'] = (int) ($sessionAttempts['count'] ?? 0) + 1;
            $_SESSION[$attemptKey] = $sessionAttempts;
            $_SESSION['gruber_login_error'] = 'Sign-in failed. Check the email address and password.';
            record_security_event($pdo, 'failed_sign_in', 'warning', $email, null, ['attempt' => $sessionAttempts['count']]);
            return false;
        }

        unset($_SESSION[$attemptKey]);
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash=:password_hash WHERE id=:id')->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $user['id'],
            ]);
        }

        session_regenerate_id(true);
        unset(
            $_SESSION['gruber_csrf'],
            $_SESSION['gruber_demo_mode'],
            $_SESSION['gruber_demo_user_id'],
            $_SESSION['gruber_demo_company_id'],
            $_SESSION['gruber_demo_state']
        );
        $_SESSION['gruber_production_user_id'] = (int) $user['id'];
        $_SESSION['gruber_production_company_id'] = !empty($user['primary_company_id']) ? (int) $user['primary_company_id'] : 0;
        $_SESSION['gruber_production_session_started_at'] = time();
        $_SESSION['gruber_production_session_tracked'] = false;

        try {
            $sessionHash = hash('sha256', session_id());
            $expires = date('Y-m-d H:i:s', time() + ((int) ($security['session_lifetime'] ?? 120) * 60));
            $sessionStatement = $pdo->prepare(
                'INSERT INTO user_sessions(id,user_id,ip_address,user_agent,device_label,expires_at)
                 VALUES(?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE last_activity_at=NOW(),expires_at=VALUES(expires_at),revoked_at=NULL'
            );
            $sessionStatement->execute([
                $sessionHash,
                (int) $user['id'],
                $ipAddress,
                current_user_agent(),
                substr(current_user_agent(), 0, 150),
                $expires,
            ]);
            $_SESSION['gruber_production_session_tracked'] = true;
        } catch (Throwable $sessionError) {
            $_SESSION['gruber_db_error'] = $sessionError->getMessage();
        }

        try {
            $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=:id')->execute(['id' => (int) $user['id']]);
        } catch (Throwable $updateError) {
            $_SESSION['gruber_db_error'] = $updateError->getMessage();
        }

        record_security_event($pdo, 'successful_sign_in', 'info', $email, (int) $user['id'], [
            'password_reset_required' => !empty($user['password_reset_required']),
        ]);

        try {
            $audit = $pdo->prepare(
                'INSERT INTO audit_logs(user_id,company_id,action,entity_type,entity_id,after_data,ip_address,user_agent)
                 VALUES(?,?,?,?,?,?,?,?)'
            );
            $audit->execute([
                (int) $user['id'],
                !empty($user['primary_company_id']) ? (int) $user['primary_company_id'] : null,
                'production_login',
                'user',
                (int) $user['id'],
                json_encode(['environment' => 'production', 'module' => 'Security'], JSON_UNESCAPED_SLASHES),
                $ipAddress,
                current_user_agent(),
            ]);
        } catch (Throwable $auditError) {
            $_SESSION['gruber_db_error'] = $auditError->getMessage();
        }

        unset($_SESSION['gruber_pending_environment'], $_SESSION['gruber_login_error']);
        return true;
    } catch (Throwable $e) {
        $_SESSION['gruber_db_error'] = $e->getMessage();
        $_SESSION['gruber_login_error'] = 'The sign-in service encountered a database error. The credentials were not changed.';
        return false;
    }
}

function touch_production_session(): void
{
    if (demo_mode_active() || empty($_SESSION['gruber_production_user_id']) || !database_available()) return;

    try {
        $pdo = database_connection();
        if (!$pdo instanceof PDO) return;

        $userId = (int) $_SESSION['gruber_production_user_id'];
        $hash = hash('sha256', session_id());
        $security = runtime_security_settings();
        $idleMinutes = max(5, (int) ($security['session_lifetime'] ?? 120));
        $absoluteMinutes = max($idleMinutes, (int) ($security['session_absolute_lifetime'] ?? 720));

        $stmt = $pdo->prepare('SELECT user_id,started_at,revoked_at,expires_at FROM user_sessions WHERE id=? LIMIT 1');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        if (!$row && empty($_SESSION['gruber_production_session_tracked'])) {
            $expires = date('Y-m-d H:i:s', time() + ($idleMinutes * 60));
            $insert = $pdo->prepare(
                'INSERT INTO user_sessions(id,user_id,ip_address,user_agent,device_label,expires_at)
                 VALUES(?,?,?,?,?,?)'
            );
            $insert->execute([$hash, $userId, current_ip(), current_user_agent(), substr(current_user_agent(), 0, 150), $expires]);
            $_SESSION['gruber_production_session_tracked'] = true;
            $_SESSION['gruber_production_session_started_at'] = time();
            return;
        }

        if (!$row
            || (int) $row['user_id'] !== $userId
            || !empty($row['revoked_at'])
            || strtotime((string) $row['expires_at']) < time()
            || strtotime((string) $row['started_at']) + ($absoluteMinutes * 60) < time()) {
            app_logout();
            $_SESSION['gruber_login_error'] = 'Your session expired or was revoked. Sign in again.';
            return;
        }

        $expires = date('Y-m-d H:i:s', time() + ($idleMinutes * 60));
        $pdo->prepare('UPDATE user_sessions SET last_activity_at=NOW(),expires_at=? WHERE id=?')->execute([$expires, $hash]);
        $_SESSION['gruber_production_session_tracked'] = true;
    } catch (Throwable $exception) {
        $_SESSION['gruber_db_error'] = $exception->getMessage();
    }
}

function app_logout(): void
{
    if (!demo_mode_active() && !empty($_SESSION['gruber_production_user_id']) && database_available()) {
        try {
            database_connection()
                ->prepare('UPDATE user_sessions SET revoked_at=NOW(),revoked_by=user_id WHERE id=?')
                ->execute([hash('sha256', session_id())]);
        } catch (Throwable $exception) {
            $_SESSION['gruber_db_error'] = $exception->getMessage();
        }
    }

    unset(
        $_SESSION['gruber_demo_mode'],
        $_SESSION['gruber_demo_user_id'],
        $_SESSION['gruber_demo_company_id'],
        $_SESSION['gruber_demo_state'],
        $_SESSION['gruber_production_user_id'],
        $_SESSION['gruber_production_company_id'],
        $_SESSION['gruber_production_session_tracked'],
        $_SESSION['gruber_production_session_started_at'],
        $_SESSION['gruber_pending_environment'],
        $_SESSION['gruber_csrf']
    );
    session_regenerate_id(true);
}
