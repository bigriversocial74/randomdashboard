<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Gruber Procurement Intelligence',
        'environment' => 'production',
        'timezone' => 'America/Phoenix',
        'support_email' => 'YOUR_EMAIL@example.com',
        'demo_enabled' => true,
        'import_storage_path' => __DIR__ . '/storage/imports',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'YOUR_DATABASE_NAME',
        'username' => 'YOUR_DATABASE_USER',
        'password' => 'YOUR_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'session_lifetime_minutes' => 120,
        'session_absolute_lifetime_minutes' => 720,
        'password_reset_lifetime_minutes' => 60,
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        // Only list reverse proxies you control. Forwarded HTTPS headers are ignored otherwise.
        'trusted_proxies' => [],
    ],
];
