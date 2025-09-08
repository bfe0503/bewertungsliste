<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Flash messages stored in session for next request.
 */
final class Flash
{
    private const KEY = '_flash';

    public static function add(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int, array{type:string,message:string}> */
    public static function consume(): array
    {
        $msgs = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return $msgs;
    }
}
