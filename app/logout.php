<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/app/bootstrap.php';
$wasDemo = demo_mode_active();
app_logout();
flash('success', $wasDemo ? 'Demo session ended.' : 'You have signed out.');
redirect_to($wasDemo ? root_url('demo.php') : app_url('login.php'));
