<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Rating
{
    /**
     * Create or update a rating with optional comment.
     * Backticks around `comment` to avoid keyword confusion.
     */
    public static function upsert(int $itemId, int $userId, int $score, ?string $comment = null): void
    {
        $score = max(1, min(5, $score));
        $sql = '
            INSERT INTO ratings (`item_id`, `user_id`, `score`, `comment`)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `score`   = VALUES(`score`),
                `comment` = VALUES(`comment`),
                `created_at` = CURRENT_TIMESTAMP
        ';
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute([$itemId, $userId, $score, $comment]);
    }

    /**
     * Return aggregate stats for an item (average and count).
     * @return array{avg:float,count:int}
     */
    public static function stats(int $itemId): array
    {
        $stmt = Db::pdo()->prepare('SELECT COALESCE(AVG(`score`),0) AS avg, COUNT(*) AS count FROM ratings WHERE `item_id` = ?');
        $stmt->execute([$itemId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avg' => 0, 'count' => 0];
        return ['avg' => round((float)$r['avg'], 2), 'count' => (int)$r['count']];
    }

    /**
     * Fetch up to $limit latest non-empty comments per item for the given item IDs.
     * Returns: itemId => list of {user, score, comment, created_at}
     *
     * @param array<int,int> $itemIds
     * @return array<int, array<int, array{user:string,score:int,comment:string,created_at:string}>>
     */
    public static function latestCommentsForItems(array $itemIds, int $limit = 3): array
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if (empty($itemIds)) {
            return [];
        }

        $ph = implode(',', array_fill(0, count($itemIds), '?'));

        $sql = "
            SELECT
                r.`item_id`,
                r.`score`,
                r.`comment`,
                r.`created_at`,
                COALESCE(NULLIF(TRIM(u.`display_name`), ''), u.`email`) AS user
            FROM ratings r
            INNER JOIN users u ON u.`id` = r.`user_id`
            WHERE r.`item_id` IN ($ph)
              AND r.`comment` IS NOT NULL
              AND r.`comment` <> ''
            ORDER BY r.`item_id` ASC, r.`created_at` DESC, r.`id` DESC
        ";

        $stmt = Db::pdo()->prepare($sql);
        foreach ($itemIds as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $iid = (int)$row['item_id'];
            if (!isset($out[$iid])) {
                $out[$iid] = [];
            }
            if (count($out[$iid]) >= $limit) {
                continue;
            }
            $out[$iid][] = [
                'user'       => (string)$row['user'],
                'score'      => (int)$row['score'],
                'comment'    => (string)$row['comment'],
                'created_at' => (string)$row['created_at'],
            ];
        }

        return $out;
    }
}
