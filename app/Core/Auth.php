<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tiny auth helper: login, logout, current user id.
 */
final class Auth
{
    private const KEY = '_uid';

    public static function login(int $userId): void
    {
        $_SESSION[self::KEY] = $userId;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::KEY]);
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::KEY]) ? (int)$_SESSION[self::KEY] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }
}
