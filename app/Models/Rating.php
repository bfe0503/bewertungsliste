<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Rating
{
    /**
     * Insert or update user's rating for an item.
     * Ratings table columns: id, item_id, user_id, rating, created_at
     */
    public static function upsertScore(int $itemId, int $userId, int $score): void
    {
        // server-side clamp just in case:
        if ($score < 1) { $score = 1; }
        if ($score > 5) { $score = 5; }

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // Check if a rating already exists for (item_id, user_id)
            $sel = $pdo->prepare('SELECT id FROM ratings WHERE item_id = ? AND user_id = ? LIMIT 1 FOR UPDATE');
            $sel->execute([$itemId, $userId]);
            $id = $sel->fetchColumn();

            if ($id) {
                $upd = $pdo->prepare('UPDATE ratings SET rating = ?, created_at = NOW() WHERE id = ?');
                $upd->execute([$score, (int)$id]);
            } else {
                $ins = $pdo->prepare('INSERT INTO ratings (item_id, user_id, rating, created_at) VALUES (?, ?, ?, NOW())');
                $ins->execute([$itemId, $userId, $score]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Aggregate stats for one item.
     * @return array{avg: float, count: int}
     */
    public static function stats(int $itemId): array
    {
        $stmt = Db::pdo()->prepare('SELECT COALESCE(AVG(rating),0) AS avg_score, COUNT(*) AS cnt FROM ratings WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avg_score' => 0, 'cnt' => 0];

        return [
            'avg'   => round((float)$row['avg_score'], 2),
            'count' => (int)$row['cnt'],
        ];
        }

    /**
     * Comments are not present in the current schema.
     * Return an empty list per item to keep views compatible.
     *
     * @param array<int> $itemIds
     * @return array<int, array<int, array{user:?string, comment:?string, created_at:string}>>
     */
    public static function latestCommentsForItems(array $itemIds, int $limit = 3): array
    {
        $out = [];
        foreach ($itemIds as $id) {
            $out[(int)$id] = []; // no comments feature -> return empty
        }
        return $out;
    }
}
