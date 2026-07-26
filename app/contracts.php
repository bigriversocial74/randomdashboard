<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app/contract_management.php';
require_permission('suppliers.view');

$contractId=query_int('id');$supplierId=query_int('supplier_id');
$selected=$contractId?contract_find_record($contractId):null;if($contractId&&!$selected){flash('error','The supplier contract is outside the active company scope.');redirect_to(app_url('contracts.php'));}
if(!$selected&&$supplierId){$matches=contract_records($supplierId);$selected=$matches[0]??null;}
$selected??=contract_default_record();$records=contract_records();
$error=null;$blueprint=null;try{if($selected)$blueprint=contract_blueprint($selected);}catch(Throwable $exception){$error=$exception->getMessage();}
$obligations=$selected?contract_obligations((int)$selected['id']):[];$amendments=$selected?contract_amendments((int)$selected['id']):[];$decisions=$selected?contract_decisions((int)$selected['id']):[];$events=$selected?contract_events((int)$selected['id']):[];$metrics=$selected?contract_metrics($selected,$obligations,$amendments,$decisions):[];
if(query_string('export')==='csv'&&$selected)contract_export_csv($selected,$metrics,$obligations,$amendments,$decisions,$events);
$agentPrompt='Review contract lifecycle and renewal governance for '.($selected['number']??'the selected supplier agreement').'. Evaluate contract value, committed and actual spend, off-contract spend, notice deadlines, obligations, SLA breaches, amendments, Section 15 supplier performance, corrective actions, alternative suppliers, renewal readiness, approval evidence, negotiation objectives, and recommended commercial decision.';
$headerActions='<a class="button ghost" href="'.h(app_url('suppliers.php')).'">Supplier Master</a>';
if($blueprint&&$blueprint['performance'])$headerActions.='<a class="button ghost" href="'.h(app_url('performance.php?id='.(int)$blueprint['performance']['id'])).'">Performance Evidence</a>';
if($selected&&can('reports.export'))$headerActions.='<a class="button secondary" href="'.h(app_url('contracts.php?id='.(int)$selected['id'].'&export=csv')).'">Export Contract</a>';
$headerActions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode($agentPrompt))).'">Analyze in Agent</a>';
render_app_start('Contract Lifecycle & Renewal Governance','contracts','Commercial relationship control','Manage supplier agreements, obligations, SLA evidence, amendments, notice deadlines, renewal decisions, approvals, and implementation.',$headerActions);
require dirname(__DIR__) . '/includes/app/contract_view_styles.php';
require dirname(__DIR__) . '/includes/app/contract_view.php';
render_app_end();
