<?php
declare(strict_types=1);

function process_mapping_save_row(string $table, string $key, callable $seed, array $record, array $fields): array
{
    process_mapping_require_tables();
    $record['created_at'] = $record['created_at'] ?? date('Y-m-d H:i:s');
    $record['updated_at'] = date('Y-m-d H:i:s');
    if (data_is_demo()) return process_mapping_demo_save($key, $record, $seed);

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
            'UPDATE '.$table.' SET '.implode(',', array_map(static fn(string $field): string => $field.'=?', $fields))
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

function process_mapping_save_process(array $record): array
{
    return process_mapping_save_row('business_processes','business_processes','process_mapping_demo_processes',$record,[
        'process_code','template_code','process_name','purpose','category_code','value_chain','owner_entity_id','is_template','status','active_version_id','created_by',
    ]);
}

function process_mapping_save_version(array $record): array
{
    $existing = !empty($record['id']) ? process_mapping_raw_find_version((int)$record['id']) : null;
    if ($existing && (($existing['status'] ?? '') === 'published' || !empty($existing['locked_at']))) {
        $protected = ['process_id','version_number','effective_from','effective_to','prepared_by','reviewed_by','approved_by','manifest_hash','published_at','locked_at'];
        foreach ($protected as $field) {
            if ((string)($existing[$field] ?? '') !== (string)($record[$field] ?? '')) {
                throw new RuntimeException('Published process-version governance fields are immutable.');
            }
        }
    }
    return process_mapping_save_row('business_process_versions','business_process_versions','process_mapping_demo_versions',$record,[
        'process_id','version_number','status','effective_from','effective_to','prepared_by','reviewed_by','approved_by','manifest_hash','evidence_note','published_at','locked_at',
    ]);
}

function process_mapping_save_lane(array $record): array
{
    process_mapping_assert_version_mutable((int)$record['version_id']);
    return process_mapping_save_row('business_process_lanes','business_process_lanes','process_mapping_demo_lanes',$record,[
        'version_id','lane_code','lane_name','participant_type','entity_id','role_code','sort_order','color_token','status',
    ]);
}

function process_mapping_save_step(array $record): array
{
    process_mapping_assert_version_mutable((int)$record['version_id']);
    return process_mapping_save_row('business_process_steps','business_process_steps','process_mapping_demo_steps',$record,[
        'version_id','lane_id','step_code','step_name','step_type','description','position_x','position_y','node_width','node_height','required_permission',
        'sla_minutes','evidence_required','canonical_record_type','integration_event','automation_mode','sort_order','status',
    ]);
}

function process_mapping_save_transition(array $record): array
{
    process_mapping_assert_version_mutable((int)$record['version_id']);
    return process_mapping_save_row('business_process_transitions','business_process_transitions','process_mapping_demo_transitions',$record,[
        'version_id','from_step_id','to_step_id','transition_code','condition_label','is_default','is_exception_path','sort_order','status',
    ]);
}

function process_mapping_step_version_id(int $stepId): int
{
    $step = process_mapping_raw_find_step($stepId);
    if (!$step) throw new RuntimeException('Process step not found.');
    return (int)$step['version_id'];
}

function process_mapping_save_control(array $record): array
{
    process_mapping_assert_version_mutable(process_mapping_step_version_id((int)$record['step_id']));
    return process_mapping_save_row('business_process_controls','business_process_controls','process_mapping_demo_controls',$record,[
        'step_id','control_type','rule_code','control_description','severity','separation_role','evidence_type','status',
    ]);
}

function process_mapping_save_integration(array $record): array
{
    process_mapping_assert_version_mutable(process_mapping_step_version_id((int)$record['step_id']));
    return process_mapping_save_row('business_process_integrations','business_process_integrations','process_mapping_demo_integrations',$record,[
        'step_id','connection_id','direction','event_type','record_type','sync_mode','required_acknowledgment','timeout_minutes','status',
    ]);
}

function process_mapping_save_assignment(array $record): array
{
    entity_system_assert_entity_access((int)$record['entity_id'], 'process_mapping', true);
    return process_mapping_save_row('business_process_assignments','business_process_assignments','process_mapping_demo_assignments',$record,[
        'process_id','entity_id','assignment_type','module_code','inheritance_mode','status','effective_from','effective_to',
    ]);
}

function process_mapping_save_instance(array $record): array
{
    return process_mapping_save_row('business_process_instances','business_process_instances','process_mapping_demo_instances',$record,[
        'process_id','version_id','instance_number','entity_id','canonical_record_type','canonical_record_id','instance_title','status','current_step_id',
        'started_by','started_at','due_at','completed_at','cycle_seconds','process_data_json',
    ]);
}

function process_mapping_save_step_instance(array $record): array
{
    return process_mapping_save_row('business_process_step_instances','business_process_step_instances','process_mapping_demo_step_instances',$record,[
        'instance_id','step_id','assigned_entity_id','assigned_user_id','status','started_at','due_at','completed_at','elapsed_seconds','evidence_note','output_json',
    ]);
}

function process_mapping_save_exception(array $record): array
{
    return process_mapping_save_row('business_process_exceptions','business_process_exceptions','process_mapping_demo_exceptions',$record,[
        'instance_id','step_instance_id','exception_type','severity','status','owner_id','reviewer_id','exception_description','resolution_note','opened_at','resolved_at',
    ]);
}

function process_mapping_add_event(?int $processId, ?int $versionId, ?int $instanceId, ?int $stepId, string $type, ?string $from, ?string $to, string $severity, string $note): array
{
    process_mapping_require_tables();
    if ($processId !== null && !process_mapping_find_process($processId)) {
        throw new RuntimeException('The process event is outside the active entity scope.');
    }
    $record = [
        'id'=>null,'process_id'=>$processId,'version_id'=>$versionId,'instance_id'=>$instanceId,'step_id'=>$stepId,
        'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'severity'=>$severity,'evidence_note'=>$note,
        'created_by'=>(int)current_user()['id'],'created_at'=>date('Y-m-d H:i:s'),
    ];
    if (data_is_demo()) return process_mapping_demo_save('business_process_events', $record, 'process_mapping_demo_events');
    $pdo = production_database_connection();
    $pdo->prepare(
        'INSERT INTO business_process_events(process_id,version_id,instance_id,step_id,event_type,from_status,to_status,severity,evidence_note,created_by)'
        .' VALUES(?,?,?,?,?,?,?,?,?,?)'
    )->execute([$processId,$versionId,$instanceId,$stepId,$type,$from,$to,$severity,$note,$record['created_by']]);
    $record['id'] = (int)$pdo->lastInsertId();
    return $record;
}
