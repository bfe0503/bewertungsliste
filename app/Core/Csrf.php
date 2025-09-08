<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Simple CSRF token helper bound to the session.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(string $form): string
    {
        if (!isset($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = [];
        }
        $token = bin2hex(random_bytes(16));
        $_SESSION[self::KEY][$form] = $token;
        return $token;
    }

    public static function validate(string $form, ?string $token): bool
    {
        $ok = isset($_SESSION[self::KEY][$form]) && hash_equals($_SESSION[self::KEY][$form], (string)$token);
        // One-time token
        unset($_SESSION[self::KEY][$form]);
        return $ok;
    }
}
