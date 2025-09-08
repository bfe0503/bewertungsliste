<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use PDO;

final class Rating
{
    public static function upsert(int $itemId, int $userId, int $score): void
    {
        $score = max(1, min(5, $score));
        $sql = '
            INSERT INTO ratings (item_id, user_id, score)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score), created_at = CURRENT_TIMESTAMP
        ';
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute([$itemId, $userId, $score]);
    }

    /** @return array{avg:float,count:int} */
    public static function stats(int $itemId): array
    {
        $stmt = Db::pdo()->prepare('SELECT COALESCE(AVG(score),0) AS avg, COUNT(*) AS count FROM ratings WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avg' => 0, 'count' => 0];
        return ['avg' => round((float)$r['avg'], 2), 'count' => (int)$r['count']];
    }
}
