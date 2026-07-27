<?php
declare(strict_types=1);

function process_mapping_create_from_template(string $code, string $note, int $reviewerId, int $approverId): array
{
    process_mapping_require_capability('edit');
    process_mapping_require_enterprise_context();
    if (trim($note) === '') throw new RuntimeException('Template governance evidence is required.');
    $preview = process_mapping_template_preview($code);
    if (!$preview['valid']) throw new RuntimeException(implode(' ', $preview['errors']));
    $userId = (int)current_user()['id'];
    process_mapping_assert_three_person_governance($userId, $reviewerId, $approverId);

    $template = $preview['template'];
    $root = entity_system_find_by_code('GRUBER-ENTERPRISE');
    if (!$root) throw new RuntimeException('Apply the Section 24 enterprise entity foundation before creating process templates.');
    $process = process_mapping_save_process([
        'id'=>null,
        'process_code'=>'PROC-'.strtoupper(substr(hash('sha256', $code.'|'.microtime(true)), 0, 10)),
        'template_code'=>$code,
        'process_name'=>$template['name'].' Copy',
        'purpose'=>$template['purpose'],
        'category_code'=>$template['category'],
        'value_chain'=>$template['value_chain'],
        'owner_entity_id'=>(int)$root['id'],
        'is_template'=>0,
        'status'=>'draft',
        'active_version_id'=>null,
        'created_by'=>$userId,
    ]);
    $version = process_mapping_save_version([
        'id'=>null,'process_id'=>$process['id'],'version_number'=>'1.0','status'=>'draft',
        'effective_from'=>date('Y-m-d'),'effective_to'=>null,'prepared_by'=>$userId,
        'reviewed_by'=>$reviewerId,'approved_by'=>$approverId,
        'manifest_hash'=>hash('sha256', json_encode($template, JSON_UNESCAPED_SLASHES)),
        'evidence_note'=>mb_substr($note, 0, 5000),'published_at'=>null,'locked_at'=>null,
    ]);

    $laneMap = [];
    foreach ($template['lanes'] as $index => $lane) {
        $entityId = process_mapping_template_entity_id($lane['entity_code'] ?? null);
        $saved = process_mapping_save_lane([
            'id'=>null,'version_id'=>$version['id'],'lane_code'=>$lane['code'],'lane_name'=>$lane['name'],
            'participant_type'=>$lane['participant_type'],'entity_id'=>$entityId,'role_code'=>$lane['role_code'],
            'sort_order'=>$index + 1,'color_token'=>'lane-'.(($index % 6) + 1),'status'=>'active',
        ]);
        $laneMap[$lane['code']] = (int)$saved['id'];
    }

    $stepMap = [];
    foreach ($template['steps'] as $index => $step) {
        $saved = process_mapping_save_step([
            'id'=>null,'version_id'=>$version['id'],'lane_id'=>$laneMap[$step['lane']],
            'step_code'=>$step['code'],'step_name'=>$step['name'],'step_type'=>$step['type'],
            'description'=>$step['name'].' within '.$template['name'].'.','position_x'=>$step['x'],'position_y'=>$step['y'],
            'node_width'=>150,'node_height'=>72,'required_permission'=>$step['permission'] ?? 'platform.view',
            'sla_minutes'=>$step['sla'] ?? 0,'evidence_required'=>!empty($step['evidence']) ? 1 : 0,
            'canonical_record_type'=>$step['record'] ?? 'business_process','integration_event'=>$step['event'] ?? '',
            'automation_mode'=>$step['type'] === 'integration' ? 'adapter_gated' : 'human_supervised',
            'sort_order'=>$index + 1,'status'=>'active',
        ]);
        $stepMap[$step['code']] = (int)$saved['id'];
        if (!empty($step['evidence']) || in_array($step['type'], ['approval','control'], true)) {
            process_mapping_save_control([
                'id'=>null,'step_id'=>$saved['id'],'control_type'=>$step['type'] === 'approval' ? 'approval' : 'evidence',
                'rule_code'=>'CTRL-'.$saved['step_code'],'control_description'=>'Require governed evidence before transition.',
                'severity'=>$step['type'] === 'approval' ? 'high' : 'medium',
                'separation_role'=>$step['type'] === 'approval' ? 'preparer' : '',
                'evidence_type'=>'record_reference','status'=>'active',
            ]);
        }
        if (!empty($step['event'])) {
            process_mapping_save_integration([
                'id'=>null,'step_id'=>$saved['id'],'connection_id'=>null,'direction'=>'outbound',
                'event_type'=>$step['event'],'record_type'=>$saved['canonical_record_type'],'sync_mode'=>'event',
                'required_acknowledgment'=>1,'timeout_minutes'=>120,'status'=>'active',
            ]);
        }
    }
    foreach ($template['transitions'] as $index => $transition) {
        process_mapping_save_transition([
            'id'=>null,'version_id'=>$version['id'],'from_step_id'=>$stepMap[$transition['from']],
            'to_step_id'=>$stepMap[$transition['to']],'transition_code'=>'T'.($index + 1),
            'condition_label'=>$transition['label'],'is_default'=>!empty($transition['default']) ? 1 : 0,
            'is_exception_path'=>!empty($transition['exception']) ? 1 : 0,'sort_order'=>$index + 1,'status'=>'active',
        ]);
    }
    foreach (entity_system_bindings() as $binding) {
        process_mapping_save_assignment([
            'id'=>null,'process_id'=>$process['id'],'entity_id'=>$binding['entity_id'],'assignment_type'=>'applicable',
            'module_code'=>$template['category'],'inheritance_mode'=>'template','status'=>'active',
            'effective_from'=>date('Y-m-d'),'effective_to'=>null,
        ]);
    }
    process_mapping_add_event((int)$process['id'], (int)$version['id'], null, null, 'process_created', null, 'draft', 'medium', $note);
    return process_mapping_find_process((int)$process['id']) ?? $process;
}

function process_mapping_publish_version(array $version, string $note): array
{
    process_mapping_require_capability('publish');
    process_mapping_require_enterprise_context();
    if (($version['status'] ?? '') === 'published') return $version;
    if (trim($note) === '') throw new RuntimeException('Publication evidence is required.');
    $prepared = (int)$version['prepared_by'];
    $reviewed = (int)$version['reviewed_by'];
    $approved = (int)$version['approved_by'];
    process_mapping_assert_three_person_governance($prepared, $reviewed, $approved);
    $steps = process_mapping_steps((int)$version['id']);
    $types = array_column($steps, 'step_type');
    if (!in_array('start', $types, true) || !in_array('end', $types, true)) {
        throw new RuntimeException('A published process needs start and end steps.');
    }
    if (count(process_mapping_transitions((int)$version['id'])) < 1) {
        throw new RuntimeException('A published process needs transitions.');
    }
    foreach (process_mapping_lanes((int)$version['id']) as $lane) {
        if (($lane['participant_type'] ?? '') === 'entity' && !empty($lane['entity_id'])) {
            entity_system_assert_entity_access((int)$lane['entity_id'], 'process_mapping', true);
        }
    }
    $version['status'] = 'published';
    $version['published_at'] = date('Y-m-d H:i:s');
    $version['locked_at'] = $version['published_at'];
    $version['evidence_note'] = mb_substr($note, 0, 5000);
    $version = process_mapping_save_version($version);
    $process = process_mapping_find_process((int)$version['process_id']);
    if ($process) {
        $process['status'] = 'published';
        $process['active_version_id'] = $version['id'];
        process_mapping_save_process($process);
    }
    process_mapping_add_event((int)$version['process_id'], (int)$version['id'], null, null, 'version_published', 'draft', 'published', 'high', $note);
    return $version;
}

function process_mapping_save_layout(int $versionId, array $positions, string $note): void
{
    process_mapping_require_capability('edit');
    $version = process_mapping_find_version($versionId);
    if (!$version) throw new RuntimeException('Process version not found.');
    process_mapping_assert_version_mutable($versionId);
    if (trim($note) === '') throw new RuntimeException('Layout evidence is required.');
    foreach (process_mapping_steps($versionId) as $step) {
        $position = $positions[(string)$step['id']] ?? $positions[(int)$step['id']] ?? null;
        if (!$position) continue;
        $step['position_x'] = max(190, min(1800, (int)($position['x'] ?? $step['position_x'])));
        $step['position_y'] = max(40, min(1200, (int)($position['y'] ?? $step['position_y'])));
        process_mapping_save_step($step);
    }
    process_mapping_add_event((int)$version['process_id'], $versionId, null, null, 'layout_saved', null, 'draft', 'low', $note);
}
