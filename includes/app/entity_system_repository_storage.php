<?php
declare(strict_types=1);

function entity_system_save_row(string $table, string $key, callable $seed, array $record, array $fields): array
{
    entity_system_require_tables();
    $record['created_at'] = $record['created_at'] ?? date('Y-m-d H:i:s');
    $record['updated_at'] = date('Y-m-d H:i:s');
    if (data_is_demo()) return entity_system_demo_save($key, $record, $seed);

    $pdo = production_database_connection();
    $id = (int)($record['id'] ?? 0);
    $values = [];
    foreach ($fields as $field) {
        $value = $record[$field] ?? null;
        if (is_array($value)) $value = json_encode($value, JSON_UNESCAPED_SLASHES);
        $values[] = $value;
    }
    if ($id > 0) {
        $values[] = $id;
        $pdo->prepare(
            'UPDATE '.$table.' SET '
            .implode(',', array_map(static fn(string $field): string => $field.'=?', $fields))
            .',updated_at=NOW() WHERE id=?'
        )->execute($values);
    } else {
        $pdo->prepare(
            'INSERT INTO '.$table.' ('.implode(',', $fields).') VALUES ('
            .implode(',', array_fill(0, count($fields), '?')).')'
        )->execute($values);
        $id = (int)$pdo->lastInsertId();
    }
    $record['id'] = $id;
    return $record;
}

function entity_system_save_entity(array $record): array
{
    return entity_system_save_row('business_entities', 'business_entities', 'entity_system_demo_entities', $record, [
        'entity_code','entity_name','legal_name','entity_type','parent_entity_id','company_id','status',
        'country_code','base_currency','timezone','fiscal_calendar_code','effective_from','effective_to','application_id',
    ]);
}

function entity_system_save_relationship(array $record): array
{
    entity_system_assert_entity_access((int)$record['from_entity_id'], '*', true);
    entity_system_assert_entity_access((int)$record['to_entity_id'], '*', true);
    return entity_system_save_row('business_entity_relationships', 'business_entity_relationships', 'entity_system_demo_relationships', $record, [
        'application_id','from_entity_id','to_entity_id','relationship_type','service_domain','status','effective_from','effective_to','evidence_note',
    ]);
}

function entity_system_save_binding(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], '*', true);
    if (!data_company_within_scope((int)$record['company_id']) && !(current_company_id() === 'enterprise' && can_use_enterprise_view())) {
        throw new RuntimeException('The company binding is outside the active scope.');
    }
    return entity_system_save_row('business_entity_company_bindings', 'business_entity_company_bindings', 'entity_system_demo_bindings', $record, [
        'application_id','entity_id','company_id','binding_type','is_primary','status','effective_from','effective_to',
    ]);
}

function entity_system_save_profile(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], (string)$record['module_code'], true);
    if (!empty($record['source_entity_id'])) entity_system_assert_entity_access((int)$record['source_entity_id'], (string)$record['module_code'], true);
    return entity_system_save_row('business_entity_module_profiles', 'business_entity_module_profiles', 'entity_system_demo_module_profiles', $record, [
        'application_id','entity_id','module_code','enabled','inheritance_mode','source_entity_id','settings_json','status',
    ]);
}

function entity_system_save_authority(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], '*', true);
    entity_system_assert_entity_access((int)$record['authority_entity_id'], '*', true);
    return entity_system_save_row('business_entity_data_authorities', 'business_entity_data_authorities', 'entity_system_demo_authorities', $record, [
        'application_id','entity_id','domain_code','authority_entity_id','authority_mode','override_policy','status','effective_from','effective_to','evidence_note',
    ]);
}

function entity_system_save_access_scope(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], (string)$record['module_code'], true);
    $target = data_find('users', (int)$record['user_id']);
    if (!$target || ($target['status'] ?? '') !== 'active') throw new RuntimeException('Entity access can only be assigned to an active user.');
    return entity_system_save_row('business_entity_access_scopes', 'business_entity_access_scopes', 'entity_system_demo_access_scopes', $record, [
        'application_id','user_id','entity_id','module_code','scope_type','include_descendants','status','effective_from','effective_to',
    ]);
}

function entity_system_save_application(array $record): array
{
    return entity_system_save_row('business_entity_template_applications', 'business_entity_template_applications', 'entity_system_demo_applications', $record, [
        'application_number','template_code','template_version','status','manifest_json','relationship_hash','prepared_by','reviewed_by','approved_by',
        'evidence_note','applied_at','rolled_back_at','rollback_note',
    ]);
}

function entity_system_save_connection(array $record): array
{
    return entity_system_save_row('integration_connections', 'integration_connections', 'entity_system_demo_connections', $record, [
        'connection_number','connection_name','provider','system_type','environment','auth_type','secret_reference','adapter_version','status',
        'capabilities_json','last_success_at','created_by','reviewer_id','evidence_note',
    ]);
}

function entity_system_save_integration_binding(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], '*', true);
    return entity_system_save_row('integration_entity_bindings', 'integration_entity_bindings', 'entity_system_demo_integration_bindings', $record, [
        'connection_id','entity_id','external_organization_id','external_company_id','external_tenant_id','sync_direction','enabled_domains_json',
        'data_authority','status','effective_from','effective_to','evidence_note',
    ]);
}

function entity_system_save_mapping(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], '*', true);
    return entity_system_save_row('external_id_mappings', 'external_id_mappings', 'entity_system_demo_external_mappings', $record, [
        'connection_id','entity_id','domain_code','internal_record_type','internal_record_id','external_record_type','external_record_id',
        'external_parent_id','mapping_status','effective_from','effective_to','evidence_note',
    ]);
}

function entity_system_save_conflict(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], '*', true);
    return entity_system_save_row('integration_conflicts', 'integration_conflicts', 'entity_system_demo_conflicts', $record, [
        'connection_id','entity_id','domain_code','internal_record_type','internal_record_id','external_record_id','conflict_type','status',
        'internal_value_json','external_value_json','resolution_json','owner_id','reviewer_id','evidence_note','resolved_at',
    ]);
}

function entity_system_add_event(?int $applicationId, ?int $entityId, string $type, string $entityType, int $recordId, ?string $from, ?string $to, string $severity, string $note): array
{
    entity_system_require_tables();
    if ($entityId !== null) entity_system_assert_entity_access($entityId, '*', false);
    $record = [
        'id'=>null,'application_id'=>$applicationId,'entity_id'=>$entityId,'event_type'=>$type,'entity_type'=>$entityType,
        'entity_record_id'=>$recordId,'from_status'=>$from,'to_status'=>$to,'severity'=>$severity,'evidence_note'=>$note,
        'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s'),
    ];
    if (data_is_demo()) return entity_system_demo_save('business_entity_events', $record, 'entity_system_demo_events');
    $pdo = production_database_connection();
    $pdo->prepare(
        'INSERT INTO business_entity_events(application_id,entity_id,event_type,entity_type,entity_record_id,from_status,to_status,severity,evidence_note,created_by)'
        .' VALUES(?,?,?,?,?,?,?,?,?,?)'
    )->execute([$applicationId,$entityId,$type,$entityType,$recordId,$from,$to,$severity,$note,$record['created_by']]);
    $record['id'] = (int)$pdo->lastInsertId();
    return $record;
}

function entity_system_queue_integration_event(int $connectionId, int $entityId, string $direction, string $type, string $recordType, int $recordId, string $key, array $payload, string $note): array
{
    entity_system_require_tables();
    entity_system_assert_entity_access($entityId, '*', true);
    $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
    foreach (entity_system_integration_events() as $event) {
        if (hash_equals((string)$event['idempotency_key'], $key)) return $event;
    }
    $record = [
        'id'=>null,'connection_id'=>$connectionId,'entity_id'=>$entityId,'direction'=>$direction,'event_type'=>$type,
        'record_type'=>$recordType,'record_id'=>$recordId,'idempotency_key'=>$key,'payload_checksum'=>$hash,'status'=>'ready',
        'attempt_count'=>0,'occurred_at'=>date('Y-m-d H:i:s'),'processed_at'=>null,'evidence_note'=>$note,
    ];
    if (data_is_demo()) return entity_system_demo_save('integration_events', $record, 'entity_system_demo_integration_events');
    $pdo = production_database_connection();
    $pdo->prepare(
        'INSERT INTO integration_events(connection_id,entity_id,direction,event_type,record_type,record_id,idempotency_key,payload_checksum,status,attempt_count,occurred_at,evidence_note)'
        .' VALUES(?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([$connectionId,$entityId,$direction,$type,$recordType,$recordId,$key,$hash,'ready',0,$record['occurred_at'],$note]);
    $record['id'] = (int)$pdo->lastInsertId();
    return $record;
}
