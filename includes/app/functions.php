<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $base = defined('APP_URL') ? APP_URL : '/app';
    return rtrim($base, '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function root_url(string $path = ''): string
{
    $base = defined('ROOT_URL') ? ROOT_URL : '';
    return rtrim($base, '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function redirect_to(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return filter_var($_POST[$key] ?? $default, FILTER_VALIDATE_INT) ?: $default;
}

function query_string(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query_int(string $key, int $default = 0): int
{
    return filter_var($_GET[$key] ?? $default, FILTER_VALIDATE_INT) ?: $default;
}

function money(float|int|string|null $value): string
{
    return '$' . number_format((float) $value, 2);
}

function compact_money(float|int|string|null $value): string
{
    $number = (float) $value;
    if (abs($number) >= 1000000) {
        return '$' . number_format($number / 1000000, 2) . 'M';
    }
    if (abs($number) >= 1000) {
        return '$' . number_format($number / 1000, 1) . 'K';
    }
    return money($number);
}

function date_us(?string $value, bool $withTime = false): string
{
    if (!$value) {
        return '—';
    }
    try {
        $date = new DateTimeImmutable($value);
        return $date->format($withTime ? 'M j, Y g:i A' : 'M j, Y');
    } catch (Throwable) {
        return h($value);
    }
}

function status_label(string $status): string
{
    return ucwords(str_replace(['_', '-'], ' ', $status));
}

function status_class(string $status): string
{
    return 'status-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($status));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= strtoupper(substr($part, 0, 1));
    }
    return $out ?: 'GU';
}

function csrf_token(): string
{
    if (empty($_SESSION['gruber_csrf'])) {
        $_SESSION['gruber_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['gruber_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('The form session expired. Return to the previous page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['gruber_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['gruber_flash'] ?? [];
    unset($_SESSION['gruber_flash']);
    return is_array($items) ? $items : [];
}

function current_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
}

function current_user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown browser'), 0, 250);
}

function safe_return_to(string $candidate, string $fallback = 'dashboard.php'): string
{
    $candidate = trim($candidate);
    if ($candidate === '' || str_contains($candidate, '://') || str_starts_with($candidate, '//')) {
        return app_url($fallback);
    }
    if (str_starts_with($candidate, '/')) {
        $appBase = rtrim(APP_URL, '/');
        if (!str_starts_with($candidate, $appBase)) {
            return app_url($fallback);
        }
        return $candidate;
    }
    return app_url(ltrim($candidate, '/'));
}

function array_find_by_id(array $records, int|string $id): ?array
{
    foreach ($records as $record) {
        if ((string) ($record['id'] ?? '') === (string) $id) {
            return $record;
        }
    }
    return null;
}

function record_name(array $record, string $fallback = 'Record'): string
{
    foreach (['name', 'title', 'email', 'number', 'po_number', 'supplier_number', 'item_number'] as $key) {
        if (!empty($record[$key])) {
            return (string) $record[$key];
        }
    }
    return $fallback;
}

function paginate_records(array $records, int $page, int $perPage = 10): array
{
    $total = count($records);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;
    return [
        'items' => array_slice($records, $offset, $perPage),
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'per_page' => $perPage,
    ];
}

function sort_records(array $records, string $field, string $direction = 'asc'): array
{
    usort($records, static function (array $a, array $b) use ($field, $direction): int {
        $left = $a[$field] ?? '';
        $right = $b[$field] ?? '';
        $cmp = is_numeric($left) && is_numeric($right)
            ? ((float) $left <=> (float) $right)
            : strcasecmp((string) $left, (string) $right);
        return strtolower($direction) === 'desc' ? -$cmp : $cmp;
    });
    return $records;
}
