<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class User
{
    public int $id;
    public ?string $email;
    public string $username;
    public string $username_lower;
    public string $password_hash;
    public int $is_admin;
    public string $created_at;
    public ?string $display_name;

    /** Insert new user and return its ID */
    public static function create(string $username, ?string $email, string $passwordHash): int
    {
        $lower = mb_strtolower($username);
        $stmt = Db::pdo()->prepare('
            INSERT INTO users (email, username, username_lower, password_hash, is_admin)
            VALUES (:email, :username, :username_lower, :password_hash, 0)
        ');
        $stmt->execute([
            ':email'         => $email,
            ':username'      => $username,
            ':username_lower'=> $lower,
            ':password_hash' => $passwordHash,
        ]);
        return (int) Db::pdo()->lastInsertId();
    }

    /** Find by case-insensitive username (via username_lower) */
    public static function findByUsernameLower(string $lower): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM users WHERE username_lower = ? LIMIT 1');
        $stmt->execute([$lower]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    /** Find by id */
    public static function findById(int $id): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    /** Update password hash */
    public static function updatePassword(int $id, string $hash): void
    {
        $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    /** Delete user (lists/items/ratings get removed via FK ON DELETE CASCADE) */
    public static function delete(int $id): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Get all users for admin overview (lightweight payload) */
    public static function all(): array
    {
        $stmt = Db::pdo()->query('
            SELECT id, username, username_lower, is_admin, created_at
            FROM users
            ORDER BY id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Hydrate object from DB row */
    private static function fromRow(array $r): self
    {
        $u = new self();
        $u->id             = (int) $r['id'];
        $u->email          = $r['email'] !== null ? (string)$r['email'] : null;
        $u->username       = (string) $r['username'];
        $u->username_lower = (string) $r['username_lower'];
        $u->password_hash  = (string) $r['password_hash'];
        $u->is_admin       = (int) $r['is_admin'];
        $u->created_at     = (string) $r['created_at'];
        $u->display_name   = array_key_exists('display_name', $r) && $r['display_name'] !== null
            ? (string)$r['display_name'] : null;
        return $u;
    }
}
