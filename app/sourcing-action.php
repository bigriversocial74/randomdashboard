<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/sourcing.php';
require_app_user();
if (request_method() !== 'POST') redirect_to(app_url('sourcing.php'));
verify_csrf();

try {
    $action = post_string('action');
    if ($action === 'save') {
        require_permission('suppliers.edit');
        $id = post_int('id');
        $before = $id ? sourcing_find_record($id) : null;
        if ($id && !$before) throw new RuntimeException('The sourcing decision is outside the active scope.');
        $supplierIds = $_POST['supplier_ids'] ?? [];
        if (!is_array($supplierIds)) $supplierIds = [];
        $weights = [];
        foreach (sourcing_default_weights() as $key => $default) $weights[$key] = post_string('weight_' . $key, (string)$default);
        $comparison = sourcing_build_comparison($supplierIds, $weights);
        $selected = array_map(static fn(array $row): int => (int)$row['supplier']['id'], $comparison['rows']);
        $preferred = post_int('preferred_supplier_id', (int)$comparison['recommended_supplier_id']);
        $alternate = post_int('alternate_supplier_id', (int)$comparison['alternate_supplier_id']);
        if (!in_array($preferred, $selected, true)) $preferred = (int)$comparison['recommended_supplier_id'];
        if (!in_array($alternate, $selected, true) || $alternate === $preferred) $alternate = (int)$comparison['alternate_supplier_id'];
        $title = trim(post_string('title'));
        $rationale = trim(post_string('rationale'));
        if ($title === '' || $rationale === '') throw new RuntimeException('Decision title and rationale are required.');
        $record = [
            'id'=>$id ?: null,
            'comparison_number'=>$before['comparison_number'] ?? sourcing_next_number(),
            'company_id'=>current_company_id()==='enterprise' ? null : (int)current_company_id(),
            'title'=>mb_substr($title,0,190),
            'category'=>mb_substr(post_string('category','Strategic sourcing'),0,190),
            'selected_supplier_ids'=>$selected,
            'weights'=>$comparison['weights'],
            'preferred_supplier_id'=>$preferred,
            'alternate_supplier_id'=>$alternate,
            'decision_status'=>$before['decision_status'] ?? 'draft',
            'rationale'=>mb_substr($rationale,0,5000),
            'owner_id'=>(int)current_user()['id'],
            'approval_id'=>$before['approval_id'] ?? null,
            'created_at'=>$before['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s'),
        ];
        $saved = sourcing_save_record($record);
        data_add_audit('Strategic Sourcing',$before?'decision_updated':'decision_created','sourcing_decision',$saved['id'],$before,$saved,$saved['company_id']);
        flash('success','Sourcing decision saved: '.$saved['comparison_number'].'.');
        redirect_to(app_url('sourcing.php?id='.(int)$saved['id']));
    }

    if ($action === 'submit') {
        require_permission('approvals.submit');
        $id = post_int('id');
        $record = sourcing_find_record($id);
        if (!$record) throw new RuntimeException('The sourcing decision is outside the active scope.');
        $currentStatus = sourcing_effective_status($record);
        if (in_array($currentStatus,['pending','in_review','approved'],true)) throw new RuntimeException('This sourcing decision is already in an active or completed approval workflow.');
        $reviewerId = (int)current_user()['id'];
        foreach (data_admin_visible_users() as $candidate) {
            if (($candidate['status'] ?? '') === 'active' && can('approvals.review',$candidate) && (int)$candidate['id'] !== (int)current_user()['id']) { $reviewerId=(int)$candidate['id']; break; }
        }
        $companyId = $record['company_id'] ?? data_default_company_id(current_user());
        $approval = data_upsert('workflow_approvals',[
            'id'=>null,'company_id'=>$companyId,'module'=>'suppliers','entity_type'=>'sourcing_decision','entity_id'=>$id,
            'title'=>$record['title'].' · '.$record['comparison_number'],'submitted_by'=>(int)current_user()['id'],'assigned_to'=>$reviewerId,
            'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s'),'due_date'=>date('Y-m-d',strtotime('+3 days')),
            'notes'=>'Review the supplier comparison matrix, evidence confidence, preferred supplier, alternate source, and decision rationale.',
        ]);
        $before = $record;
        $record['approval_id'] = (int)$approval['id'];
        $record['decision_status'] = 'submitted';
        $record = sourcing_save_record($record);
        data_upsert('notifications',[
            'id'=>null,'company_id'=>$companyId,'user_id'=>$reviewerId,'title'=>'Sourcing decision awaiting review',
            'message'=>$record['comparison_number'].' recommends '.data_supplier_name($record['preferred_supplier_id']).'.','severity'=>'info','read'=>false,'created_at'=>date('Y-m-d H:i:s'),
        ]);
        data_add_audit('Strategic Sourcing','submitted','sourcing_decision',$id,$before,$record,$companyId);
        flash('success','Sourcing decision routed to Reviews & Approvals.');
        redirect_to(app_url('sourcing.php?id='.$id));
    }

    throw new RuntimeException('Unknown sourcing action.');
} catch (Throwable $exception) {
    flash('error','The sourcing action could not be completed: '.$exception->getMessage());
    redirect_to(app_url('sourcing.php'));
}
