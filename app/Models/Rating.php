<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Rating
{
    /**
     * Insert or update user's rating and optional comment for an item.
     * Table: ratings(id, item_id, user_id, rating, comment NULL, created_at)
     * Policy:
     * - Always set rating (clamped 1..5)
     * - If $clearComment === true -> set comment = NULL
     * - Else if $comment !== null -> set comment = trimmed $comment
     * - Else leave comment as is
     */
    public static function upsertWithPolicy(int $itemId, int $userId, int $score, ?string $comment, bool $clearComment): void
    {
        // server-side clamp
        if ($score < 1) { $score = 1; }
        if ($score > 5) { $score = 5; }

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            // Lock existing row if present
            $sel = $pdo->prepare('SELECT id FROM ratings WHERE item_id = ? AND user_id = ? LIMIT 1 FOR UPDATE');
            $sel->execute([$itemId, $userId]);
            $id = $sel->fetchColumn();

            if ($id) {
                if ($clearComment) {
                    $upd = $pdo->prepare('UPDATE ratings SET rating = ?, comment = NULL, created_at = NOW() WHERE id = ?');
                    $upd->execute([$score, (int)$id]);
                } elseif ($comment !== null) {
                    $upd = $pdo->prepare('UPDATE ratings SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?');
                    $upd->execute([$score, $comment, (int)$id]);
                } else {
                    $upd = $pdo->prepare('UPDATE ratings SET rating = ?, created_at = NOW() WHERE id = ?');
                    $upd->execute([$score, (int)$id]);
                }
            } else {
                $ins = $pdo->prepare('INSERT INTO ratings (item_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())');
                $ins->execute([$itemId, $userId, $score, $clearComment ? null : $comment]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array{avg: float, count: int} */
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
     * Return latest comments per item (up to $limit each).
     * @param array<int> $itemIds
     * @return array<int, array<int, array{ user:?string, comment:?string, created_at:string }>>
     */
    public static function latestCommentsForItems(array $itemIds, int $limit = 3): array
    {
        $out = [];
        if (empty($itemIds)) {
            return $out;
        }

        // Fetch a generous pool and group in PHP (simple and robust)
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $sql = "
            SELECT r.item_id, r.comment, r.created_at, u.username
            FROM ratings r
            JOIN users u ON u.id = r.user_id
            WHERE r.item_id IN ($placeholders) AND r.comment IS NOT NULL AND r.comment <> ''
            ORDER BY r.item_id ASC, r.created_at DESC
            LIMIT " . (count($itemIds) * max(1, $limit) * 4); // cushion factor
        $stmt = Db::pdo()->prepare($sql);
        foreach ($itemIds as $i => $id) {
            $stmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
        }
        $stmt->execute();

        // Group and trim per item
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $iid = (int)$row['item_id'];
            $out[$iid] = $out[$iid] ?? [];
            if (count($out[$iid]) >= $limit) {
                continue;
            }
            $out[$iid][] = [
                'user'       => $row['username'] !== null ? (string)$row['username'] : null,
                'comment'    => $row['comment'] !== null ? (string)$row['comment'] : null,
                'created_at' => (string)$row['created_at'],
            ];
        }

        // Ensure keys exist
        foreach ($itemIds as $iid) {
            $iid = (int)$iid;
            $out[$iid] = $out[$iid] ?? [];
        }
        return $out;
    }
}
