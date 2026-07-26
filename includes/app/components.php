<?php
declare(strict_types=1);

function badge(string $status, ?string $label = null): string
{
    return '<span class="status-badge ' . h(status_class($status)) . '">' . h($label ?? status_label($status)) . '</span>';
}

function sample_badge(): string
{
    return data_is_demo() ? '<span class="sample-badge">SAMPLE</span>' : '<span class="production-badge">LIVE</span>';
}

function workflow_actions(string $collection, int $id, string $current, string $area, string $returnTo): string
{
    $forms = [];
    if (can($area . '.submit') && in_array($current, ['draft','changes_requested','not_started','in_progress'], true)) {
        $forms[] = workflow_form($collection, $id, 'submitted', 'Submit', 'secondary', $returnTo);
    }
    if (can($area . '.review') && in_array($current, ['submitted','pending'], true)) {
        $forms[] = workflow_form($collection, $id, 'changes_requested', 'Request changes', 'ghost', $returnTo);
    }
    if (can($area . '.approve')) {
        if ($collection === 'workflow_approvals' && in_array($current, ['pending','in_review'], true)) {
            $forms[] = workflow_form($collection, $id, 'approved', 'Approve', 'primary', $returnTo);
        } elseif ($collection !== 'workflow_approvals' && in_array($current, ['submitted','in_review'], true)) {
            $forms[] = workflow_form($collection, $id, 'validated', 'Validate', 'secondary', $returnTo);
        } elseif ($collection !== 'workflow_approvals' && $current === 'validated') {
            $forms[] = workflow_form($collection, $id, 'approved', 'Approve', 'primary', $returnTo);
        }
    }
    return '<div class="row-actions">' . implode('', $forms) . '</div>';
}

function workflow_form(string $collection, int $id, string $transition, string $label, string $class, string $returnTo): string
{
    return '<form action="' . h(app_url('action.php')) . '" method="post">'
        . csrf_field()
        . '<input type="hidden" name="action" value="workflow_transition">'
        . '<input type="hidden" name="collection" value="' . h($collection) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="transition" value="' . h($transition) . '">'
        . '<input type="hidden" name="return_to" value="' . h($returnTo) . '">'
        . '<button class="mini-button ' . h($class) . '" type="submit">' . h($label) . '</button></form>';
}

function company_options(int|string|null $selected = null, bool $includeEnterprise = false): string
{
    $html = '';
    if ($includeEnterprise) {
        $html .= '<option value="enterprise"' . ($selected === 'enterprise' ? ' selected' : '') . '>Enterprise View</option>';
    }
    foreach (data_permitted_companies() as $company) {
        $isSelected = (string) $selected === (string) $company['id'];
        $html .= '<option value="' . (int) $company['id'] . '"' . ($isSelected ? ' selected' : '') . '>' . h($company['name']) . '</option>';
    }
    return $html;
}

function user_options(int|string|null $selected = null, bool $activeOnly = true): string
{
    $html = '<option value="">Unassigned</option>';
    foreach (data_admin_visible_users() as $user) {
        if ($activeOnly && $user['status'] !== 'active') {
            continue;
        }
        $isSelected = (string) $selected === (string) $user['id'];
        $html .= '<option value="' . (int) $user['id'] . '"' . ($isSelected ? ' selected' : '') . '>' . h($user['name']) . '</option>';
    }
    return $html;
}

function supplier_options(int|string|null $selected = null): string
{
    $html = '';
    foreach (data_visible_collection('suppliers') as $supplier) {
        $isSelected = (string) $selected === (string) $supplier['id'];
        $html .= '<option value="' . (int) $supplier['id'] . '"' . ($isSelected ? ' selected' : '') . '>' . h($supplier['name']) . '</option>';
    }
    return $html;
}

function category_options(int|string|null $selected = null): string
{
    $html = '';
    foreach (data_collection('categories') as $category) {
        $isSelected = (string) $selected === (string) $category['id'];
        $html .= '<option value="' . (int) $category['id'] . '"' . ($isSelected ? ' selected' : '') . '>' . h($category['name']) . '</option>';
    }
    return $html;
}

function company_checkbox_grid(array $selected = [], string $name = 'company_ids[]'): string
{
    $html = '<div class="checkbox-grid">';
    foreach (data_permitted_companies() as $company) {
        $checked = in_array((int) $company['id'], array_map('intval', $selected), true);
        $html .= '<label class="check-card"><input type="checkbox" name="' . h($name) . '" value="' . (int) $company['id'] . '"' . ($checked ? ' checked' : '') . '><span><strong>' . h($company['code']) . '</strong><small>' . h($company['name']) . '</small></span></label>';
    }
    return $html . '</div>';
}

function role_checkbox_grid(array $selected = []): string
{
    $html = '<div class="checkbox-grid">';
    $assignable = data_assignable_role_codes();
    foreach (data_collection('roles') as $role) {
        if ($role['status'] !== 'active' || !in_array($role['code'], $assignable, true)) {
            continue;
        }
        $checked = in_array($role['code'], $selected, true);
        $html .= '<label class="check-card"><input type="checkbox" name="role_codes[]" value="' . h($role['code']) . '"' . ($checked ? ' checked' : '') . '><span><strong>' . h($role['name']) . '</strong><small>' . h($role['description']) . '</small></span></label>';
    }
    return $html . '</div>';
}

function current_scope_label(): string
{
    return data_company_name(current_company_id());
}

function count_for_company(array $records, int $companyId): int
{
    return count(array_filter($records, static function(array $record) use ($companyId): bool {
        if (isset($record['company_id'])) {
            return (int) $record['company_id'] === $companyId;
        }
        if (isset($record['company_ids']) && is_array($record['company_ids'])) {
            return in_array($companyId, array_map('intval', $record['company_ids']), true);
        }
        return false;
    }));
}
