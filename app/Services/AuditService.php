<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Auth;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Repositories\AuditRepository;

final class AuditService
{
    private AuditRepository $audit;

    public function __construct(?AuditRepository $audit = null)
    {
        $this->audit = $audit ?? new AuditRepository();
    }

    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function record(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): void
    {
        $this->audit->insert(
            $userId ?? Auth::id(),
            $action,
            $entity,
            $entityId,
            $oldValues,
            $newValues,
            self::clientIp(),
            self::userAgent()
        );
    }

    /**
     * @return array{items: list<AuditLog>, total: int, users: list<array{id: int, name: string, email: string}>}
     */
    public function searchLogs(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $entity,
        int $page,
        int $perPage
    ): array
    {
        $normalizedFrom = $this->normalizeDateStart($dateFrom);
        $normalizedTo = $this->normalizeDateEnd($dateTo);

        $result = $this->audit->search(
            $userId,
            $normalizedFrom,
            $normalizedTo,
            $entity !== '' ? $entity : null,
            $page,
            $perPage
        );

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'users' => $this->audit->listUsersForFilter(),
        ];
    }

    public static function productSnapshot(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
        ];
    }

    public static function customerSnapshot(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ];
    }

    public static function userSnapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'active' => $user->active,
        ];
    }

    private function normalizeDateStart(?string $date): ?string
    {
        if ($date === null || trim($date) === '')
        {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($date));
        if ($parsed === false)
        {
            return null;
        }

        return $parsed->format('Y-m-d 00:00:00');
    }

    private function normalizeDateEnd(?string $date): ?string
    {
        if ($date === null || trim($date) === '')
        {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($date));
        if ($parsed === false)
        {
            return null;
        }

        return $parsed->format('Y-m-d 23:59:59');
    }

    private static function clientIp(): ?string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if (!is_string($ip) || $ip === '')
        {
            return null;
        }

        if (str_contains($ip, ','))
        {
            $ip = trim(explode(',', $ip)[0]);
        }

        return strlen($ip) <= 45 ? $ip : substr($ip, 0, 45);
    }

    private static function userAgent(): ?string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return is_string($ua) && $ua !== '' ? $ua : null;
    }
}
