<?php
declare(strict_types=1);

function supplier_portal_raw_accounts(): array
{
    if(data_is_demo())return supplier_portal_demo_collection('supplier_portal_accounts','supplier_portal_demo_accounts');
    if(!supplier_portal_tables_ready())return[];
    return production_database_connection()->query('SELECT * FROM supplier_portal_accounts ORDER BY id')->fetchAll();
}

function supplier_portal_raw_invitations(): array
{
    if(data_is_demo())return supplier_portal_demo_collection('supplier_portal_invitations','supplier_portal_demo_invitations');
    if(!supplier_portal_tables_ready())return[];
    return production_database_connection()->query('SELECT * FROM supplier_portal_invitations ORDER BY created_at DESC,id DESC')->fetchAll();
}

function supplier_portal_raw_account(int $id): ?array
{
    foreach(supplier_portal_raw_accounts() as $account)if((int)$account['id']===$id)return$account;
    return null;
}

function supplier_portal_current_account(): ?array
{
    $id=(int)($_SESSION['gruber_supplier_account_id']??0);
    if($id<=0)return null;
    $account=supplier_portal_raw_account($id);
    if(!$account||($account['status']??'')!=='active'){
        unset($_SESSION['gruber_supplier_account_id'],$_SESSION['gruber_supplier_session_started_at'],$_SESSION['gruber_supplier_last_activity']);
        return null;
    }
    $now=time();$idle=120*60;$absolute=720*60;
    $started=(int)($_SESSION['gruber_supplier_session_started_at']??$now);
    $last=(int)($_SESSION['gruber_supplier_last_activity']??$now);
    if($now-$last>$idle||$now-$started>$absolute){supplier_portal_logout();return null;}
    $_SESSION['gruber_supplier_last_activity']=$now;
    $account['id']=(int)$account['id'];$account['supplier_id']=(int)$account['supplier_id'];
    return $account;
}

function supplier_portal_require_account(): array
{
    $account=supplier_portal_current_account();
    if($account)return$account;
    redirect_to(root_url('supplier-portal/login.php'));
}

function supplier_portal_login_error(): string
{
    return (string)($_SESSION['gruber_supplier_login_error']??'Sign-in failed. Check the email address and password.');
}

function supplier_portal_login(string $email,string $password): bool
{
    unset($_SESSION['gruber_supplier_login_error']);
    if(!supplier_portal_tables_ready()){
        $_SESSION['gruber_supplier_login_error']='The Supplier Portal is not active until the Section 22 migration is imported.';
        return false;
    }
    $email=strtolower(trim($email));
    if($email===''||$password===''){
        $_SESSION['gruber_supplier_login_error']='Enter both the email address and password.';
        return false;
    }
    $attemptKey='gruber_supplier_login_'.hash('sha256',$email.'|'.current_ip());
    $attempts=$_SESSION[$attemptKey]??['count'=>0,'first'=>time()];
    if(time()-(int)$attempts['first']>900)$attempts=['count'=>0,'first'=>time()];
    if((int)$attempts['count']>=5){$_SESSION['gruber_supplier_login_error']='Too many failed attempts. Try again after the login window expires.';return false;}
    $account=null;foreach(supplier_portal_raw_accounts() as $candidate)if(strtolower((string)$candidate['email'])===$email){$account=$candidate;break;}
    $unlocked=$account&&(empty($account['locked_until'])||strtotime((string)$account['locked_until'])<=time());
    $valid=$account&&($account['status']??'')==='active'&&!empty($account['email_verified_at'])&&$unlocked&&password_verify($password,(string)($account['password_hash']??''));
    if(!$valid){$attempts['count']=(int)$attempts['count']+1;$_SESSION[$attemptKey]=$attempts;$_SESSION['gruber_supplier_login_error']='Sign-in failed. Check the email address and password.';return false;}
    unset($_SESSION[$attemptKey]);session_regenerate_id(true);
    unset($_SESSION['gruber_csrf'],$_SESSION['gruber_production_user_id'],$_SESSION['gruber_production_company_id']);
    $_SESSION['gruber_supplier_account_id']=(int)$account['id'];
    $_SESSION['gruber_supplier_session_started_at']=time();$_SESSION['gruber_supplier_last_activity']=time();
    $account['last_login_at']=date('Y-m-d H:i:s');supplier_portal_save_account($account);
    supplier_portal_add_event((int)$account['supplier_id'],null,'portal_account',(int)$account['id'],'supplier_login',null,'active','low','Supplier portal account authenticated.','supplier',null,(int)$account['id']);
    return true;
}

function supplier_portal_demo_login(): bool
{
    $_SESSION['gruber_demo_mode']=true;
    if(!isset($_SESSION['gruber_demo_state'])||!is_array($_SESSION['gruber_demo_state']))$_SESSION['gruber_demo_state']=demo_default_state();
    session_regenerate_id(true);
    $_SESSION['gruber_supplier_account_id']=1;$_SESSION['gruber_supplier_session_started_at']=time();$_SESSION['gruber_supplier_last_activity']=time();
    return true;
}

function supplier_portal_logout(): void
{
    unset($_SESSION['gruber_supplier_account_id'],$_SESSION['gruber_supplier_session_started_at'],$_SESSION['gruber_supplier_last_activity'],$_SESSION['gruber_csrf']);
}

function supplier_portal_invitation_by_token(string $token): ?array
{
    $hash=hash('sha256',$token);
    foreach(supplier_portal_raw_invitations() as $invitation){
        if(hash_equals((string)$invitation['token_hash'],$hash)&&($invitation['status']??'')==='pending'&&strtotime((string)$invitation['expires_at'])>=time())return$invitation;
    }
    return null;
}

function supplier_portal_create_invitation(int $supplierId,?int $contactId,string $name,string $email,string $role,int $days=7): array
{
    supplier_portal_require_tables();
    if(!supplier_portal_supplier_visible($supplierId))throw new RuntimeException('The supplier is outside the active company scope.');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid supplier email address.');
    $token=bin2hex(random_bytes(24));
    $record=supplier_portal_save_invitation([
        'supplier_id'=>$supplierId,'supplier_contact_id'=>$contactId?:null,'invited_name'=>trim($name)?:$email,'email'=>strtolower($email),
        'portal_role'=>$role,'token_hash'=>hash('sha256',$token),'expires_at'=>date('Y-m-d H:i:s',strtotime('+'.max(1,min(30,$days)).' days')),
        'status'=>'pending','invited_by'=>(int)current_user()['id'],'accepted_account_id'=>null,'accepted_at'=>null,
    ]);
    supplier_portal_add_event($supplierId,null,'portal_invitation',(int)$record['id'],'invitation_created',null,'pending','medium','Supplier portal invitation created for '.$email.'.','internal',(int)current_user()['id'],null);
    $record['activation_token']=$token;
    return $record;
}

function supplier_portal_activate_invitation(string $token,string $password,string $confirmation): array
{
    $invitation=supplier_portal_invitation_by_token($token);
    if(!$invitation)throw new RuntimeException('The invitation is invalid, expired, or already used.');
    if($password!==$confirmation)throw new RuntimeException('The password confirmation does not match.');
    if(!password_meets_runtime_policy($password))throw new RuntimeException('Use at least 12 characters with upper and lowercase letters, a number, and a symbol.');
    foreach(supplier_portal_raw_accounts() as $existing)if(strtolower((string)$existing['email'])===strtolower((string)$invitation['email']))throw new RuntimeException('A supplier portal account already exists for this email address.');
    $account=supplier_portal_save_account([
        'supplier_id'=>(int)$invitation['supplier_id'],'supplier_contact_id'=>$invitation['supplier_contact_id']?:null,'name'=>$invitation['invited_name'],
        'email'=>strtolower((string)$invitation['email']),'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'portal_role'=>$invitation['portal_role'],
        'status'=>'active','email_verified_at'=>date('Y-m-d H:i:s'),'mfa_required'=>0,'last_login_at'=>null,'locked_until'=>null,'created_by'=>$invitation['invited_by'],
    ]);
    $companyIds=[];
    if(data_is_demo()){$supplier=data_find('suppliers',(int)$invitation['supplier_id']);$companyIds=array_map('intval',$supplier['company_ids']??[]);}else{
        $stmt=production_database_connection()->prepare('SELECT company_id FROM supplier_companies WHERE supplier_id=? AND account_status="active"');$stmt->execute([(int)$invitation['supplier_id']]);$companyIds=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    foreach($companyIds as $companyId)supplier_portal_save_grant(['account_id'=>$account['id'],'company_id'=>$companyId,'access_scope'=>'company','can_acknowledge_po'=>1,'can_submit_asn'=>1,'can_submit_invoice'=>1,'can_submit_documents'=>1,'can_message'=>1,'can_quality'=>1,'can_sourcing'=>1,'starts_at'=>date('Y-m-d'),'expires_at'=>null,'status'=>'active','granted_by'=>$invitation['invited_by']]);
    $invitation['status']='accepted';$invitation['accepted_account_id']=$account['id'];$invitation['accepted_at']=date('Y-m-d H:i:s');supplier_portal_save_invitation($invitation);
    supplier_portal_add_event((int)$account['supplier_id'],null,'portal_account',(int)$account['id'],'account_activated',null,'active','medium','Supplier portal invitation accepted and tenant grants activated.','supplier',null,(int)$account['id']);
    return $account;
}

function supplier_portal_can(array $account,string $capability): bool
{
    if(($account['portal_role']??'')==='account_administrator')return supplier_portal_account_grant($account,$capability);
    $roleMap=['sales'=>['acknowledge_po','message','sourcing'],'order_fulfillment'=>['acknowledge_po','submit_asn','message'],'billing'=>['submit_invoice','documents','message'],'quality'=>['quality','documents','message'],'executive'=>['acknowledge_po','message','sourcing','quality']];
    return in_array($capability,$roleMap[$account['portal_role']]??[],true)&&supplier_portal_account_grant($account,$capability);
}

function supplier_portal_require_capability(array $account,string $capability): void
{
    if(!supplier_portal_can($account,$capability)){http_response_code(403);throw new RuntimeException('This supplier account does not have permission for that action.');}
}
