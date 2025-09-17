<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class User
{
    public int $id;
    public ?string $email;
    public string $password_hash;
    public ?string $display_name;
    public ?string $username;
    public ?string $username_lower;
    public ?int $is_admin;

    public static function findById(int $id): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT id, email, password_hash, display_name, username, username_lower, is_admin FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    public static function findByUsernameLower(string $usernameLower): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT id, email, password_hash, display_name, username, username_lower, is_admin FROM users WHERE username_lower = ?');
        $stmt->execute([$usernameLower]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT id, email, password_hash, display_name, username, username_lower, is_admin FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    public static function createWithUsername(string $username, string $passwordHash): int
    {
        $usernameLower = strtolower($username);
        $stmt = Db::pdo()->prepare('INSERT INTO users (username, username_lower, password_hash, display_name, email) VALUES (?, ?, ?, ?, NULL)');
        $stmt->execute([$username, $usernameLower, $passwordHash, $username]);
        return (int)Db::pdo()->lastInsertId();
    }

    public static function updateUsername(int $id, string $username): void
    {
        $stmt = Db::pdo()->prepare('UPDATE users SET username = ?, username_lower = ? WHERE id = ?');
        $stmt->execute([$username, strtolower($username), $id]);
    }

    public static function updatePassword(int $id, string $hash): void
    {
        $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Admin listing (basic) */
    public static function all(): array
    {
        $q = Db::pdo()->query('SELECT id, email, display_name, username, username_lower, is_admin FROM users ORDER BY id ASC');
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'fromRow'], $rows);
    }

    private static function fromRow(array $r): self
    {
        $u = new self();
        $u->id = (int)$r['id'];
        $u->email = $r['email'] ?? null;
        $u->password_hash = $r['password_hash'] ?? '';
        $u->display_name = $r['display_name'] ?? null;
        $u->username = $r['username'] ?? null;
        $u->username_lower = $r['username_lower'] ?? null;
        $u->is_admin = isset($r['is_admin']) ? (int)$r['is_admin'] : null;
        return $u;
    }
}