<?php
declare(strict_types=1);

function process_mapping_can(string $capability, ?array $user = null): bool
{
    $user ??= current_user();
    if (!$user) return false;
    if (can('process_mapping.'.$capability, $user)) return true;
    if (demo_mode_active()) {
        $roles = current_role_codes($user);
        $demoCapabilities = [
            'system_administrator'=>['view','edit','publish','execute','analyze','export','administer'],
            'executive'=>['view','analyze','export'],
            'company_administrator'=>['view','edit','execute','analyze','export'],
            'procurement_manager'=>['view','edit','execute','analyze','export'],
            'data_contributor'=>['view','execute'],
            'reviewer'=>['view','publish','execute','analyze'],
            'read_only'=>['view','analyze'],
        ];
        foreach ($roles as $role) if (in_array($capability, $demoCapabilities[$role] ?? [], true)) return true;
    }

    // Backward-compatible fallback while the deferred Section 25 and audit
    // migrations have not yet been imported into a populated production DB.
    $fallback = match ($capability) {
        'view', 'analyze' => 'companies.view',
        'edit', 'publish', 'execute', 'administer' => 'companies.administer',
        'export' => 'reports.export',
        default => '',
    };
    return $fallback !== '' && can($fallback, $user);
}

function process_mapping_require_capability(string $capability): void
{
    if (!process_mapping_can($capability)) {
        throw new RuntimeException('You do not have permission to '.$capability.' process maps.');
    }
}

function process_mapping_require_enterprise_context(): void
{
    if (current_company_id() !== 'enterprise' || !can_use_enterprise_view()) {
        throw new RuntimeException('Enterprise process templates can only be governed from Enterprise View.');
    }
}

function process_mapping_active_users(): array
{
    return array_values(array_filter(
        data_collection('users'),
        static fn(array $user): bool => ($user['status'] ?? '') === 'active'
    ));
}

function process_mapping_active_user(int $userId): ?array
{
    foreach (process_mapping_active_users() as $user) {
        if ((int)$user['id'] === $userId) return $user;
    }
    return null;
}

function process_mapping_assert_three_person_governance(int $preparedBy, int $reviewedBy, int $approvedBy): void
{
    if (count(array_unique([$preparedBy, $reviewedBy, $approvedBy])) !== 3) {
        throw new RuntimeException('Process preparation, review, and approval require three different active users.');
    }
    foreach ([$preparedBy, $reviewedBy, $approvedBy] as $userId) {
        if (!process_mapping_active_user($userId)) {
            throw new RuntimeException('Every process governance participant must be an active internal user.');
        }
    }
}

function process_mapping_metrics(): array
{
    $processes = process_mapping_processes();
    $instances = process_mapping_instances();
    $stepInstances = process_mapping_step_instances();
    $exceptions = process_mapping_exceptions();
    $active = array_values(array_filter($instances, static fn(array $row): bool => in_array($row['status'], ['in_progress','blocked','overdue'], true)));
    $completed = array_values(array_filter($instances, static fn(array $row): bool => $row['status'] === 'completed'));
    $elapsed = array_filter(array_map('intval', array_column($completed, 'cycle_seconds')));
    $average = $elapsed ? (int)round(array_sum($elapsed) / count($elapsed)) : 0;
    $overdue = count(array_filter($stepInstances, static fn(array $row): bool => $row['status'] === 'active'
        && !empty($row['due_at']) && strtotime((string)$row['due_at']) < time()));
    return [
        'processes'=>count($processes),
        'published'=>count(array_filter($processes, static fn(array $row): bool => $row['status'] === 'published')),
        'active_instances'=>count($active),
        'completed_instances'=>count($completed),
        'open_exceptions'=>count(array_filter($exceptions, static fn(array $row): bool => $row['status'] !== 'resolved')),
        'overdue_steps'=>$overdue,
        'average_cycle_seconds'=>$average,
        'touchless_percent'=>process_mapping_touchless_percent(),
    ];
}

function process_mapping_touchless_percent(): float
{
    $steps = process_mapping_steps();
    if (!$steps) return 0.0;
    $automated = count(array_filter($steps, static fn(array $step): bool => in_array($step['automation_mode'], ['adapter_gated','automatic'], true)));
    return round(($automated / count($steps)) * 100, 1);
}

function process_mapping_process_version(array $process): ?array
{
    $id = (int)($process['active_version_id'] ?? 0);
    if ($id > 0) return process_mapping_find_version($id);
    $versions = process_mapping_versions((int)$process['id']);
    return $versions[0] ?? null;
}

function process_mapping_map_model(int $processId, ?int $instanceId = null): array
{
    $process = process_mapping_find_process($processId) ?? process_mapping_processes()[0] ?? null;
    if (!$process) return [];
    $version = process_mapping_process_version($process);
    if (!$version) return [];
    $lanes = process_mapping_lanes((int)$version['id']);
    $steps = process_mapping_steps((int)$version['id']);
    $transitions = process_mapping_transitions((int)$version['id']);
    $controls = process_mapping_controls();
    $integrations = process_mapping_integrations();
    $instance = $instanceId ? process_mapping_find_instance($instanceId) : null;
    if (!$instance) {
        $matching = process_mapping_instances((int)$process['id']);
        $instance = $matching[0] ?? null;
    }
    $statuses = [];
    $stepInstances = [];
    $exceptions = [];
    if ($instance) {
        $stepInstances = process_mapping_step_instances((int)$instance['id']);
        foreach ($stepInstances as $stepInstance) $statuses[(int)$stepInstance['step_id']] = $stepInstance['status'];
        $exceptions = process_mapping_exceptions((int)$instance['id']);
    }
    foreach ($steps as &$step) {
        $step['runtime_status'] = $statuses[(int)$step['id']] ?? 'not_started';
        $step['controls'] = array_values(array_filter($controls, static fn(array $control): bool => (int)$control['step_id'] === (int)$step['id']));
        $step['integrations'] = array_values(array_filter($integrations, static fn(array $integration): bool => (int)$integration['step_id'] === (int)$step['id']));
    }
    unset($step);
    return compact('process','version','lanes','steps','transitions','instance','stepInstances','exceptions');
}

function process_mapping_template_entity_id(?string $entityCode): ?int
{
    if ($entityCode === null || $entityCode === '' || str_starts_with($entityCode, '@')) return null;
    foreach (entity_system_all_entities() as $entity) {
        if (strcasecmp((string)$entity['entity_code'], $entityCode) === 0) return (int)$entity['id'];
    }
    throw new RuntimeException('The process template references missing entity '.$entityCode.'.');
}
