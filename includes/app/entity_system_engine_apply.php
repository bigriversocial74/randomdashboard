<?php
declare(strict_types=1);

function entity_system_apply_template(string $code, string $evidence, int $reviewerId = 0, int $approverId = 0): array
{
    entity_system_require_capability('apply');
    entity_system_require_enterprise_context();
    entity_system_require_tables();
    $preview = entity_system_template_preview($code);
    if (!$preview['validation']['valid']) {
        throw new RuntimeException('Resolve relationship validation errors before applying the template.');
    }
    if (trim($evidence) === '') throw new RuntimeException('Template-application evidence is required.');
    $userId = (int)current_user()['id'];
    entity_system_assert_three_person_governance($userId, $reviewerId, $approverId);
    foreach (entity_system_applications() as $application) {
        if ($application['template_code'] === $code && $application['status'] === 'applied') return $application;
    }

    $manifest = [
        'template'=>$code,
        'version'=>$preview['template']['version'],
        'canonical_company_ids_preserved'=>true,
        'companies'=>$preview['carryover']['company_ids'],
        'carryover'=>$preview['carryover'],
        'services'=>$preview['template']['services'],
        'authorities'=>$preview['template']['authority'],
    ];
    $hash = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES));
    $application = entity_system_save_application([
        'id'=>null,
        'application_number'=>'ENT-APP-'.date('Ymd').'-'.str_pad((string)(count(entity_system_applications()) + 1), 4, '0', STR_PAD_LEFT),
        'template_code'=>$code,'template_version'=>$preview['template']['version'],'status'=>'applied',
        'manifest_json'=>json_encode($manifest, JSON_UNESCAPED_SLASHES),'relationship_hash'=>$hash,
        'prepared_by'=>$userId,'reviewed_by'=>$reviewerId,'approved_by'=>$approverId,
        'evidence_note'=>mb_substr($evidence, 0, 5000),'applied_at'=>date('Y-m-d H:i:s'),
        'rolled_back_at'=>null,'rollback_note'=>'',
    ]);
    $root = entity_system_find_by_code('GRUBER-ENTERPRISE') ?? entity_system_save_entity([
        'id'=>null,'entity_code'=>'GRUBER-ENTERPRISE','entity_name'=>'Gruber Enterprise','legal_name'=>'Gruber Enterprise',
        'entity_type'=>'enterprise_group','parent_entity_id'=>null,'company_id'=>null,'status'=>'active','country_code'=>'US',
        'base_currency'=>'USD','timezone'=>'America/Phoenix','fiscal_calendar_code'=>'CALENDAR','effective_from'=>date('Y-m-d'),
        'effective_to'=>null,'application_id'=>$application['id'],
    ]);
    $map = ['enterprise'=>(int)$root['id']];
    foreach ($preview['template']['services'] as $serviceCode) {
        $entityCode = 'GRUBER-'.strtoupper(str_replace('_', '-', $serviceCode));
        $service = entity_system_find_by_code($entityCode) ?? entity_system_save_entity([
            'id'=>null,'entity_code'=>$entityCode,'entity_name'=>'Gruber '.entity_system_service_name($serviceCode),
            'legal_name'=>'Gruber '.entity_system_service_name($serviceCode),'entity_type'=>'shared_service',
            'parent_entity_id'=>$root['id'],'company_id'=>null,'status'=>'active','country_code'=>'US','base_currency'=>'USD',
            'timezone'=>'America/Phoenix','fiscal_calendar_code'=>'CALENDAR','effective_from'=>date('Y-m-d'),
            'effective_to'=>null,'application_id'=>$application['id'],
        ]);
        $map[$serviceCode] = (int)$service['id'];
    }
    foreach (data_collection('companies') as $company) {
        $entityCode = 'COMP-'.strtoupper((string)$company['code']);
        $entity = entity_system_find_by_code($entityCode) ?? entity_system_save_entity([
            'id'=>null,'entity_code'=>$entityCode,'entity_name'=>$company['name'],'legal_name'=>$company['name'],
            'entity_type'=>'operating_company','parent_entity_id'=>$root['id'],'company_id'=>(int)$company['id'],
            'status'=>'active','country_code'=>'US','base_currency'=>'USD','timezone'=>'America/Phoenix',
            'fiscal_calendar_code'=>'CALENDAR','effective_from'=>date('Y-m-d'),'effective_to'=>null,
            'application_id'=>$application['id'],
        ]);
        $entityId = (int)$entity['id'];
        $map['company_'.$company['id']] = $entityId;
        $exists = false;
        foreach (entity_system_raw_bindings() as $binding) {
            if ((int)$binding['company_id'] === (int)$company['id'] && entity_system_row_effective($binding)) $exists = true;
        }
        if (!$exists) {
            entity_system_save_binding([
                'id'=>null,'application_id'=>$application['id'],'entity_id'=>$entityId,'company_id'=>(int)$company['id'],
                'binding_type'=>'operational','is_primary'=>1,'status'=>'active','effective_from'=>date('Y-m-d'),'effective_to'=>null,
            ]);
        }
        entity_system_save_relationship([
            'id'=>null,'application_id'=>$application['id'],'from_entity_id'=>$root['id'],'to_entity_id'=>$entityId,
            'relationship_type'=>'owns','service_domain'=>'governance','status'=>'active','effective_from'=>date('Y-m-d'),
            'effective_to'=>null,'evidence_note'=>'Canonical company ID '.$company['id'].' preserved.',
        ]);
        foreach ($preview['template']['services'] as $serviceCode) {
            entity_system_save_relationship([
                'id'=>null,'application_id'=>$application['id'],'from_entity_id'=>$map[$serviceCode],'to_entity_id'=>$entityId,
                'relationship_type'=>'provides_service','service_domain'=>$serviceCode,'status'=>'active',
                'effective_from'=>date('Y-m-d'),'effective_to'=>null,
                'evidence_note'=>'Applied from '.$preview['template']['name'].'.',
            ]);
        }
        foreach (array_values(array_unique(array_merge($company['modules'] ?? [], ['supplier_portal','accounts_payable','strategy']))) as $module) {
            entity_system_save_profile([
                'id'=>null,'application_id'=>$application['id'],'entity_id'=>$entityId,'module_code'=>$module,'enabled'=>1,
                'inheritance_mode'=>in_array($module, ['supplier_portal','accounts_payable','strategy'], true) ? 'inherited' : 'local',
                'source_entity_id'=>$module === 'accounts_payable' ? ($map['accounts_payable'] ?? null) : ($map['procurement'] ?? null),
                'settings_json'=>'{}','status'=>'active',
            ]);
        }
        foreach ($preview['template']['authority'] as $domain => $mode) {
            entity_system_save_authority([
                'id'=>null,'application_id'=>$application['id'],'entity_id'=>$entityId,'domain_code'=>$domain,
                'authority_entity_id'=>entity_system_authority_entity($mode, $map, $entityId),'authority_mode'=>$mode,
                'override_policy'=>$mode === 'local' ? 'local_only' : 'governed_override','status'=>'active',
                'effective_from'=>date('Y-m-d'),'effective_to'=>null,'evidence_note'=>'Template authority policy.',
            ]);
        }
    }
    foreach (data_collection('users') as $user) {
        foreach (array_unique(array_map('intval', $user['company_ids'] ?? [])) as $companyId) {
            if (!isset($map['company_'.$companyId])) continue;
            entity_system_save_access_scope([
                'id'=>null,'application_id'=>$application['id'],'user_id'=>(int)$user['id'],
                'entity_id'=>$map['company_'.$companyId],'module_code'=>'*','scope_type'=>'entity_only',
                'include_descendants'=>0,'status'=>'active','effective_from'=>date('Y-m-d'),'effective_to'=>null,
            ]);
        }
    }
    entity_system_add_event((int)$application['id'], (int)$root['id'], 'template_applied', 'business_entity_application', (int)$application['id'], 'validated', 'applied', 'medium', $evidence);
    data_add_audit('Business Entities', 'template_applied', 'business_entity_application', (int)$application['id'], null, $application, null);
    return $application;
}
