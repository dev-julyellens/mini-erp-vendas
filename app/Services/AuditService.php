<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Models\User;
use App\Helpers\Auth;
use App\Models\Product;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Repositories\AuditRepository;

final class AuditService
{
    /** @var list<string> */
    public const ENTITIES = ['produtos', 'clientes', 'vendas', 'estoque', 'financeiro', 'usuarios'];

    /** @var array<string, string> */
    public const ACTION_LABELS = [
        'criar' => 'Criar',
        'editar' => 'Editar',
        'excluir' => 'Excluir',
        'login' => 'Login',
        'logout' => 'Logout',
        'solicitar_redefinir_senha' => 'Solicitar redefinição de senha',
        'redefinir_senha' => 'Redefinir senha',
        'consentimento_lgpd' => 'Consentimento LGPD',
        'venda' => 'Venda',
        'saida_estoque' => 'Saída de estoque',
        'cancelamento_venda' => 'Cancelamento de venda',
        'entrada_estoque' => 'Entrada de estoque',
        'conta_receber' => 'Conta a receber',
        'recebimento' => 'Recebimento',
        'cancelamento_conta_receber' => 'Cancelamento conta a receber',
    ];

    /** @var array<string, string> */
    public const ENTITY_LABELS = [
        'produtos' => 'Produtos',
        'clientes' => 'Clientes',
        'vendas' => 'Vendas',
        'estoque' => 'Estoque',
        'financeiro' => 'Financeiro',
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
            Logger::exception($e, 'Falha ao registrar auditoria.', [
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
            ]);
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
        catch (\JsonException $e)
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

    /**
     * @return array<string, mixed>
     */
    public static function productSnapshot(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category_id' => $product->categoryId,
            'unit_of_measure' => $product->unitOfMeasure,
            'cost_price' => $product->costPrice,
            'margin_percent' => $product->marginPercent,
            'markup_percent' => $product->markupPercent,
            'price' => $product->price,
            'stock' => $product->stock,
            'min_stock' => $product->minStock,
            'type' => $product->type,
            'estimated_time_minutes' => $product->estimatedTimeMinutes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function customerSnapshot(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
