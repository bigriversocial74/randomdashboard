<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';

if (request_method() !== 'POST') {
    redirect_to(root_url('demo.php'));
}
verify_csrf();

if (!app_config()['app']['demo_enabled']) {
    flash('error', 'Demo Mode is disabled.');
    redirect_to(root_url('demo.php'));
}

$userId = post_int('user_id');
if (!demo_start_session($userId)) {
    flash('error', 'The selected demo account is unavailable.');
    redirect_to(root_url('demo.php'));
}
flash('success', 'Demo Mode started as ' . current_user()['name'] . '.');
$returnTo = post_string('return_to');
redirect_to($returnTo !== '' ? safe_return_to($returnTo, 'dashboard.php') : app_url('dashboard.php'));
