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
    public int $is_public; // 0/1
    public string $created_at;

    /** Create new list and return its id */
    public static function create(int $userId, string $title, ?string $description, int $isPublic): int
    {
        $stmt = Db::pdo()->prepare('
            INSERT INTO lists (user_id, title, description, is_public)
            VALUES (:uid, :title, :desc, :pub)
        ');
        $stmt->execute([
            ':uid'   => $userId,
            ':title' => $title,
            ':desc'  => $description,
            ':pub'   => $isPublic === 1 ? 1 : 0,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    /** Find a list by id */
    public static function find(int $id): ?self
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM lists WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    /** All public lists (with owner info joinable in views if needed) */
    public static function allPublic(int $limit = 30): array
    {
        $stmt = Db::pdo()->prepare('
            SELECT l.*, u.username AS owner_username
            FROM lists l
            JOIN users u ON u.id = l.user_id
            WHERE l.is_public = 1
            ORDER BY l.id DESC
            LIMIT :lim
        ');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All lists by user */
    public static function allByUser(int $userId): array
    {
        $stmt = Db::pdo()->prepare('
            SELECT l.*, u.username AS owner_username
            FROM lists l
            JOIN users u ON u.id = l.user_id
            WHERE l.user_id = ?
            ORDER BY l.id DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Update list */
    public static function update(int $id, string $title, ?string $description, int $isPublic): void
    {
        $stmt = Db::pdo()->prepare('
            UPDATE lists
            SET title = :title,
                description = :desc,
                is_public = :pub
            WHERE id = :id
        ');
        $stmt->execute([
            ':title' => $title,
            ':desc'  => $description,
            ':pub'   => $isPublic === 1 ? 1 : 0,
            ':id'    => $id,
        ]);
    }

    /** Delete list; items/ratings are removed via FK ON DELETE CASCADE */
    public static function deleteCascade(int $id): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM lists WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Hydrate object */
    private static function fromRow(array $r): self
    {
        $m = new self();
        $m->id          = (int)$r['id'];
        $m->user_id     = (int)$r['user_id'];
        $m->title       = (string)$r['title'];
        $m->description = $r['description'] !== null ? (string)$r['description'] : null;
        $m->is_public   = (int)$r['is_public'];
        $m->created_at  = (string)$r['created_at'];
        return $m;
    }
}
