<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/entity_system.php';
require_permission('companies.view');
$tab=query_string('tab','overview');$tabs=['overview'=>'Overview','templates'=>'Templates','hierarchy'=>'Hierarchy','authorities'=>'Authorities & Access','integrations'=>'Connections','mappings'=>'Mappings','governance'=>'Governance'];if(!isset($tabs[$tab]))$tab='overview';
if(query_string('export')==='csv')entity_system_export_csv();
$metrics=entity_system_metrics();$readiness=entity_system_readiness();$entities=entity_system_entities();$relationships=entity_system_relationships();$applications=entity_system_applications();$connections=entity_system_connections();$bindings=entity_system_integration_bindings();$mappings=entity_system_external_mappings();$conflicts=entity_system_conflicts();$events=entity_system_events();$authorities=entity_system_authorities();$scopes=entity_system_access_scopes();$selectedTemplate=query_string('template','gruber_shared_services');$preview=entity_system_template_preview($selectedTemplate);
$actions='<a class="button ghost" href="'.h(app_url('admin/companies.php')).'">Companies</a><a class="button ghost" href="'.h(app_url('process-maps.php')).'">Visual Process Maps</a>';if(can('reports.export'))$actions.='<a class="button secondary" href="'.h(app_url('entity-system.php?export=csv')).'">Export Entities</a>';$actions.='<a class="button primary" href="'.h(app_url('agent.php?prompt='.rawurlencode('Review the Gruber business entity hierarchy, preserved company relationships, shared services, data authorities, access scopes, integration connections, external ID mappings, synchronization events, and unresolved conflicts. Identify structural or integration-readiness gaps without changing canonical transactions.'))).'">Analyze in Agent</a>';
render_app_start('Business Entity & Integration Foundation','entities','Modular organization and cross-system governance','Preserve the six operating businesses while adding reusable entity templates, shared services, authority policies, access scopes, and integration mappings.',$actions);
?>
<nav class="tab-row" aria-label="Entity workspace sections"><?php foreach($tabs as$key=>$label): ?><a class="<?= $tab===$key?'active':'' ?>" href="<?= h(app_url('entity-system.php?tab='.$key.($key==='templates'?'&template='.rawurlencode($selectedTemplate):''))) ?>"><?= h($label) ?></a><?php endforeach; ?></nav>
<?php require dirname(__DIR__).'/includes/app/entity_system_view.php';render_app_end();
