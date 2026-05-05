<?php declare(strict_types=1);

namespace App\Core;

final class Env
{
    /** @var array<string, string>|null */
    private static ?array $values = null;

    public static function load(string $file): void
    {
        $values = [];

        if (is_readable($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines === false ? [] : $lines as $line) {
                if ($line === '' || $line[0] === '#') {
                    continue;
                }

                [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                $values[trim($k)] = trim($v);
            }
        }

        self::$values = $values;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$values === null) {
            self::$values = [];
        }

        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $fromSystem = getenv($key);

        return $fromSystem !== false ? $fromSystem : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return (bool) filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null ? $default : (int) $value;
    }
}