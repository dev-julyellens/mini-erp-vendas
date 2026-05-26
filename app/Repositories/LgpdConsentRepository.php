<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LgpdConsentRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function hasAcceptedVersion(int $userId, string $policyVersion): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM lgpd_consents
             WHERE user_id = :user_id AND policy_version = :policy_version
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'policy_version' => $policyVersion,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function recordConsent(
        int $userId,
        string $policyVersion,
        string $ipAddress,
        ?string $userAgent
    ): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO lgpd_consents (user_id, policy_version, ip_address, user_agent)
             VALUES (:user_id, :policy_version, :ip_address, :user_agent)
             ON CONFLICT (user_id, policy_version) DO NOTHING'
        );
        $stmt->execute([
            'user_id' => $userId,
            'policy_version' => $policyVersion,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
