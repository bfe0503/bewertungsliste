<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Item
{
    public int $id;
    public int $list_id;
    public int $user_id;
    public string $title;
    public ?string $url;

    /** Create a new item and return its ID */
    public static function create(int $listId, int $userId, string $title, ?string $url): int
    {
        $stmt = Db::pdo()->prepare(
            'INSERT INTO items (list_id, user_id, title, url, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$listId, $userId, $title, $url]);
        return (int)Db::pdo()->lastInsertId();
    }

    /**
     * Return items for a list with rating stats and current user's rating.
     * The return shape is aligned with existing views (name/description keys).
     *
     * @return array<int, array{
     *   id:int,
     *   name:string,
     *   description:?string,
     *   avg:float,
     *   count:int,
     *   my_score:?int
     * }>
     */
    public static function forListWithStats(int $listId, ?int $currentUserId): array
    {
        $sql = '
            SELECT
              i.id,
              i.title,
              i.url,
              COALESCE(AVG(r.rating), 0) AS avg,
              COUNT(r.id) AS cnt,
              ur.rating AS my_rating
            FROM items i
            LEFT JOIN ratings r  ON r.item_id = i.id
            LEFT JOIN ratings ur ON ur.item_id = i.id AND ur.user_id = :uid
            WHERE i.list_id = :lid
            GROUP BY i.id
            ORDER BY i.created_at DESC, i.id DESC
        ';
        $stmt = Db::pdo()->prepare($sql);
        // Using NULL for :uid is fine; LEFT JOIN will not match and my_rating stays NULL.
        if ($currentUserId === null) {
            $stmt->bindValue(':uid', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':lid', $listId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $r): array {
            return [
                'id'        => (int)$r['id'],
                // map DB fields to legacy view keys:
                'name'      => (string)$r['title'],
                'description'=> $r['url'] !== null ? (string)$r['url'] : null,
                'avg'       => round((float)$r['avg'], 2),
                'count'     => (int)$r['cnt'],
                'my_score'  => $r['my_rating'] !== null ? (int)$r['my_rating'] : null,
            ];
        }, $rows);
    }
}
