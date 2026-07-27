<?php
declare(strict_types=1);

function entity_system_rollback_application(int $id, string $note): array
{
    entity_system_require_capability('administer');
    entity_system_require_enterprise_context();
    $application = entity_system_find_application($id);
    if (!$application) throw new RuntimeException('The template application was not found.');
    if ($application['status'] !== 'applied') throw new RuntimeException('Only an applied template can be rolled back.');
    if (trim($note) === '') throw new RuntimeException('Rollback evidence is required.');
    $before = $application;
    $application['status'] = 'rolled_back';
    $application['rolled_back_at'] = date('Y-m-d H:i:s');
    $application['rollback_note'] = mb_substr($note, 0, 5000);
    $saved = entity_system_save_application($application);
    if (data_is_demo()) {
        foreach (entity_system_demo_collection('business_entity_relationships', 'entity_system_demo_relationships') as $relationship) {
            if ((int)($relationship['application_id'] ?? 0) !== $id) continue;
            $relationship['status'] = 'inactive';
            entity_system_demo_save('business_entity_relationships', $relationship, 'entity_system_demo_relationships');
        }
    } else {
        $pdo = production_database_connection();
        $pdo->prepare("UPDATE business_entity_relationships SET status='inactive' WHERE application_id=?")->execute([$id]);
        $pdo->prepare("UPDATE business_entity_module_profiles SET status='inactive' WHERE application_id=?")->execute([$id]);
        $pdo->prepare("UPDATE business_entity_data_authorities SET status='inactive' WHERE application_id=?")->execute([$id]);
        $pdo->prepare("UPDATE business_entities SET status='inactive' WHERE application_id=? AND company_id IS NULL AND entity_type='shared_service'")->execute([$id]);
    }
    entity_system_add_event($id, null, 'template_rolled_back', 'business_entity_application', $id, 'applied', 'rolled_back', 'high', $note);
    data_add_audit('Business Entities', 'template_rolled_back', 'business_entity_application', $id, $before, $saved, null);
    return $saved;
}

function entity_system_validate_connection_payload(array $record): void
{
    if (trim((string)($record['connection_name'] ?? '')) === '') throw new RuntimeException('Connection name is required.');
    $secret = trim((string)($record['secret_reference'] ?? ''));
    if ($secret === '' || !preg_match('#^(vault|secret|env)://#i', $secret)) {
        throw new RuntimeException('Use an external secret reference such as vault://, secret://, or env://.');
    }
    $serialized = strtolower((string)json_encode($record));
    if (preg_match('/(routing_number|account_number|private_key|client_secret|password)\s*[=:]/', $serialized)) {
        throw new RuntimeException('Raw credentials or banking details cannot be stored in integration metadata.');
    }
}

function entity_system_readiness(): array
{
    $companyIds = entity_system_context_company_ids();
    if (current_company_id() === 'enterprise' && can_use_enterprise_view()) $companyIds = permitted_company_ids();
    $validation = entity_system_validate_current_relationships($companyIds);
    $bindings = entity_system_bindings();
    $bound = array_unique(array_map('intval', array_column($bindings, 'company_id')));
    $issues = $validation['errors'];
    foreach (array_diff($companyIds, $bound) as $companyId) {
        $issues[] = 'Company '.$companyId.' has no active entity binding.';
    }
    foreach (entity_system_connections() as $connection) {
        if ($connection['status'] !== 'active') continue;
        $hasBinding = array_filter(entity_system_integration_bindings(), static fn(array $binding): bool =>
            (int)$binding['connection_id'] === (int)$connection['id'] && $binding['status'] === 'active'
        );
        if (!$hasBinding) $issues[] = 'Active connection '.$connection['connection_name'].' has no active entity binding.';
    }
    return [
        'ready'=>$issues === [],
        'issues'=>array_values(array_unique($issues)),
        'warnings'=>$validation['warnings'],
        'score'=>max(0, 100 - count($issues) * 15 - count($validation['warnings']) * 2),
    ];
}
