<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\AppConfig;
use App\Helpers\CompanyContext;
use App\Helpers\DateHelper;
use App\Core\ValidationException;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\AccountsReceivable;
use App\Models\Installment;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ProductRepository;
use PDO;

final class NotificationService
{
    public const TYPE_LOW_STOCK = 'low_stock';
    public const TYPE_OVERDUE_ACCOUNT = 'overdue_account';
    public const TYPE_ORDER_CANCELED = 'order_canceled';
    public const TYPE_CRITICAL_ERROR = 'critical_error';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_LOW_STOCK,
        self::TYPE_OVERDUE_ACCOUNT,
        self::TYPE_ORDER_CANCELED,
        self::TYPE_CRITICAL_ERROR,
    ];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_LOW_STOCK => 'Estoque baixo',
        self::TYPE_OVERDUE_ACCOUNT => 'Conta vencida',
        self::TYPE_ORDER_CANCELED => 'Venda cancelada',
        self::TYPE_CRITICAL_ERROR => 'Erro crítico',
    ];

    /** @var array<string, string> */
    public const LEVEL_LABELS = [
        'info' => 'Informação',
        'warning' => 'Atenção',
        'danger' => 'Crítico',
    ];

    private const SYNC_INTERVAL_SECONDS = 60;
    private const SESSION_SYNC_KEY = 'notification_sync_at';
    private const SESSION_LAST_SEEN_KEY = 'notification_last_seen_id';

    private NotificationRepository $notifications;

    public function __construct(?NotificationRepository $notifications = null, ?PDO $pdo = null)
    {
        $this->notifications = $notifications ?? new NotificationRepository($pdo);
    }

    /**
     * @return array{
     *   notificationUnreadCount: int,
     *   notificationRecent: list<Notification>,
     *   notificationToasts: list<Notification>
     * }
     */
    public function layoutPayload(): array
    {
        $empty = [
            'notificationUnreadCount' => 0,
            'notificationRecent' => [],
            'notificationToasts' => [],
        ];

        if (!CompanyContext::hasSelected())
        {
            return $empty;
        }

        if ($this->shouldSyncOperationalAlerts())
        {
            $this->syncOperationalAlerts();
        }

        $lastSeenId = $this->lastSeenNotificationId();
        $toasts = $this->notifications->findUnreadAfterId($lastSeenId, 5);
        $maxId = $this->notifications->maxId();
        $this->storeLastSeenNotificationId($maxId);

        return [
            'notificationUnreadCount' => $this->notifications->countUnread(),
            'notificationRecent' => $this->notifications->findRecent(8),
            'notificationToasts' => $toasts,
        ];
    }

    public function notifyLowStock(Product $product): void
    {
        if (!$product->isLowStock() || $product->isService())
        {
            $this->resolveLowStockAlert($product->id);

            return;
        }

        $dedupeKey = $this->dedupeKey('low_stock', 'product', $product->id);
        if ($this->notifications->existsByDedupeKey($dedupeKey))
        {
            return;
        }

        $this->notifications->insert([
            'type' => self::TYPE_LOW_STOCK,
            'title' => 'Estoque baixo',
            'message' => sprintf(
                'O produto "%s" está com estoque %d (mínimo: %d).',
                $product->name,
                $product->stock,
                $product->minStock
            ),
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'level' => 'warning',
            'link_url' => '/products/edit?id=' . $product->id,
            'dedupe_key' => $dedupeKey,
        ]);
    }

    public function notifyOrderCanceled(Order $order): void
    {
        $dedupeKey = 'order_canceled:' . $order->id;
        if ($this->notifications->existsByDedupeKey($dedupeKey))
        {
            return;
        }

        $this->notifications->insert([
            'type' => self::TYPE_ORDER_CANCELED,
            'title' => 'Venda cancelada',
            'message' => sprintf(
                'A venda #%d foi cancelada (cliente #%d, total R$ %s).',
                $order->id,
                $order->customer_id,
                number_format((float) $order->total_amount, 2, ',', '.')
            ),
            'entity_type' => 'order',
            'entity_id' => $order->id,
            'level' => 'info',
            'link_url' => '/orders/show?id=' . $order->id,
            'dedupe_key' => $dedupeKey,
        ]);
    }

    public function notifyCriticalError(string $message, ?string $context = null): void
    {
        if (!CompanyContext::hasSelected())
        {
            return;
        }

        $normalized = trim($message);
        if ($normalized === '')
        {
            $normalized = 'Erro interno não especificado.';
        }

        $hash = substr(hash('sha256', $normalized . '|' . ($context ?? '')), 0, 16);
        $dedupeKey = 'critical_error:' . $hash;
        if ($this->notifications->existsUnreadByDedupe($dedupeKey))
        {
            return;
        }

        $body = $context !== null && $context !== ''
            ? $context . ': ' . $normalized
            : $normalized;

        if (!AppConfig::isDebug())
        {
            $body = 'Falha interna na API. Consulte os logs do sistema.';
        }

        $this->notifications->insert([
            'type' => self::TYPE_CRITICAL_ERROR,
            'title' => 'Erro crítico',
            'message' => mb_substr($body, 0, 500),
            'entity_type' => 'system',
            'entity_id' => null,
            'level' => 'danger',
            'link_url' => null,
            'dedupe_key' => $dedupeKey,
        ]);
    }

    public function syncOperationalAlerts(): void
    {
        if (!CompanyContext::hasSelected())
        {
            return;
        }

        $this->syncLowStockAlerts();
        $this->syncOverdueAccountAlerts();
        $this->syncOverdueInstallmentAlerts();
    }

    /**
     * @return array{items: list<Notification>, total: int}
     */
    public function search(
        int $page,
        int $perPage,
        ?string $type,
        ?bool $unreadOnly
    ): array
    {
        $normalizedType = $this->normalizeTypeFilter($type);

        return $this->notifications->paginate($page, $perPage, $normalizedType, $unreadOnly);
    }

    public function markAsRead(int $id): bool
    {
        return $this->notifications->markRead($id);
    }

    /**
     * Marca como lida e retorna o caminho interno para redirecionamento.
     */
    public function markAsReadAndGetRedirectPath(int $id): string
    {
        $notification = $this->notifications->findById($id);
        if ($notification === null)
        {
            throw new ValidationException(['id' => 'Notificação não encontrada.']);
        }

        if (!$notification->isRead())
        {
            $this->notifications->markRead($id);
        }

        $link = $notification->link_url;

        return self::sanitizeInternalPath($link);
    }

    public function markAllAsRead(): int
    {
        return $this->notifications->markAllRead();
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    public static function levelLabel(string $level): string
    {
        return self::LEVEL_LABELS[$level] ?? $level;
    }

    public static function levelBootstrapClass(string $level): string
    {
        return match ($level)
        {
            'danger' => 'danger',
            'info' => 'info',
            default => 'warning',
        };
    }

    /**
     * @param array{type: string, unread: string} $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $query = [];

        $type = self::normalizeTypeFilterStatic($filters['type'] ?? null);
        if ($type !== null)
        {
            $query['type'] = $type;
        }

        if (($filters['unread'] ?? '') === '1')
        {
            $query['unread'] = '1';
        }

        return $query;
    }

    private function syncLowStockAlerts(): void
    {
        $products = (new ProductRepository($this->notifications->getConnection()))->findLowStock();
        foreach ($products as $product)
        {
            $this->notifyLowStock($product);
        }
    }

    private function syncOverdueAccountAlerts(): void
    {
        $accountsRepo = new AccountsReceivableRepository($this->notifications->getConnection());
        $page = $accountsRepo->paginateFiltered(1, 100, null, null, null, null, true);

        foreach ($page['items'] as $account)
        {
            $this->notifyOverdueAccount($account);
        }
    }

    private function syncOverdueInstallmentAlerts(): void
    {
        $installmentsRepo = new InstallmentRepository($this->notifications->getConnection());
        $page = $installmentsRepo->paginateFiltered(1, 100, 'overdue', null, null, null);

        foreach ($page['items'] as $installment)
        {
            $this->notifyOverdueInstallment($installment);
        }
    }

    private function notifyOverdueAccount(AccountsReceivable $account): void
    {
        $dedupeKey = $this->dedupeKey('overdue_ar', 'accounts_receivable', $account->id);
        if ($this->notifications->existsByDedupeKey($dedupeKey))
        {
            return;
        }

        $this->notifications->insert([
            'type' => self::TYPE_OVERDUE_ACCOUNT,
            'title' => 'Conta vencida',
            'message' => sprintf(
                'Conta a receber #%d (pedido #%d) venceu em %s.',
                $account->id,
                $account->order_id,
                DateHelper::toBrDate($account->due_date)
            ),
            'entity_type' => 'accounts_receivable',
            'entity_id' => $account->id,
            'level' => 'danger',
            'link_url' => '/finance/accounts-receivable/show?id=' . $account->id,
            'dedupe_key' => $dedupeKey,
        ]);
    }

    private function notifyOverdueInstallment(Installment $installment): void
    {
        $dedupeKey = $this->dedupeKey('overdue_installment', 'installment', $installment->id);
        if ($this->notifications->existsByDedupeKey($dedupeKey))
        {
            return;
        }

        $this->notifications->insert([
            'type' => self::TYPE_OVERDUE_ACCOUNT,
            'title' => 'Parcela vencida',
            'message' => sprintf(
                'Parcela %d do pedido #%d venceu em %s (R$ %s).',
                $installment->installment_number,
                $installment->order_id,
                DateHelper::toBrDate($installment->due_date),
                number_format((float) $installment->amount, 2, ',', '.')
            ),
            'entity_type' => 'installment',
            'entity_id' => $installment->id,
            'level' => 'danger',
            'link_url' => '/finance/installments/overdue',
            'dedupe_key' => $dedupeKey,
        ]);
    }

    private function resolveLowStockAlert(int $productId): void
    {
        $this->notifications->deleteByDedupeKey(
            $this->dedupeKey('low_stock', 'product', $productId)
        );
    }

    private function dedupeKey(string $prefix, string $entityType, int $entityId): string
    {
        return $prefix . ':' . $entityType . ':' . $entityId;
    }

    private static function sanitizeInternalPath(?string $link): string
    {
        if ($link === null || trim($link) === '')
        {
            return 'notifications';
        }

        $path = ltrim(trim($link), '/');
        if (
            $path === ''
            || str_contains($path, '://')
            || str_starts_with($path, '//')
            || str_contains($path, '..')
            || preg_match('/[\r\n\x00]/', $path) === 1
        )
        {
            return 'notifications';
        }

        return $path;
    }

    private function shouldSyncOperationalAlerts(): bool
    {
        $now = time();
        $last = isset($_SESSION[self::SESSION_SYNC_KEY]) ? (int) $_SESSION[self::SESSION_SYNC_KEY] : 0;
        if (($now - $last) < self::SYNC_INTERVAL_SECONDS)
        {
            return false;
        }

        $_SESSION[self::SESSION_SYNC_KEY] = $now;

        return true;
    }

    private function lastSeenNotificationId(): int
    {
        return isset($_SESSION[self::SESSION_LAST_SEEN_KEY])
            ? max(0, (int) $_SESSION[self::SESSION_LAST_SEEN_KEY])
            : 0;
    }

    private function storeLastSeenNotificationId(int $id): void
    {
        $_SESSION[self::SESSION_LAST_SEEN_KEY] = max(0, $id);
    }

    private function normalizeTypeFilter(?string $type): ?string
    {
        return self::normalizeTypeFilterStatic($type);
    }

    private static function normalizeTypeFilterStatic(?string $type): ?string
    {
        if ($type === null || trim($type) === '')
        {
            return null;
        }

        $type = trim($type);

        return in_array($type, self::TYPES, true) ? $type : null;
    }
}
