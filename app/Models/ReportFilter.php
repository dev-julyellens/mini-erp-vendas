<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Filtros reutilizáveis para relatórios gerenciais.
 */
final class ReportFilter
{
    public const ORDER_STATUSES = ['paid', 'pending', 'canceled', 'refunded'];

    public ?string $dateFrom;
    public ?string $dateTo;
    public ?int $customerId;
    public ?int $productId;
    public ?int $categoryId;
    /** @var string|null Status de pedido; null = apenas pagos (paid). */
    public ?string $orderStatus;
    public ?string $cashFlowType;
    public int $page;

    public function __construct(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $customerId = null,
        ?int $productId = null,
        ?int $categoryId = null,
        ?string $orderStatus = null,
        ?string $cashFlowType = null,
        int $page = 1
    )
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->customerId = $customerId;
        $this->productId = $productId;
        $this->categoryId = $categoryId;
        $this->orderStatus = $orderStatus;
        $this->cashFlowType = $cashFlowType;
        $this->page = max(1, $page);
    }

    /** Status efetivo para consultas de vendas (padrão: paid). */
    public function effectiveOrderStatus(): string
    {
        if ($this->orderStatus !== null && in_array($this->orderStatus, self::ORDER_STATUSES, true))
        {
            return $this->orderStatus;
        }

        return 'paid';
    }
}
