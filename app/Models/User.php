<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class User
{
    public int $id;
    public string $email;
    public string $password_hash;
    public ?string $display_name;

    public static function findByEmail(string $email): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    public static function create(string $email, string $password, ?string $displayName): int
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = Db::pdo()->prepare('INSERT INTO users (email, password_hash, display_name) VALUES (?, ?, ?)');
        $stmt->execute([$email, $hash, $displayName]);
        return (int)Db::pdo()->lastInsertId();
    }

    private static function fromRow(array $r): self
    {
        $u = new self();
        $u->id = (int)$r['id'];
        $u->email = $r['email'];
        $u->password_hash = $r['password_hash'];
        $u->display_name = $r['display_name'] ?? null;
        return $u;
    }
}
