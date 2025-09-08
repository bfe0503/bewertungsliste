<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Item
{
    public int $id;
    public int $list_id;
    public string $name;
    public ?string $description;

    public static function create(int $listId, string $name, ?string $description): int
    {
        $stmt = Db::pdo()->prepare('
            INSERT INTO list_items (list_id, name, description)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$listId, $name, $description]);
        return (int)Db::pdo()->lastInsertId();
    }

    /** @return array<int, array{id:int,name:string,description:?string,avg:float,count:int,my_score:?int}> */
    public static function forListWithStats(int $listId, ?int $currentUserId): array
    {
        $sql = '
            SELECT
              i.id,
              i.name,
              i.description,
              COALESCE(AVG(r.score), 0) AS avg,
              COUNT(r.id) AS count,
              ur.score AS my_score
            FROM list_items i
            LEFT JOIN ratings r ON r.item_id = i.id
            LEFT JOIN ratings ur ON ur.item_id = i.id AND ur.user_id = :uid
            WHERE i.list_id = :lid
            GROUP BY i.id
            ORDER BY i.created_at DESC, i.id DESC
        ';
        $stmt = Db::pdo()->prepare($sql);
        // Using NULL for :uid is fine; the join will not match and my_score stays NULL.
        $stmt->bindValue(':uid', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':lid', $listId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cast types
        return array_map(static function (array $r): array {
            return [
                'id' => (int)$r['id'],
                'name' => (string)$r['name'],
                'description' => $r['description'] !== null ? (string)$r['description'] : null,
                'avg' => round((float)$r['avg'], 2),
                'count' => (int)$r['count'],
                'my_score' => $r['my_score'] !== null ? (int)$r['my_score'] : null,
            ];
        }, $rows);
    }
}
