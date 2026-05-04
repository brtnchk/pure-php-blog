<?php

declare(strict_types=1);

$env = static function (string $key, ?string $default = null): ?string {
    static $loaded = null;
    if ($loaded === null) {
        $loaded = [];
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                $loaded[trim($k)] = trim($v);
            }
        }
    }
    return $loaded[$key] ?? getenv($key) ?: $default;
};

return [
    'app' => [
        'env'   => $env('APP_ENV', 'prod'),
        'debug' => filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
        'url'   => rtrim($env('APP_URL', 'http://localhost'), '/'),
    ],
    'db' => [
        'host'    => $env('DB_HOST', '127.0.0.1'),
        'port'    => (int) $env('DB_PORT', '3306'),
        'name'    => $env('DB_NAME', 'blog'),
        'user'    => $env('DB_USER', 'root'),
        'pass'    => $env('DB_PASS', ''),
        'charset' => $env('DB_CHARSET', 'utf8mb4'),
    ],
    'paths' => [
        'templates'    => dirname(__DIR__, 2) . '/templates',
        'compile'      => dirname(__DIR__, 2) . '/templates_c',
        'cache'        => dirname(__DIR__, 2) . '/cache',
        'public'       => dirname(__DIR__, 2) . '/public',
    ],
    'pagination' => [
        'per_page' => 6,
    ],
];