<?php
declare(strict_types=1);

function supplier_portal_public_tabs(): array
{
    return ['dashboard'=>'Dashboard','orders'=>'Purchase orders','shipments'=>'Shipments','invoices'=>'Invoices','documents'=>'Documents','sourcing'=>'Sourcing','quality'=>'Quality','messages'=>'Messages'];
}

function supplier_portal_render_start(string $title,string $active,array $account): void
{
    $supplier=data_find('suppliers',(int)$account['supplier_id']);$flashes=pull_flashes();
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($title) ?> | Supplier Portal</title><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="<?= h(root_url('supplier-portal/assets/portal.css?v=20260727-section22')) ?>"></head><body><div class="portal-shell"><header class="portal-header"><a class="portal-brand" href="<?= h(root_url('supplier-portal/index.php')) ?>"><img src="<?= h(app_url('assets/gruber-main.png')) ?>" alt="Gruber"><span>Restricted Supplier Portal</span></a><nav class="portal-nav" aria-label="Supplier portal navigation"><?php foreach(supplier_portal_public_tabs() as $key=>$label): ?><a class="<?= $active===$key?'active':'' ?>" href="<?= h(root_url('supplier-portal/index.php?tab='.$key)) ?>"><?= h($label) ?></a><?php endforeach; ?></nav><div class="portal-user"><div><strong><?= h($account['name']) ?></strong><small><?= h($supplier['name']??'Supplier') ?></small></div><a href="<?= h(root_url('supplier-portal/logout.php')) ?>">Sign out</a></div></header><main class="portal-main"><?php foreach($flashes as $flash): ?><div class="flash flash-<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div><?php endforeach; ?><?php
}

function supplier_portal_render_end(): void
{
    ?></main><footer class="portal-footer">Restricted supplier collaboration · Activity is logged and subject to internal review.</footer></div></body></html><?php
}
