<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$prod=file_get_contents($root.'/includes/data/actions.php');
$demo=file_get_contents($root.'/includes/demo/actions.php');
$permissions=file_get_contents($root.'/includes/admin/permissions.php');
$adminIndex=file_get_contents($root.'/app/admin/index.php');
$environment=file_get_contents($root.'/app/admin/environment.php');
$settings=file_get_contents($root.'/app/admin/settings.php');
foreach ([$prod,$demo] as $source) {
    foreach (['admin_normalize_permissions','admin_normalize_modules','That role code is already in use','That company code is already in use','Allowed file types may contain only csv and xlsx','No password was emailed'] as $needle) {
        if (!str_contains($source,$needle)) { fwrite(STDERR,"Administration validation missing: {$needle}\n"); exit(1); }
    }
}
foreach (['function admin_allowed_company_modules','function admin_normalize_permissions','function admin_email_valid'] as $needle) {
    if (!str_contains($permissions,$needle)) { fwrite(STDERR,"Shared governance helper missing: {$needle}\n"); exit(1); }
}
if (str_contains($adminIndex,"\$env['installer']") || str_contains($environment,'Open Installer') || str_contains($environment,"'installer'=>'Installer status'")) {
    fwrite(STDERR,"Administration still references the retired installer workflow.\n"); exit(1);
}
if (!str_contains($environment,'Manual setup guide') || !str_contains($settings,'no password is emailed')) {
    fwrite(STDERR,"Manual-deployment or mail-delivery guidance is incomplete.\n"); exit(1);
}
fwrite(STDOUT,"Section 9 administration gates passed.\n");
