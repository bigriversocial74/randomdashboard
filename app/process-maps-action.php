<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/process_mapping.php';
require_app_user();
if (request_method() !== 'POST') redirect_to(app_url('process-maps.php'));
verify_csrf();

function process_action_note(string $key = 'evidence_note'): string
{
    $note = trim(post_string($key));
    if ($note === '') throw new RuntimeException('Governance evidence is required.');
    return mb_substr($note, 0, 5000);
}

function process_action_redirect(string $tab, array $params = []): never
{
    redirect_to(app_url('process-maps.php?'.http_build_query(array_replace(['tab'=>$tab], $params))));
}

try {
    $action = post_string('action');
    if ($action === 'create_from_template') {
        process_mapping_require_capability('edit');
        $process = process_mapping_create_from_template(
            post_string('template_code'),
            process_action_note(),
            post_int('reviewer_id'),
            post_int('approver_id')
        );
        flash('success', 'Governed process copy created.');
        process_action_redirect('designer', ['process_id'=>$process['id']]);
    }
    if ($action === 'publish_version') {
        process_mapping_require_capability('publish');
        $version = process_mapping_find_version(post_int('version_id'));
        if (!$version) throw new RuntimeException('Process version not found.');
        process_mapping_publish_version($version, process_action_note());
        flash('success', 'Process version published and permanently locked.');
        process_action_redirect('live', ['process_id'=>$version['process_id']]);
    }
    if ($action === 'save_layout') {
        process_mapping_require_capability('edit');
        $positions = json_decode(post_string('positions_json', '{}'), true);
        if (!is_array($positions)) throw new RuntimeException('Invalid process layout payload.');
        process_mapping_save_layout(post_int('version_id'), $positions, process_action_note());
        flash('success', 'Draft process layout saved.');
        $version = process_mapping_find_version(post_int('version_id'));
        process_action_redirect('designer', ['process_id'=>$version['process_id'] ?? 0]);
    }
    if ($action === 'start_instance') {
        process_mapping_require_capability('execute');
        $instance = process_mapping_start_instance(
            post_int('process_id'),
            post_int('entity_id'),
            post_string('record_type'),
            post_int('record_id'),
            post_string('instance_title'),
            process_action_note()
        );
        flash('success', 'Live process instance started.');
        process_action_redirect('live', ['process_id'=>$instance['process_id'], 'instance_id'=>$instance['id']]);
    }
    if ($action === 'advance_step') {
        process_mapping_require_capability('execute');
        $instance = process_mapping_advance_step(post_int('step_instance_id'), 'complete', process_action_note());
        flash('success', 'Process step completed with evidence.');
        process_action_redirect('live', ['process_id'=>$instance['process_id'], 'instance_id'=>$instance['id']]);
    }
    if ($action === 'open_exception') {
        process_mapping_require_capability('execute');
        $exception = process_mapping_open_exception(
            post_int('step_instance_id'),
            post_string('exception_type'),
            post_string('severity', 'medium'),
            process_action_note('exception_description'),
            post_int('reviewer_id')
        );
        flash('success', 'Process exception opened for independent review.');
        process_action_redirect('governance', ['instance_id'=>$exception['instance_id']]);
    }
    if ($action === 'resolve_exception') {
        process_mapping_require_capability('execute');
        $exception = process_mapping_resolve_exception(post_int('exception_id'), process_action_note('resolution_note'));
        flash('success', 'Process exception resolved.');
        process_action_redirect('governance', ['instance_id'=>$exception['instance_id']]);
    }
    throw new RuntimeException('Unsupported process mapping action.');
} catch (Throwable $exception) {
    flash('error', $exception->getMessage());
    process_action_redirect(post_string('return_tab', 'live'));
}
