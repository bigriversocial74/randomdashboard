<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/app/bootstrap.php';
require_once dirname(__DIR__).'/includes/app/supplier_portal.php';
if(supplier_portal_current_account())redirect_to(root_url('supplier-portal/index.php'));
$error='';
if(request_method()==='POST'){
    verify_csrf();
    if(post_string('action')==='demo_login'&&supplier_portal_demo_login())redirect_to(root_url('supplier-portal/index.php'));
    if(supplier_portal_login(post_string('email'),post_string('password')))redirect_to(root_url('supplier-portal/index.php'));
    $error=supplier_portal_login_error();
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Supplier Portal sign in | Gruber</title><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="<?= h(root_url('supplier-portal/assets/portal.css?v=20260727-section22')) ?>"></head><body><main class="login-main"><section class="login-card"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"><span class="eyebrow">Restricted Supplier Portal</span><h1>Supplier sign in</h1><p>Use the supplier account created from your controlled invitation. Internal Gruber accounts do not work here.</p><?php if($error): ?><div class="flash flash-error" role="alert"><?= h($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="login"><label><span>Email</span><input type="email" name="email" required autocomplete="email" value="<?= h(post_string('email')) ?>"></label><label><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label><button class="button primary" type="submit">Sign in</button></form><?php if(query_string('demo')==='1'||demo_mode_active()): ?><div class="demo-note"><strong>Isolated supplier demo</strong><p>Uses seeded supplier-only records and does not access production data.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="demo_login"><button class="button secondary" type="submit">Open supplier demo</button></form><small>Demo credentials are not accepted in Production Data.</small></div><?php endif; ?></section></main></body></html>
