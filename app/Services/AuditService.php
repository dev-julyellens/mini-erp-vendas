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
    /** @var list<string> */
    public const ENTITIES = ['produtos', 'clientes', 'vendas', 'estoque', 'usuarios'];

    /** @var array<string, string> */
    public const ACTION_LABELS = [
        'criar' => 'Criar',
        'editar' => 'Editar',
        'excluir' => 'Excluir',
        'login' => 'Login',
        'logout' => 'Logout',
        'solicitar_redefinir_senha' => 'Solicitar redefinição de senha',
        'redefinir_senha' => 'Redefinir senha',
        'venda' => 'Venda',
        'saida_estoque' => 'Saída de estoque',
    ];

    /** @var array<string, string> */
    public const ENTITY_LABELS = [
        'produtos' => 'Produtos',
        'clientes' => 'Clientes',
        'vendas' => 'Vendas',
        'estoque' => 'Estoque',
        'usuarios' => 'Usuários',
    ];

    private AuditRepository $audit;

    public function __construct(?AuditRepository $audit = null)
    {
        $this->audit = $audit ?? new AuditRepository();
    }

    /**
     * Falha na auditoria não deve interromper a operação de negócio já concluída.
     *
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
        try
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
        catch (\Throwable $e)
        {
            error_log(sprintf(
                'Audit log failed [%s/%s entity_id=%s]: %s',
                $action,
                $entity,
                $entityId !== null ? (string) $entityId : 'null',
                $e->getMessage()
            ));
        }
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
        $normalizedEntity = self::normalizeEntityFilter($entity);

        $result = $this->audit->search(
            $userId,
            $normalizedFrom,
            $normalizedTo,
            $normalizedEntity,
            $page,
            $perPage
        );

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'users' => $this->audit->listUsersForFilter(),
        ];
    }

    /**
     * @param array{user_id: ?int, date_from: string, date_to: string, entity: string} $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $query = [];

        if ($filters['user_id'] !== null && $filters['user_id'] > 0)
        {
            $query['user_id'] = (string) $filters['user_id'];
        }

        if ($filters['date_from'] !== '')
        {
            $query['date_from'] = $filters['date_from'];
        }

        if ($filters['date_to'] !== '')
        {
            $query['date_to'] = $filters['date_to'];
        }

        $entity = self::normalizeEntityFilter($filters['entity']);
        if ($entity !== null)
        {
            $query['entity'] = $entity;
        }

        return $query;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function formatJsonDisplay(?array $data): string
    {
        if ($data === null || $data === [])
        {
            return '—';
        }

        try
        {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }
        catch (\JsonException)
        {
            return '—';
        }

        return $json;
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }

    public static function entityLabel(string $entity): string
    {
        return self::ENTITY_LABELS[$entity] ?? $entity;
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

    private static function normalizeEntityFilter(?string $entity): ?string
    {
        if ($entity === null || trim($entity) === '')
        {
            return null;
        }

        $entity = trim($entity);

        return in_array($entity, self::ENTITIES, true) ? $entity : null;
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
        $candidates = [];

        if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '')
        {
            $candidates[] = trim($_SERVER['REMOTE_ADDR']);
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && is_string($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== '')
        {
            $candidates[] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        foreach ($candidates as $ip)
        {
            if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            {
                return strlen($ip) <= 45 ? $ip : substr($ip, 0, 45);
            }
        }

        return null;
    }

    private static function userAgent(): ?string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return is_string($ua) && $ua !== '' ? $ua : null;
    }
}
