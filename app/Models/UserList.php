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
    public ?string $description = null;
    public int $is_public;

    /** Create a new list and return its ID */
    public static function create(int $userId, string $title, ?string $description, int $isPublic = 1): int
    {
        $stmt = Db::pdo()->prepare(
            'INSERT INTO lists (user_id, title, description, is_public, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $title, $description, $isPublic]);
        return (int)Db::pdo()->lastInsertId();
    }

    /** Admin overview */
    public static function all(): array
    {
        $q = Db::pdo()->query('SELECT id, title, user_id FROM lists ORDER BY id ASC');
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static function(array $r): array {
            return [
                'id'       => (int)$r['id'],
                'title'    => (string)$r['title'],
                'user_id'  => (int)$r['user_id'],
            ];
        }, $rows);
    }

    /** Lists owned by a specific user (for dashboard) */
    public static function allByUser(int $userId): array
    {
        $stmt = Db::pdo()->prepare('SELECT id, title, user_id, is_public, created_at FROM lists WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Public lists (for homepage) */
    public static function allPublic(int $limit = 30): array
    {
        $limit = max(1, min($limit, 200));
        $stmt = Db::pdo()->prepare('SELECT id, title, user_id, is_public, created_at FROM lists WHERE is_public = 1 ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Find a list by id (rich) */
    public static function findById(int $id): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT id, user_id, title, description, is_public FROM lists WHERE id = ?');
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }
        $l = new self();
        $l->id         = (int)$r['id'];
        $l->user_id    = (int)$r['user_id'];
        $l->title      = (string)$r['title'];
        $l->description= $r['description'] !== null ? (string)$r['description'] : null;
        $l->is_public  = (int)$r['is_public'];
        return $l;
    }

    /** BC alias if older code calls find() */
    public static function find(int $id): ?self
    {
        return self::findById($id);
    }

    /** Update title/visibility flag */
    public static function update(int $id, string $title, int $isPublic): void
    {
        $stmt = Db::pdo()->prepare('UPDATE lists SET title = ?, is_public = ? WHERE id = ?');
        $stmt->execute([$title, $isPublic, $id]);
    }

    /** Delete a list (FK ON DELETE CASCADE should remove children) */
    public static function deleteCascade(int $id): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM lists WHERE id = ?');
        $stmt->execute([$id]);
    }
}
