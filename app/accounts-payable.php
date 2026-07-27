<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/accounts_payable.php';
require_permission('accounts_payable.view');

$tab=query_string('tab','overview');$allowed=['overview','schedules','batches','credits','close','governance'];if(!in_array($tab,$allowed,true))$tab='overview';
$metrics=accounts_payable_portfolio_metrics();$schedules=accounts_payable_schedules();$batches=accounts_payable_batches();$credits=accounts_payable_credits();$periods=accounts_payable_periods();$instructions=accounts_payable_instructions();$events=accounts_payable_events();$grni=accounts_payable_grni_rows();
$batch=query_int('batch')?accounts_payable_find_batch(query_int('batch')):null;$batchItems=$batch?accounts_payable_batch_items((int)$batch['id']):[];$executions=$batch?accounts_payable_executions((int)$batch['id']):[];
$period=query_int('period')?accounts_payable_find_period(query_int('period')):($periods[0]??null);$owner=(int)current_user()['id'];$reviewer=$owner===3?6:3;$approver=in_array($owner,[3,6],true)?1:6;$company=current_company_id()==='enterprise'?2:(int)current_company_id();$periodReviewer=(int)($period['reviewer_id']??$reviewer);$periodApprover=$owner;foreach(data_collection('users')as$user)if(!in_array((int)$user['id'],[(int)($period['owner_id']??0),$periodReviewer],true)){$periodApprover=(int)$user['id'];if($periodApprover===$owner)break;}
if(query_string('export')==='csv')accounts_payable_export_csv($batches,$schedules,$credits,$events);
$actions='<a class="button secondary" href="'.h(app_url('fulfillment.php')).'">Fulfillment & Invoice Match</a><a class="button secondary" href="'.h(app_url('supplier-portal.php')).'">Supplier Portal</a>';
if(can('agent.view'))$actions.='<a class="button secondary" href="'.h(app_url('agent.php?prompt='.rawurlencode('Analyze accounts payable cash requirements, payment batches, supplier credits, GRNI accruals, settlement risk, reconciliation exceptions, and financial close readiness.'))).'">Ask AP Agent</a>';
if(can('accounts_payable.export'))$actions.='<a class="button primary" href="'.h(app_url('accounts-payable.php?export=csv')).'">Export AP Governance</a>';
render_app_start('Accounts Payable, Payment Execution & Financial Close','accounts_payable','Settlement and close command center','Schedule approved invoices, enforce payment dual control, record external settlement evidence, reconcile payments, manage credits and accruals, and certify accounting periods.',$actions);
echo accounts_payable_styles();
?>
<section class="metric-grid metric-grid-6">
<article class="metric-card"><span>Due in 7 days</span><strong><?= compact_money($metrics['due_7']) ?></strong><small>Approved invoice cash demand</small></article>
<article class="metric-card"><span>Approved unscheduled</span><strong><?= compact_money($metrics['approved_unscheduled']) ?></strong><small><?= number_format($metrics['approved_unscheduled_count']) ?> invoice(s)</small></article>
<article class="metric-card"><span>Released unsettled</span><strong><?= compact_money($metrics['released_unsettled']) ?></strong><small><?= number_format($metrics['unsettled_batch_count']) ?> batch(es)</small></article>
<article class="metric-card"><span>Supplier credits</span><strong><?= compact_money($metrics['unapplied_credit_amount']) ?></strong><small>Validated and available</small></article>
<article class="metric-card"><span>GRNI exposure</span><strong><?= compact_money($metrics['grni_amount']) ?></strong><small>Goods received, not invoiced</small></article>
<article class="metric-card"><span>Settled payments</span><strong><?= compact_money($metrics['settled_amount']) ?></strong><small>Settlement evidence recorded</small></article>
</section>
<nav class="ap-tabs" aria-label="Accounts payable workspace"><?php foreach(['overview'=>'Portfolio','schedules'=>'Payment schedules','batches'=>'Payment batches','credits'=>'Credits & instructions','close'=>'Accruals & close','governance'=>'Governance'] as$key=>$label): ?><a class="<?= $tab===$key?'active':'' ?>" href="?tab=<?= h($key) ?>"><?= h($label) ?></a><?php endforeach; ?></nav>

<?php if(!accounts_payable_tables_ready()): ?><section class="notice-card critical-notice"><div><strong>Section 23 migration required</strong><p>Production writes are blocked until the deferred AP migration is imported. Demo Mode remains functional.</p></div></section><?php endif; ?>

<?php require dirname(__DIR__).'/includes/app/accounts_payable_views/'.$tab.'.php'; ?>
<?php render_app_end(); ?>
