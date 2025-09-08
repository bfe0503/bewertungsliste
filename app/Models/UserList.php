<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class UserList
{
    public int $id;
    public int $user_id;
    public string $title;
    public ?string $description;
    public string $visibility;

    public static function create(int $userId, string $title, ?string $description, string $visibility = 'public'): int
    {
        $stmt = Db::pdo()->prepare('
            INSERT INTO lists (user_id, title, description, visibility)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $title, $description, $visibility]);
        return (int)Db::pdo()->lastInsertId();
    }

    public static function find(int $id): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM lists WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function allPublic(int $limit = 30): array
    {
        $stmt = Db::pdo()->prepare('
            SELECT * FROM lists
            WHERE visibility = "public"
            ORDER BY created_at DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'fromRow'], $rows);
    }

    /** @return array<int, self> */
    public static function allByUser(int $userId): array
    {
        $stmt = Db::pdo()->prepare('
            SELECT * FROM lists
            WHERE user_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'fromRow'], $rows);
    }

    private static function fromRow(array $r): self
    {
        $l = new self();
        $l->id = (int)$r['id'];
        $l->user_id = (int)$r['user_id'];
        $l->title = $r['title'];
        $l->description = $r['description'] ?? null;
        $l->visibility = $r['visibility'];
        return $l;
    }
}
