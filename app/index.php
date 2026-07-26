<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';

if (current_user()) {
    redirect_to(app_url('dashboard.php'));
}
if (!database_available()) {
    render_environment_gate();
    exit;
}
redirect_to(app_url('login.php'));
