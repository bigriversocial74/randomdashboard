<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/supplier_portal.php';
require_app_user();
if(request_method()!=='POST')redirect_to(app_url('supplier-portal.php'));
verify_csrf();

function supplier_portal_internal_note(string $key='review_note'): string
{
    $value=trim(post_string($key));if($value==='')throw new RuntimeException('Governance evidence is required.');return mb_substr($value,0,5000);
}
function supplier_portal_internal_redirect(string $tab='overview',array $params=[]): never
{
    redirect_to(app_url('supplier-portal.php?'.http_build_query(array_replace(['tab'=>$tab],$params))));
}

try{
    $action=post_string('action');
    if($action==='create_invitation'){
        require_permission('supplier_portal.invite');
        $supplierId=post_int('supplier_id');$contactId=post_int('supplier_contact_id');$role=post_string('portal_role','account_administrator');
        if(!in_array($role,['account_administrator','sales','order_fulfillment','billing','quality','executive'],true))throw new RuntimeException('Select a valid supplier portal role.');
        $invitation=supplier_portal_create_invitation($supplierId,$contactId?:null,post_string('invited_name'),post_string('email'),$role,post_int('expiration_days',7));
        $url=root_url('supplier-portal/activate.php?token='.rawurlencode($invitation['activation_token']));
        data_add_audit('Supplier Portal','invitation_created','supplier_portal_invitation',(int)$invitation['id'],null,['supplier_id'=>$supplierId,'email'=>$invitation['email'],'role'=>$role],null);
        flash('success','Supplier invitation created. Controlled activation link: '.$url);
        supplier_portal_internal_redirect('accounts',['supplier_id'=>$supplierId]);
    }
    if($action==='save_grant'){
        require_permission('supplier_portal.administer');
        $account=supplier_portal_find_account(post_int('account_id'));if(!$account)throw new RuntimeException('The supplier portal account is outside the active scope.');
        $companyId=post_int('company_id');if(!data_company_within_scope($companyId))throw new RuntimeException('The company grant is outside the active scope.');
        $grant=null;foreach(supplier_portal_access_grants((int)$account['id']) as $candidate)if((int)$candidate['company_id']===$companyId){$grant=$candidate;break;}
        $saved=supplier_portal_save_grant(['id'=>$grant['id']??null,'account_id'=>$account['id'],'company_id'=>$companyId,'access_scope'=>'company','can_acknowledge_po'=>post_int('can_acknowledge_po')?1:0,'can_submit_asn'=>post_int('can_submit_asn')?1:0,'can_submit_invoice'=>post_int('can_submit_invoice')?1:0,'can_submit_documents'=>post_int('can_submit_documents')?1:0,'can_message'=>post_int('can_message')?1:0,'can_quality'=>post_int('can_quality')?1:0,'can_sourcing'=>post_int('can_sourcing')?1:0,'starts_at'=>post_string('starts_at',date('Y-m-d')),'expires_at'=>post_string('expires_at')?:null,'status'=>post_string('status','active'),'granted_by'=>(int)current_user()['id']]);
        supplier_portal_add_event((int)$account['supplier_id'],$companyId,'access_grant',(int)$saved['id'],'access_grant_updated',$grant['status']??null,$saved['status'],'medium',supplier_portal_internal_note('evidence_note'),'internal',(int)current_user()['id'],(int)$account['id']);
        data_add_audit('Supplier Portal','access_grant_updated','supplier_portal_access_grant',(int)$saved['id'],$grant,$saved,$companyId);
        flash('success','Supplier portal access grant updated.');supplier_portal_internal_redirect('accounts',['supplier_id'=>$account['supplier_id']]);
    }
    if($action==='set_account_status'){
        require_permission('supplier_portal.administer');$account=supplier_portal_find_account(post_int('account_id'));if(!$account)throw new RuntimeException('The account is outside the active scope.');
        $status=post_string('status');if(!in_array($status,['active','suspended','revoked'],true))throw new RuntimeException('Select a valid account status.');$before=$account['status'];$account['status']=$status;$saved=supplier_portal_save_account($account);
        supplier_portal_add_event((int)$account['supplier_id'],null,'portal_account',(int)$account['id'],'account_status_changed',$before,$status,$status==='active'?'medium':'high',supplier_portal_internal_note('evidence_note'),'internal',(int)current_user()['id'],(int)$account['id']);
        data_add_audit('Supplier Portal','account_status_changed','supplier_portal_account',(int)$account['id'],$before,$saved,null);flash('success','Supplier portal account status updated.');supplier_portal_internal_redirect('accounts',['supplier_id'=>$account['supplier_id']]);
    }
    if($action==='review_record'){
        require_permission('supplier_portal.review');$saved=supplier_portal_review(post_string('record_type'),post_int('record_id'),post_string('decision'),supplier_portal_internal_note());
        flash('success','Supplier submission reviewed: '.status_label((string)$saved['status']).'.');supplier_portal_internal_redirect('review',['supplier_id'=>$saved['supplier_id']]);
    }
    if($action==='send_message'){
        require_permission('supplier_portal.review');$supplierId=post_int('supplier_id');$companyId=post_int('company_id')?:null;
        if($companyId!==null&&!data_company_within_scope($companyId))throw new RuntimeException('The company is outside the active scope.');
        supplier_portal_internal_message($supplierId,$companyId,post_string('entity_type','supplier'),post_int('entity_id',$supplierId),post_string('subject'),post_string('message_body'),post_string('required_response_date')?:null);
        flash('success','Supplier-visible message sent.');supplier_portal_internal_redirect('messages',['supplier_id'=>$supplierId]);
    }
    throw new RuntimeException('Unknown Supplier Portal action.');
}catch(Throwable $e){flash('error',$e->getMessage());supplier_portal_internal_redirect(post_string('return_tab','overview'),['supplier_id'=>post_int('supplier_id')?:null]);}
