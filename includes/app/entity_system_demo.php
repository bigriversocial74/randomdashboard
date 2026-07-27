<?php
declare(strict_types=1);

function entity_system_demo_entities(): array
{
    $companies = data_collection('companies');
    $rows = [
        ['id'=>1,'entity_code'=>'GRUBER-ENTERPRISE','entity_name'=>'Gruber Enterprise','legal_name'=>'Gruber Enterprise','entity_type'=>'enterprise_group','parent_entity_id'=>null,'company_id'=>null,'status'=>'active','country_code'=>'US','base_currency'=>'USD','timezone'=>'America/Phoenix','fiscal_calendar_code'=>'CALENDAR','effective_from'=>'2026-01-01','effective_to'=>null,'application_id'=>1],
        ['id'=>2,'entity_code'=>'GRUBER-PROCUREMENT','entity_name'=>'Gruber Shared Procurement','legal_name'=>'Gruber Shared Procurement Services','entity_type'=>'shared_service','parent_entity_id'=>1,'company_id'=>null,'status'=>'active','country_code'=>'US','base_currency'=>'USD','timezone'=>'America/Phoenix','fiscal_calendar_code'=>'CALENDAR','effective_from'=>'2026-01-01','effective_to'=>null,'application_id'=>1],
        ['id'=>3,'entity_code'=>'GRUBER-ACCOUNTS-PAYABLE','entity_name'=>'Gruber Shared Finance & Accounts Payable','legal_name'=>'Gruber Shared Finance Services','entity_type'=>'shared_service','parent_entity_id'=>1,'company_id'=>null,'status'=>'active','country_code'=>'US','base_currency'=>'USD','timezone'=>'America/Phoenix','fiscal_calendar_code'=>'CALENDAR','effective_from'=>'2026-01-01','effective_to'=>null,'application_id'=>1],
    ];
    foreach ($companies as $company) {
        $rows[] = ['id'=>100+(int)$company['id'],'entity_code'=>'COMP-'.strtoupper((string)$company['code']),'entity_name'=>$company['name'],'legal_name'=>$company['name'],'entity_type'=>'operating_company','parent_entity_id'=>1,'company_id'=>(int)$company['id'],'status'=>'active','country_code'=>'US','base_currency'=>'USD','timezone'=>'America/Phoenix','fiscal_calendar_code'=>'CALENDAR','effective_from'=>'2026-01-01','effective_to'=>null,'application_id'=>1];
    }
    return $rows;
}

function entity_system_demo_relationships(): array
{
    $rows = [];
    $id = 1;
    foreach (data_collection('companies') as $company) {
        $entityId = 100 + (int)$company['id'];
        $rows[] = ['id'=>$id++,'application_id'=>1,'from_entity_id'=>1,'to_entity_id'=>$entityId,'relationship_type'=>'owns','service_domain'=>'governance','status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null,'evidence_note'=>'Existing company preserved as an operating entity.'];
        $rows[] = ['id'=>$id++,'application_id'=>1,'from_entity_id'=>2,'to_entity_id'=>$entityId,'relationship_type'=>'provides_service','service_domain'=>'procurement','status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null,'evidence_note'=>'Shared procurement serves the operating business.'];
        $rows[] = ['id'=>$id++,'application_id'=>1,'from_entity_id'=>3,'to_entity_id'=>$entityId,'relationship_type'=>'provides_service','service_domain'=>'accounts_payable','status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null,'evidence_note'=>'Shared finance and AP serve the operating business.'];
    }
    return $rows;
}

function entity_system_demo_bindings(): array
{
    return array_map(static fn(array $company): array => [
        'id'=>(int)$company['id'],'application_id'=>1,'entity_id'=>100+(int)$company['id'],'company_id'=>(int)$company['id'],
        'binding_type'=>'operational','is_primary'=>1,'status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null,
    ], data_collection('companies'));
}

function entity_system_demo_module_profiles(): array
{
    $rows = [];
    $id = 1;
    foreach (data_collection('companies') as $company) {
        foreach (array_values(array_unique($company['modules'] ?? [])) as $module) {
            $rows[] = ['id'=>$id++,'application_id'=>1,'entity_id'=>100+(int)$company['id'],'module_code'=>$module,'enabled'=>1,'inheritance_mode'=>'local','source_entity_id'=>null,'settings_json'=>'{}','status'=>'active'];
        }
        foreach (['supplier_portal','accounts_payable','strategy'] as $module) {
            $rows[] = ['id'=>$id++,'application_id'=>1,'entity_id'=>100+(int)$company['id'],'module_code'=>$module,'enabled'=>1,'inheritance_mode'=>'inherited','source_entity_id'=>$module==='accounts_payable'?3:2,'settings_json'=>'{}','status'=>'active'];
        }
    }
    return $rows;
}

function entity_system_demo_authorities(): array
{
    $domains = ['supplier_master'=>1,'item_master'=>1,'contracts'=>2,'sourcing'=>2,'purchase_orders'=>null,'inventory'=>null,'accounts_payable'=>3,'payments'=>3,'reporting'=>1,'security'=>1];
    $rows = [];
    $id = 1;
    foreach ($domains as $domain => $authority) {
        if ($authority !== null) {
            foreach (data_collection('companies') as $company) {
                $rows[] = ['id'=>$id++,'application_id'=>1,'entity_id'=>100+(int)$company['id'],'domain_code'=>$domain,'authority_entity_id'=>$authority,'authority_mode'=>'authoritative','override_policy'=>'governed_override','status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null,'evidence_note'=>'Gruber shared-services template authority.'];
            }
        } else {
            foreach (data_collection('companies') as $company) {
                $entityId = 100 + (int)$company['id'];
                $rows[] = ['id'=>$id++,'application_id'=>1,'entity_id'=>$entityId,'domain_code'=>$domain,'authority_entity_id'=>$entityId,'authority_mode'=>'authoritative','override_policy'=>'local_only','status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null,'evidence_note'=>'Operating-company transaction authority preserved.'];
            }
        }
    }
    return $rows;
}

function entity_system_demo_access_scopes(): array
{
    $rows = [];
    $id = 1;
    foreach (data_collection('users') as $user) {
        foreach (array_values(array_unique(array_map('intval', $user['company_ids'] ?? []))) as $companyId) {
            $rows[] = ['id'=>$id++,'application_id'=>1,'user_id'=>(int)$user['id'],'entity_id'=>100+$companyId,'module_code'=>'*','scope_type'=>'entity_only','include_descendants'=>0,'status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null];
        }
        if (array_intersect($user['role_codes'] ?? [], ['system_administrator','executive'])) {
            $rows[] = ['id'=>$id++,'application_id'=>1,'user_id'=>(int)$user['id'],'entity_id'=>1,'module_code'=>'*','scope_type'=>'entity_and_descendants','include_descendants'=>1,'status'=>'active','effective_from'=>'2026-01-01','effective_to'=>null];
        }
    }
    return $rows;
}

function entity_system_demo_applications(): array
{
    return [['id'=>1,'application_number'=>'ENT-APP-2026-0001','template_code'=>'gruber_shared_services','template_version'=>'1.0.0','status'=>'applied','manifest_json'=>'{"companies":6,"services":["procurement","accounts_payable"],"canonical_company_ids_preserved":true}','relationship_hash'=>hash('sha256','gruber_shared_services|1.0.0|1,2,3,4,5,6'),'prepared_by'=>1,'reviewed_by'=>6,'approved_by'=>2,'evidence_note'=>'Initial six-business relationship migration and shared-services integration.','applied_at'=>'2026-07-27 08:00:00','rolled_back_at'=>null,'rollback_note'=>'','created_at'=>'2026-07-27 08:00:00','updated_at'=>'2026-07-27 08:00:00']];
}
function entity_system_demo_connections(): array { return [['id'=>1,'connection_number'=>'INT-CONN-0001','connection_name'=>'Enterprise ERP placeholder','provider'=>'unconfigured','system_type'=>'erp','environment'=>'production','auth_type'=>'external_secret_reference','secret_reference'=>'vault://integrations/gruber-erp','adapter_version'=>'1.0','status'=>'draft','capabilities_json'=>'["companies","suppliers","items","purchase_orders","invoices"]','last_success_at'=>null,'created_by'=>1,'reviewer_id'=>6,'evidence_note'=>'Connection metadata only; no credentials are stored.']]; }
function entity_system_demo_integration_bindings(): array { return [['id'=>1,'connection_id'=>1,'entity_id'=>1,'external_organization_id'=>'UNCONFIGURED','external_company_id'=>'','external_tenant_id'=>'','sync_direction'=>'bidirectional','enabled_domains_json'=>'["companies","suppliers","items"]','data_authority'=>'internal_governed','status'=>'draft','effective_from'=>'2026-07-27','effective_to'=>null,'evidence_note'=>'Provider organization must be selected before activation.']]; }
function entity_system_demo_external_mappings(): array
{
    $rows=[];$id=1;
    foreach (data_collection('companies') as $company) {
        $rows[]=['id'=>$id++,'connection_id'=>1,'entity_id'=>100+(int)$company['id'],'domain_code'=>'companies','internal_record_type'=>'company','internal_record_id'=>(int)$company['id'],'external_record_type'=>'company','external_record_id'=>'UNMAPPED-'.$company['code'],'external_parent_id'=>'UNCONFIGURED','mapping_status'=>'pending_review','effective_from'=>'2026-07-27','effective_to'=>null,'evidence_note'=>'Existing company relationship preserved; external identity requires validation.'];
    }
    return $rows;
}
function entity_system_demo_sync_runs(): array { return []; }
function entity_system_demo_integration_events(): array { return [['id'=>1,'connection_id'=>1,'entity_id'=>102,'direction'=>'outbox','event_type'=>'entity_template_applied','record_type'=>'business_entity_application','record_id'=>1,'idempotency_key'=>hash('sha256','entity-template-applied-1'),'payload_checksum'=>hash('sha256','gruber_shared_services'),'status'=>'ready','attempt_count'=>0,'occurred_at'=>'2026-07-27 08:00:00','processed_at'=>null,'evidence_note'=>'Integration-ready event created without transmitting data.']]; }
function entity_system_demo_conflicts(): array { return []; }
function entity_system_demo_events(): array { return [['id'=>1,'application_id'=>1,'entity_id'=>102,'event_type'=>'template_applied','entity_type'=>'business_entity_application','entity_record_id'=>1,'from_status'=>'validated','to_status'=>'applied','severity'=>'medium','evidence_note'=>'Six companies bound without modifying canonical company IDs or transactions.','created_by'=>1,'created_at'=>'2026-07-27 08:00:00']]; }
