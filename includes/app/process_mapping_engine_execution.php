<?php
declare(strict_types=1);

function process_mapping_assert_step_execution(array $stepInstance, array $step, array $instance): void
{
    if (!process_mapping_find_instance((int)$instance['id'])) {
        throw new RuntimeException('The process instance is outside the active entity scope.');
    }
    if (!empty($stepInstance['assigned_entity_id'])) {
        entity_system_assert_entity_access((int)$stepInstance['assigned_entity_id'], 'process_mapping', true);
    }
    $required = trim((string)($step['required_permission'] ?? ''));
    if ($required !== '' && !can($required)) {
        throw new RuntimeException('The active process step requires '.$required.'.');
    }
}

function process_mapping_advance_step(int $stepInstanceId, string $decision, string $note): array
{
    process_mapping_require_capability('execute');
    $stepInstance = process_mapping_find_step_instance($stepInstanceId);
    if (!$stepInstance) throw new RuntimeException('Process step instance not found.');
    if ($stepInstance['status'] !== 'active') throw new RuntimeException('Only the active process step can advance.');
    if ($decision !== 'complete') throw new RuntimeException('Open a governed exception instead of bypassing the active step.');
    $step = process_mapping_find_step((int)$stepInstance['step_id']);
    $instance = process_mapping_find_instance((int)$stepInstance['instance_id']);
    if (!$step || !$instance) throw new RuntimeException('Process context is incomplete.');
    process_mapping_assert_step_execution($stepInstance, $step, $instance);
    if (!empty($step['evidence_required']) && trim($note) === '') {
        throw new RuntimeException('This step requires completion evidence.');
    }
    $from = $stepInstance['status'];
    $stepInstance['status'] = 'completed';
    $stepInstance['completed_at'] = date('Y-m-d H:i:s');
    $stepInstance['elapsed_seconds'] = max(0, time() - strtotime((string)($stepInstance['started_at'] ?? date('Y-m-d H:i:s'))));
    $stepInstance['evidence_note'] = mb_substr($note, 0, 5000);
    process_mapping_save_step_instance($stepInstance);

    $transitions = array_values(array_filter(
        process_mapping_transitions((int)$instance['version_id']),
        static fn(array $transition): bool => (int)$transition['from_step_id'] === (int)$step['id'] && !empty($transition['is_default'])
    ));
    $next = $transitions[0] ?? null;
    if (!$next) {
        $instance['status'] = 'completed';
        $instance['completed_at'] = date('Y-m-d H:i:s');
        $instance['cycle_seconds'] = max(0, time() - strtotime((string)$instance['started_at']));
        $instance['current_step_id'] = null;
    } else {
        $nextStepInstance = null;
        foreach (process_mapping_step_instances((int)$instance['id']) as $candidate) {
            if ((int)$candidate['step_id'] === (int)$next['to_step_id']) { $nextStepInstance = $candidate; break; }
        }
        if (!$nextStepInstance) throw new RuntimeException('The next governed step instance is missing.');
        $nextStep = process_mapping_find_step((int)$nextStepInstance['step_id']);
        if (!$nextStep) throw new RuntimeException('The next process step is missing.');
        $nextStepInstance['status'] = 'active';
        $nextStepInstance['started_at'] = date('Y-m-d H:i:s');
        $nextStepInstance['due_at'] = (int)$nextStep['sla_minutes'] > 0
            ? date('Y-m-d H:i:s', strtotime('+'.(int)$nextStep['sla_minutes'].' minutes')) : null;
        process_mapping_save_step_instance($nextStepInstance);
        $instance['current_step_id'] = $nextStepInstance['step_id'];
        if ($nextStep['step_type'] === 'end') {
            $nextStepInstance['status'] = 'completed';
            $nextStepInstance['completed_at'] = date('Y-m-d H:i:s');
            $nextStepInstance['elapsed_seconds'] = 0;
            process_mapping_save_step_instance($nextStepInstance);
            $instance['status'] = 'completed';
            $instance['completed_at'] = date('Y-m-d H:i:s');
            $instance['cycle_seconds'] = max(0, time() - strtotime((string)$instance['started_at']));
            $instance['current_step_id'] = null;
        }
    }
    process_mapping_save_instance($instance);
    process_mapping_add_event((int)$instance['process_id'], (int)$instance['version_id'], (int)$instance['id'], (int)$step['id'], 'step_completed', $from, $instance['status'], 'medium', $note);
    return $instance;
}

function process_mapping_user_can_review_entity(array $user, int $entityId): bool
{
    $entity = entity_system_find(entity_system_all_entities(), $entityId);
    if (!$entity || ($user['status'] ?? '') !== 'active') return false;
    if (array_intersect(current_role_codes($user), ['system_administrator','executive'])) return true;
    $companyId = (int)($entity['company_id'] ?? 0);
    return $companyId > 0 && in_array($companyId, array_map('intval', $user['company_ids'] ?? []), true);
}

function process_mapping_open_exception(int $stepInstanceId, string $type, string $severity, string $description, int $reviewerId): array
{
    process_mapping_require_capability('execute');
    $stepInstance = process_mapping_find_step_instance($stepInstanceId);
    if (!$stepInstance || $stepInstance['status'] !== 'active') throw new RuntimeException('An active step instance is required.');
    $step = process_mapping_find_step((int)$stepInstance['step_id']);
    $instance = process_mapping_find_instance((int)$stepInstance['instance_id']);
    if (!$step || !$instance) throw new RuntimeException('Process context is incomplete.');
    process_mapping_assert_step_execution($stepInstance, $step, $instance);
    $ownerId = (int)current_user()['id'];
    $reviewer = process_mapping_active_user($reviewerId);
    if (!$reviewer || $ownerId === $reviewerId) throw new RuntimeException('Exception owner and reviewer must be different active users.');
    if (!process_mapping_user_can_review_entity($reviewer, (int)$instance['entity_id'])) {
        throw new RuntimeException('The reviewer is outside the process entity scope.');
    }
    if (trim($type) === '' || trim($description) === '') throw new RuntimeException('Exception type and evidence are required.');
    if (!in_array($severity, ['low','medium','high','critical'], true)) $severity = 'medium';
    $stepInstance['status'] = 'exception';
    $stepInstance['evidence_note'] = mb_substr($description, 0, 5000);
    process_mapping_save_step_instance($stepInstance);
    $exception = process_mapping_save_exception([
        'id'=>null,'instance_id'=>$stepInstance['instance_id'],'step_instance_id'=>$stepInstance['id'],
        'exception_type'=>$type,'severity'=>$severity,'status'=>'open','owner_id'=>$ownerId,'reviewer_id'=>$reviewerId,
        'exception_description'=>mb_substr($description, 0, 5000),'resolution_note'=>'',
        'opened_at'=>date('Y-m-d H:i:s'),'resolved_at'=>null,
    ]);
    $instance['status'] = 'blocked';
    process_mapping_save_instance($instance);
    process_mapping_add_event((int)$instance['process_id'], (int)$instance['version_id'], (int)$instance['id'], (int)$stepInstance['step_id'], 'exception_opened', 'in_progress', 'blocked', $severity, $description);
    return $exception;
}

function process_mapping_resolve_exception(int $exceptionId, string $note): array
{
    process_mapping_require_capability('execute');
    $exception = process_mapping_find(process_mapping_exceptions(), $exceptionId);
    if (!$exception) throw new RuntimeException('Exception not found.');
    if ($exception['status'] === 'resolved') return $exception;
    if ((int)current_user()['id'] !== (int)$exception['reviewer_id']) {
        throw new RuntimeException('Only the assigned independent reviewer can resolve this exception.');
    }
    if (trim($note) === '') throw new RuntimeException('Resolution evidence is required.');
    $exception['status'] = 'resolved';
    $exception['resolution_note'] = mb_substr($note, 0, 5000);
    $exception['resolved_at'] = date('Y-m-d H:i:s');
    $exception = process_mapping_save_exception($exception);
    $instance = process_mapping_find_instance((int)$exception['instance_id']);
    if ($instance) {
        $otherOpen = array_filter(
            process_mapping_exceptions((int)$instance['id']),
            static fn(array $row): bool => (int)$row['id'] !== (int)$exception['id'] && $row['status'] !== 'resolved'
        );
        if (!$otherOpen) {
            $stepInstance = process_mapping_find_step_instance((int)$exception['step_instance_id']);
            if ($stepInstance && $stepInstance['status'] === 'exception') {
                $stepInstance['status'] = 'active';
                $stepInstance['started_at'] = date('Y-m-d H:i:s');
                process_mapping_save_step_instance($stepInstance);
                $instance['current_step_id'] = $stepInstance['step_id'];
            }
            $instance['status'] = 'in_progress';
            process_mapping_save_instance($instance);
        }
        process_mapping_add_event((int)$instance['process_id'], (int)$instance['version_id'], (int)$instance['id'], null, 'exception_resolved', 'blocked', $instance['status'], 'medium', $note);
    }
    return $exception;
}
