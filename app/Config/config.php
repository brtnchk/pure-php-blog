<?php declare(strict_types=1);

use App\Core\Env;

Env::load(dirname(__DIR__, 2) . '/.env');

return [
    'app' => [
        'env'   => Env::get('APP_ENV', 'prod'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url'   => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
    ],
    'db' => [
        'host'    => Env::get('DB_HOST', '127.0.0.1'),
        'port'    => Env::int('DB_PORT', 3306),
        'name'    => Env::get('DB_NAME', 'blog'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'paths' => [
        'templates' => dirname(__DIR__, 2) . '/templates',
        'compile'   => dirname(__DIR__, 2) . '/templates_c',
        'cache'     => dirname(__DIR__, 2) . '/cache',
        'public'    => dirname(__DIR__, 2) . '/public',
    ],
    'pagination' => [
        'per_page' => 6,
    ],
];