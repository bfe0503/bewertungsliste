<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Simple config holder.
 */
final class Config
{
    private static array $data = [];

    public static function init(array $config): void
    {
        self::$data = $config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }
}
