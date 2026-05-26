<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\OrderItem;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class OrderItemRepository
{
    use CompanyScope;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function insert(int $orderId, int $productId, int $quantity, string $unitPrice, string $subtotal): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
             VALUES (:order_id, :product_id, :quantity, :unit_price, :subtotal)'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ]);
    }

    /**
     * @return list<OrderItem>
     */
    public function findByOrderId(int $orderId): array
    {
        $sql = 'SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price, oi.subtotal,
                       p.name AS product_name, p.type AS product_type
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id AND o.company_id = :company_id
                INNER JOIN products p ON p.id = oi.product_id AND p.company_id = :company_id
                WHERE oi.order_id = :order_id
                ORDER BY oi.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id' => $orderId,
            'company_id' => $this->companyId(),
        ]);
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = OrderItem::fromArray($row);
        }

        return $list;
    }

    /**
     * Carrega itens de vários pedidos em uma consulta (evita N+1 na API).
     *
     * @param list<int> $orderIds
     * @return array<int, list<OrderItem>> Chave = order_id
     */
    public function findByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(
            array_map('intval', $orderIds),
            static fn(int $id): bool => $id > 0
        )));

        if ($orderIds === [])
        {
            return [];
        }

        $in = implode(',', $orderIds);
        $sql = 'SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.unit_price, oi.subtotal,
                       p.name AS product_name, p.type AS product_type
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id AND o.company_id = :company_id
                INNER JOIN products p ON p.id = oi.product_id AND p.company_id = :company_id
                WHERE oi.order_id IN (' . $in . ')
                ORDER BY oi.order_id ASC, oi.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $this->companyId()]);

        /** @var array<int, list<OrderItem>> $grouped */
        $grouped = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $orderId = (int) $row['order_id'];
            $grouped[$orderId][] = OrderItem::fromArray($row);
        }

        return $grouped;
    }
}
