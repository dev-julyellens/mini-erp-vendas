<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ApiRateLimitRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * @return array{allowed: bool, retry_after: int}
     */
    public function hit(string $bucketKey, int $limit, int $windowSeconds): array
    {
        $this->cleanupExpiredBuckets();

        $now = new \DateTimeImmutable('now');
        $resetAt = $now->modify('+' . $windowSeconds . ' seconds')->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO api_rate_limit_buckets (bucket_key, request_count, reset_at)
             VALUES (:bucket_key, 1, :reset_at)
             ON CONFLICT (bucket_key) DO UPDATE
             SET request_count = CASE
                 WHEN api_rate_limit_buckets.reset_at <= CURRENT_TIMESTAMP THEN 1
                 ELSE api_rate_limit_buckets.request_count + 1
             END,
             reset_at = CASE
                 WHEN api_rate_limit_buckets.reset_at <= CURRENT_TIMESTAMP THEN EXCLUDED.reset_at
                 ELSE api_rate_limit_buckets.reset_at
             END
             RETURNING request_count, reset_at'
        );
        $stmt->execute([
            'bucket_key' => $bucketKey,
            'reset_at' => $resetAt,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false)
        {
            return ['allowed' => true, 'retry_after' => 0];
        }

        $count = (int) $row['request_count'];
        $retryAfter = max(0, strtotime((string) $row['reset_at']) - time());

        return [
            'allowed' => $count <= $limit,
            'retry_after' => $retryAfter,
        ];
    }

    private function cleanupExpiredBuckets(): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM api_rate_limit_buckets WHERE reset_at < (CURRENT_TIMESTAMP - INTERVAL \'1 day\')'
        );
        $stmt->execute();
    }
}
