<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use App\Helpers\Auth;
use App\Core\Database;
use App\Models\StockMovement;
use App\Core\ValidationException;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;

final class StockService
{
    /** @var list<string> */
    public const TYPES = ['entrada', 'saida', 'ajuste', 'devolucao', 'perda', 'inventario'];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        'entrada' => 'Entrada',
        'saida' => 'Saída',
        'ajuste' => 'Ajuste',
        'devolucao' => 'Devolução',
        'perda' => 'Perda',
        'inventario' => 'Inventário',
    ];

    /** Tipos em que quantity é sempre positiva (efeito definido pelo tipo). */
    private const POSITIVE_QUANTITY_TYPES = ['entrada', 'saida', 'devolucao', 'perda'];

    private StockMovementRepository $movements;
    private ProductRepository $products;

    public function __construct(
        ?StockMovementRepository $movements = null,
        ?ProductRepository $products = null,
        ?PDO $pdo = null
    )
    {
        $pdo = $pdo ?? Database::getConnection();
        $this->movements = $movements ?? new StockMovementRepository($pdo);
        $this->products = $products ?? new ProductRepository($pdo);
    }

    /**
     * Registra movimentação e atualiza products.stock na mesma transação.
     *
     * @param string $type entrada|saida|ajuste|devolucao|perda|inventario
     */
    public function apply(
        string $type,
        int $productId,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null,
        bool $manageTransaction = true,
        bool $lockProduct = true
    ): int
    {
        $type = $this->normalizeType($type);
        $this->validateQuantityForType($type, $quantity);

        $delta = $this->quantityToDelta($type, $quantity);
        $storedQuantity = in_array($type, self::POSITIVE_QUANTITY_TYPES, true)
            ? abs($quantity)
            : $quantity;

        $pdo = $this->products->getConnection();

        if ($manageTransaction && !$pdo->inTransaction())
        {
            $movementId = Database::transaction(function (PDO $pdo) use (
                $type,
                $productId,
                $quantity,
                $referenceType,
                $referenceId,
                $notes,
                $createdBy,
                $lockProduct
            ): int
            {
                return $this->apply(
                    $type,
                    $productId,
                    $quantity,
                    $referenceType,
                    $referenceId,
                    $notes,
                    $createdBy,
                    false,
                    $lockProduct
                );
            });

            $updatedProduct = $this->products->findById($productId, false);
            if ($updatedProduct !== null && !$updatedProduct->isService())
            {
                (new NotificationService(null, $pdo))->notifyLowStock($updatedProduct);
            }

            return $movementId;
        }

        $product = $this->products->findById($productId, $lockProduct);
        if ($product === null)
        {
            throw new ValidationException(['product_id' => 'Produto não encontrado.']);
        }

        if ($product->isService())
        {
            throw new ValidationException([
                'product_id' => 'Serviços não possuem movimentação de estoque.',
            ]);
        }

        if ($delta < 0 && $product->stock + $delta < 0)
        {
            throw new ValidationException([
                'quantity' => sprintf(
                    'Estoque insuficiente para "%s". Disponível: %d.',
                    $product->name,
                    $product->stock
                ),
            ]);
        }

        $movementId = $this->movements->insert(
            $productId,
            $type,
            $storedQuantity,
            $referenceType,
            $referenceId,
            $notes,
            $createdBy ?? Auth::id()
        );

        $this->products->adjustStock($productId, $delta);

        return $movementId;
    }

    public function applyAbsoluteStock(
        int $productId,
        int $targetStock,
        string $movementType = 'ajuste',
        ?string $notes = null,
        bool $manageTransaction = true
    ): int
    {
        if ($targetStock < 0)
        {
            throw new ValidationException(['stock' => 'O estoque não pode ser negativo.']);
        }

        $product = $this->products->findById($productId, false);
        if ($product === null)
        {
            throw new ValidationException(['product_id' => 'Produto não encontrado.']);
        }

        if ($product->isService())
        {
            throw new ValidationException([
                'product_id' => 'Serviços não possuem movimentação de estoque.',
            ]);
        }

        $delta = $targetStock - $product->stock;
        if ($delta === 0)
        {
            throw new ValidationException(['stock' => 'Stock is already at the informed value.']);
        }

        $type = $this->normalizeType($movementType);

        return $this->apply(
            $type,
            $productId,
            $delta,
            'product',
            $productId,
            $notes,
            null,
            $manageTransaction,
            true
        );
    }

    /**
     * Movimentação manual pela tela administrativa.
     */
    public function registerManual(
        int $productId,
        string $type,
        int $quantity,
        ?string $notes = null
    ): int
    {
        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '')
        {
            $notes = null;
        }

        return $this->apply(
            $type,
            $productId,
            $quantity,
            'manual',
            null,
            $notes
        );
    }

    public function registerReturn(
        int $productId,
        int $quantity,
        int $orderId,
        ?string $notes = null,
        bool $manageTransaction = true,
        ?PDO $pdo = null
    ): int
    {
        if ($pdo !== null)
        {
            $service = new self(
                new StockMovementRepository($pdo),
                new ProductRepository($pdo),
                $pdo
            );

            return $service->apply(
                'devolucao',
                $productId,
                $quantity,
                'order',
                $orderId,
                $notes ?? 'Devolução por cancelamento da venda #' . $orderId,
                null,
                $manageTransaction,
                true
            );
        }

        return $this->apply(
            'devolucao',
            $productId,
            $quantity,
            'order',
            $orderId,
            $notes ?? 'Devolução por cancelamento da venda #' . $orderId,
            null,
            $manageTransaction,
            true
        );
    }

    /**
     * @return array{items: list<StockMovement>, total: int}
     */
    public function searchMovements(
        ?int $productId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $type,
        int $page,
        int $perPage
    ): array
    {
        $result = $this->movements->search(
            $productId,
            $this->normalizeDateStart($dateFrom),
            $this->normalizeDateEnd($dateTo),
            $this->normalizeTypeFilter($type),
            $page,
            $perPage
        );

        return [
            'items' => $result['items'],
            'total' => $result['total'],
        ];
    }

    /**
     * @param array{product_id: ?int, date_from: string, date_to: string, type: string} $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $query = [];

        if ($filters['product_id'] !== null && $filters['product_id'] > 0)
        {
            $query['product_id'] = (string) $filters['product_id'];
        }

        if ($filters['date_from'] !== '')
        {
            $query['date_from'] = $filters['date_from'];
        }

        if ($filters['date_to'] !== '')
        {
            $query['date_to'] = $filters['date_to'];
        }

        $type = self::normalizeTypeFilterStatic($filters['type']);
        if ($type !== null)
        {
            $query['type'] = $type;
        }

        return $query;
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    public static function signedQuantityDisplay(StockMovement $movement): string
    {
        $qty = $movement->quantity;
        if (in_array($movement->type, self::POSITIVE_QUANTITY_TYPES, true))
        {
            $sign = in_array($movement->type, ['entrada', 'devolucao'], true) ? '+' : '-';

            return $sign . abs($qty);
        }

        return $qty > 0 ? '+' . $qty : (string) $qty;
    }

    private function quantityToDelta(string $type, int $quantity): int
    {
        return match ($type)
        {
            'entrada', 'devolucao' => abs($quantity),
            'saida', 'perda' => -abs($quantity),
            'ajuste', 'inventario' => $quantity,
            default => throw new \InvalidArgumentException('Unknown movement type: ' . $type),
        };
    }

    private function validateQuantityForType(string $type, int $quantity): void
    {
        if ($quantity === 0)
        {
            throw new ValidationException(['quantity' => 'A quantidade não pode ser zero.']);
        }

        if (in_array($type, self::POSITIVE_QUANTITY_TYPES, true) && $quantity < 0)
        {
            throw new ValidationException(['quantity' => 'A quantidade deve ser positiva para este tipo de movimentação.']);
        }
    }

    private function normalizeType(string $type): string
    {
        $type = trim(strtolower($type));

        if (!in_array($type, self::TYPES, true))
        {
            throw new ValidationException(['type' => 'Tipo de movimentação inválido.']);
        }

        return $type;
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

        $type = trim(strtolower($type));

        return in_array($type, self::TYPES, true) ? $type : null;
    }

    private function normalizeDateStart(?string $date): ?string
    {
        if ($date === null || trim($date) === '')
        {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($date));

        return $parsed === false ? null : $parsed->format('Y-m-d 00:00:00');
    }

    private function normalizeDateEnd(?string $date): ?string
    {
        if ($date === null || trim($date) === '')
        {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($date));

        return $parsed === false ? null : $parsed->format('Y-m-d 23:59:59');
    }
}
