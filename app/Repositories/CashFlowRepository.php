<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\CashFlow;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class CashFlowRepository
{
    use CompanyScope;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function insert(
        string $type,
        string $amount,
        ?string $paymentMethod,
        ?string $referenceType,
        ?int $referenceId,
        ?string $description,
        string $occurredAt,
        ?int $createdBy
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cash_flow (
                type, amount, payment_method, reference_type, reference_id,
                description, occurred_at, created_by, company_id
             ) VALUES (
                :type, :amount, :method, :ref_type, :ref_id,
                :description, :occurred_at, :created_by, :company_id
             ) RETURNING id'
        );
        $stmt->execute([
            'type' => $type,
            'amount' => $amount,
            'method' => $paymentMethod,
            'ref_type' => $referenceType,
            'ref_id' => $referenceId,
            'description' => $description,
            'occurred_at' => $occurredAt,
            'created_by' => $createdBy,
            'company_id' => $this->companyId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: list<CashFlow>, total: int}
     */
    public function paginateFiltered(
        int $page,
        int $perPage,
        ?string $type,
        ?string $dateFrom,
        ?string $dateTo
    ): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ['cf.company_id = :company_id'];
        $params = $this->companyParams();

        if ($type !== null && $type !== '')
        {
            $where[] = 'cf.type = :type';
            $params['type'] = $type;
        }
        if ($dateFrom !== null && $dateFrom !== '')
        {
            $where[] = 'cf.occurred_at::date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '')
        {
            $where[] = 'cf.occurred_at::date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM cash_flow cf ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listSql = 'SELECT cf.id, cf.type, cf.amount, cf.payment_method, cf.reference_type, cf.reference_id,
                           cf.description, cf.occurred_at, cf.created_by, cf.created_at, u.name AS created_by_name
                    FROM cash_flow cf
                    LEFT JOIN users u ON u.id = cf.created_by
                    ' . $whereSql . '
                    ORDER BY cf.occurred_at DESC, cf.id DESC
                    LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row)
        {
            $items[] = CashFlow::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function netBalance(): string
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type = 'entrada' THEN amount ELSE -amount END), 0)
             FROM cash_flow
             WHERE company_id = :company_id"
        );
        $stmt->execute($this->companyParams());

        return (string) $stmt->fetchColumn();
    }

    public function sumByTypeBetween(string $type, string $from, string $to): string
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM cash_flow
             WHERE type = :type
               AND company_id = :company_id
               AND occurred_at::date >= :from
               AND occurred_at::date <= :to'
        );
        $stmt->execute([
            'type' => $type,
            'company_id' => $this->companyId(),
            'from' => $from,
            'to' => $to,
        ]);

        return (string) $stmt->fetchColumn();
    }
}
