<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Rating
{
    /**
     * Legacy helper – keeps behavior (no explicit clear).
     */
    public static function upsert(int $itemId, int $userId, int $score, ?string $comment = null): void
    {
        self::upsertWithPolicy($itemId, $userId, $score, $comment, false);
    }

    /**
     * Create or update a rating with comment policy.
     * If $clearComment is true -> comment will be set to NULL.
     * If $comment is null and $clearComment is false -> keep existing comment.
     * If $comment is non-null -> set/replace with that text.
     */
    public static function upsertWithPolicy(int $itemId, int $userId, int $score, ?string $comment, bool $clearComment): void
    {
        $score = max(1, min(5, $score));

        if ($clearComment) {
            // Force NULL comment on upsert
            $sql = '
                INSERT INTO ratings (`item_id`, `user_id`, `score`, `comment`)
                VALUES (?, ?, ?, NULL)
                ON DUPLICATE KEY UPDATE
                    `score` = VALUES(`score`),
                    `comment` = NULL,
                    `created_at` = CURRENT_TIMESTAMP
            ';
            $stmt = Db::pdo()->prepare($sql);
            $stmt->execute([$itemId, $userId, $score]);
            return;
        }

        // Preserve existing comment if $comment is NULL; otherwise update to the given text
        $sql = '
            INSERT INTO ratings (`item_id`, `user_id`, `score`, `comment`)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `score` = VALUES(`score`),
                `comment` = COALESCE(VALUES(`comment`), `comment`),
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
     * Returns: itemId => list of {user_id, user, score, comment, created_at}
     *
     * @param array<int,int> $itemIds
     * @return array<int, array<int, array{user_id:int,user:string,score:int,comment:string,created_at:string}>>
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
                r.`user_id`,
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
                'user_id'    => (int)$row['user_id'],
                'user'       => (string)$row['user'],
                'score'      => (int)$row['score'],
                'comment'    => (string)$row['comment'],
                'created_at' => (string)$row['created_at'],
            ];
        }

        return $out;
    }
}
