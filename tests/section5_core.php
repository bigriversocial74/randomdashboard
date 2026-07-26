<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
};
$read = static fn(string $path): string => (string) file_get_contents($root . '/' . $path);
$inventory = $read('app/inventory.php');
$scorecards = $read('app/scorecards.php');
$actions = $read('includes/data/actions.php');
$demoActions = $read('includes/demo/actions.php');
$assert(str_contains($inventory, 'gruber_agent_build_transfer_candidates'), 'Inventory transfer recommendations are not evidence-driven.');
$assert(str_contains($inventory, 'paginate_records'), 'Inventory pagination is missing.');
$assert(str_contains($scorecards, 'name="performance"') && str_contains($scorecards, 'name="status"'), 'Scorecard filters are incomplete.');
foreach (['app/suppliers.php','app/items.php','app/purchase-orders.php','app/inventory.php'] as $path) {
    $content = $read($path);
    $assert(str_contains($content, '<caption'), "{$path} is missing an accessible table caption.");
    $assert(str_contains($content, 'data-sort'), "{$path} is missing sortable table headers.");
}
$assert(str_contains($scorecards, 'role="progressbar"'), 'Scorecard metrics lack accessible progress semantics.');
$assert(str_contains($scorecards, 'render_empty_state'), 'Scorecard empty state is missing.');
$assert(str_contains($actions, 'data_require_company_scope'), 'Production action company-scope enforcement is missing.');
$assert(str_contains($actions, 'data_require_supplier_scope'), 'Production supplier-scope enforcement is missing.');
$assert(str_contains($actions, "DateTimeImmutable::createFromFormat('!Y-m-d'"), 'Production purchase-order date validation is missing.');
$assert(str_contains($demoActions, 'demo_require_company_scope'), 'Demo action company-scope enforcement is missing.');
$assert(str_contains($demoActions, 'demo_require_supplier_scope'), 'Demo supplier-scope enforcement is missing.');
$assert(str_contains(strtolower($demoActions), 'required and expected dates cannot be earlier'), 'Demo purchase-order chronology validation is missing.');
fwrite(STDOUT, "Section 5 core-workspace quality gates passed.\n");
