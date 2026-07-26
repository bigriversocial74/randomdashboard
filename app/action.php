<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
require_once dirname(__DIR__) . '/includes/data/import_engine.php';
require_once dirname(__DIR__) . '/includes/data/actions.php';

if (request_method() !== 'POST') {
    redirect_to(app_url('dashboard.php'));
}
verify_csrf();

try {
    data_handle_action(post_string('action'));
} catch (Throwable $exception) {
    if (data_is_production()) {
        try {
            mysql_repo_add_audit('Platform', 'action_failed', 'request', null, null, [
                'action' => post_string('action'),
                'message' => $exception->getMessage(),
            ], current_company_id());
        } catch (Throwable) {
            // Preserve the original failure even if audit persistence is unavailable.
        }
    }
    flash('error', 'The action could not be completed: ' . $exception->getMessage());
    redirect_to(safe_return_to(post_string('return_to')));
}
