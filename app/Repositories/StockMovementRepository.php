<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\StockMovement;
use PDO;

final class StockMovementRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function insert(
        int $productId,
        string $type,
        int $quantity,
        ?string $referenceType,
        ?int $referenceId,
        ?string $notes,
        ?int $createdBy
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO stock_movements (
                product_id, type, quantity, reference_type, reference_id, notes, created_by
             ) VALUES (
                :product_id, :type, :quantity, :reference_type, :reference_id, :notes, :created_by
             ) RETURNING id'
        );
        $stmt->execute([
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: list<StockMovement>, total: int}
     */
    public function search(
        ?int $productId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $type,
        int $page,
        int $perPage
    ): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ['1 = 1'];
        $params = [];

        if ($productId !== null)
        {
            $where[] = 'm.product_id = :product_id';
            $params['product_id'] = $productId;
        }

        if ($dateFrom !== null && $dateFrom !== '')
        {
            $where[] = 'm.created_at >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== null && $dateTo !== '')
        {
            $where[] = 'm.created_at <= :date_to';
            $params['date_to'] = $dateTo;
        }

        if ($type !== null && $type !== '')
        {
            $where[] = 'm.type = :type';
            $params['type'] = $type;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM stock_movements m WHERE ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT m.id, m.product_id, m.type, m.quantity, m.reference_type, m.reference_id,
                       m.notes, m.created_by, m.created_at,
                       p.name AS product_name,
                       u.name AS user_name, u.email AS user_email
                FROM stock_movements m
                INNER JOIN products p ON p.id = m.product_id
                LEFT JOIN users u ON u.id = m.created_by
                WHERE ' . $whereSql . '
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value)
        {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = StockMovement::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }
}
