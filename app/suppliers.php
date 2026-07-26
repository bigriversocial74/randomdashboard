<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_permission('suppliers.view');

$records=data_visible_collection('suppliers');
$search=strtolower(query_string('q'));
$status=query_string('status');
$risk=query_string('risk');
$records=array_values(array_filter($records,static function(array $s)use($search,$status,$risk):bool{
    if($search!==''&&!str_contains(strtolower($s['name'].' '.$s['supplier_number'].' '.$s['category']),$search))return false;
    if($status!==''&&$s['status']!==$status)return false;
    if($risk!==''&&$s['risk']!==$risk)return false;
    return true;
}));
$records=sort_records($records,'annual_spend','desc');
$pagination=paginate_records($records,query_int('page',1),8);
$contracts=data_visible_collection('contracts');
$visibleSupplierIds=array_map('intval',array_column(data_visible_collection('suppliers'),'id'));
$supplierContacts=array_values(array_filter(data_collection('supplier_contacts'),fn(array $contact):bool=>in_array((int)$contact['supplier_id'],$visibleSupplierIds,true)));
$actions=can('suppliers.create')?'<button class="button primary" data-modal-open="supplierModal" type="button">Create Supplier</button>':'';
render_app_start('Supplier Master','suppliers','Supplier governance','Normalize suppliers, maintain company relationships and contacts, review contracts, and manage approval status.',$actions);
?>
<section class="metric-grid metric-grid-4">
<article class="metric-card"><span>Visible suppliers</span><strong><?= count(data_visible_collection('suppliers')) ?></strong><small><?= h(current_scope_label()) ?></small></article>
<article class="metric-card"><span>Preferred</span><strong><?= count(array_filter(data_visible_collection('suppliers'),fn($s)=>$s['status']==='preferred')) ?></strong><small>Strategic source relationships</small></article>
<article class="metric-card"><span>High risk</span><strong><?= count(array_filter(data_visible_collection('suppliers'),fn($s)=>in_array($s['risk'],['high','critical'],true))) ?></strong><small>Needs active mitigation</small></article>
<article class="metric-card"><span>Annual spend</span><strong><?= compact_money(array_sum(array_column(data_visible_collection('suppliers'),'annual_spend'))) ?></strong><small>Fictional supplier spend</small></article>
</section>

<section class="panel">
<header class="panel-head"><div><span class="eyebrow">Master-data controls</span><h2>Supplier records</h2></div><span class="panel-meta"><?= $pagination['total'] ?> matching records</span></header>
<form class="filter-bar" method="get"><label><span>Search</span><input name="q" value="<?= h(query_string('q')) ?>" placeholder="Supplier, number, or category"></label><label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach(['preferred','approved','candidate','conditional','blocked'] as $value): ?><option value="<?= h($value) ?>" <?= $status===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label><label><span>Risk</span><select name="risk"><option value="">All risk levels</option><?php foreach(['low','medium','high','critical'] as $value): ?><option value="<?= h($value) ?>" <?= $risk===$value?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label><button class="button secondary" type="submit">Apply filters</button><a class="button ghost" href="<?= h(app_url('suppliers.php')) ?>">Reset</a></form>
<div class="table-wrap"><table class="data-table" data-table>
<caption class="sr-only">Supplier master records for <?= h(current_scope_label()) ?></caption>
<thead><tr><th scope="col" data-sort>Supplier</th><th scope="col" data-sort>Category</th><th scope="col">Company scope</th><th scope="col" data-sort>Owner</th><th scope="col" data-sort>Risk</th><th scope="col" data-sort>Review</th><th scope="col" class="numeric" data-sort>Annual spend</th><th scope="col">Actions</th></tr></thead>
<tbody>
<?php foreach($pagination['items'] as $supplier): ?>
<tr><td><strong><?= h($supplier['name']) ?></strong><small><?= h($supplier['supplier_number']) ?> · <?= h($supplier['payment_terms']) ?> <?= sample_badge() ?></small></td><td><?= h($supplier['category']) ?></td><td><div class="company-chip-row"><?php foreach($supplier['company_ids'] as $cid): ?><span><?= h(data_find('companies',$cid)['code']??$cid) ?></span><?php endforeach; ?></div></td><td><?= h(data_user_name($supplier['owner_id'])) ?></td><td><?= badge($supplier['risk']) ?><small><?= badge($supplier['status']) ?></small></td><td><?= badge($supplier['review_status']) ?></td><td class="numeric"><strong><?= money($supplier['annual_spend']) ?></strong></td><td><?= workflow_actions('suppliers',(int)$supplier['id'],$supplier['review_status'],'suppliers','suppliers.php') ?></td></tr>
<?php endforeach; ?>
<?php if (!$pagination['items']): ?><tr><td colspan="8"><?php render_empty_state('No suppliers found','Adjust the filters or select another permitted company scope.'); ?></td></tr><?php endif; ?>
</tbody></table></div>
<?= render_pagination($pagination,['q'=>query_string('q'),'status'=>$status,'risk'=>$risk]) ?>
</section>

<div class="dashboard-grid">
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Commercial terms</span><h2>Contracts and renewals</h2></div><span class="panel-meta"><?= count($contracts) ?> agreements</span></header><div class="compact-list"><?php foreach($contracts as $contract): ?><article><div><strong><?= h($contract['title']) ?></strong><small><?= h(data_supplier_name($contract['supplier_id'])) ?> · <?= h($contract['number']) ?> · Ends <?= h(date_us($contract['end_date'])) ?></small></div><div class="right-summary"><?= badge($contract['status']) ?><b><?= compact_money($contract['annual_value']) ?></b></div></article><?php endforeach; ?><?php if(!$contracts): render_empty_state('No contracts','No supplier agreements are visible in this scope.'); endif; ?></div></section>
<section class="panel"><header class="panel-head"><div><span class="eyebrow">Supplier contacts</span><h2>Primary relationship owners</h2></div><span class="panel-meta"><?= count($supplierContacts) ?> contacts</span></header><div class="compact-list"><?php foreach(array_slice($supplierContacts,0,7) as $contact): ?><article><div><strong><?= h($contact['name']) ?></strong><small><?= h(data_supplier_name($contact['supplier_id'])) ?> · <?= h($contact['title']) ?></small></div><a href="mailto:<?= h($contact['email']) ?>"><?= h($contact['email']) ?></a></article><?php endforeach; ?><?php if(!$supplierContacts): render_empty_state('No supplier contacts','No supplier contacts are visible in this scope.'); endif; ?></div></section>
</div>

<?php if(can('suppliers.create')): ?><div class="modal" id="supplierModal" hidden><div class="modal-backdrop" data-modal-close></div><section class="modal-card" role="dialog" aria-modal="true"><header><div><span class="eyebrow">New supplier master record</span><h2>Create supplier</h2></div><button type="button" data-modal-close>×</button></header><form class="modal-body form-grid" action="<?= h(app_url('action.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_supplier"><input type="hidden" name="return_to" value="suppliers.php"><label><span>Supplier number</span><input name="supplier_number" required value="SUP-<?= str_pad((string)data_next_id('suppliers'),4,'0',STR_PAD_LEFT) ?>"></label><label><span>Display name</span><input name="name" maxlength="190" required></label><label class="span-2"><span>Legal name</span><input name="legal_name"></label><label><span>Category</span><select name="category_id"><?= category_options(1) ?></select></label><label><span>Owner</span><select name="owner_id"><?= user_options(current_user()['id']) ?></select></label><label><span>Supplier status</span><select name="status"><?php foreach(['candidate','approved','preferred','conditional','blocked'] as $value): ?><option value="<?= h($value) ?>"><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label><label><span>Risk</span><select name="risk"><?php foreach(['low','medium','high','critical'] as $value): ?><option value="<?= h($value) ?>" <?= $value==='medium'?'selected':'' ?>><?= h(status_label($value)) ?></option><?php endforeach; ?></select></label><label><span>Annual spend</span><input type="number" min="0" step="0.01" name="annual_spend" value="0"></label><label><span>Payment terms</span><input name="payment_terms" value="Net 30"></label><label class="span-2"><span>Website</span><input type="url" name="website" placeholder="https://"></label><div class="span-2"><span class="field-label">Company access</span><?= company_checkbox_grid(current_company_id()==='enterprise'?[1]:[(int)current_company_id()]) ?></div><div class="span-2 modal-actions"><button class="button secondary" type="button" data-modal-close>Cancel</button><button class="button primary" type="submit">Create supplier</button></div></form></section></div><?php endif; ?>
<?php render_app_end(); ?>
