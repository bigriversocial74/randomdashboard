<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$prod=file_get_contents($root.'/includes/data/actions.php');
$demo=file_get_contents($root.'/includes/demo/actions.php');
$approvals=file_get_contents($root.'/app/approvals.php');
$notifications=file_get_contents($root.'/app/notifications.php');
foreach ([$prod,$demo] as $source) {
    foreach (["case 'advance_savings_stage'", "case 'workflow_transition'", 'workflow_approvals', 'accounting_validation'] as $needle) {
        if (!str_contains(strtolower($source), strtolower($needle))) { fwrite(STDERR,"Missing workflow gate: {$needle}\n"); exit(1); }
    }
}
foreach (['assignment','overdue','source record'] as $needle) {
    if (!str_contains(strtolower($approvals), $needle)) { fwrite(STDERR,"Approval workspace missing {$needle}.\n"); exit(1); }
}
foreach (['severity','unread','critical'] as $needle) {
    if (!str_contains(strtolower($notifications), $needle)) { fwrite(STDERR,"Notification workspace missing {$needle}.\n"); exit(1); }
}
fwrite(STDOUT,"Section 7 workflow gates passed.\n");
