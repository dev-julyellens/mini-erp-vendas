<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Customer;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class CustomerRepository
{
    use CompanyScope;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, company_id, created_at
             FROM customers
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Customer::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?Customer
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, company_id, created_at
             FROM customers
             WHERE LOWER(email) = LOWER(:email) AND company_id = :company_id'
        );
        $stmt->execute([
            'email' => $email,
            'company_id' => $this->companyId(),
        ]);
        $row = $stmt->fetch();

        return $row ? Customer::fromArray($row) : null;
    }

    /**
     * @return list<Customer>
     */
    public function allOrderedByName(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, company_id, created_at
             FROM customers
             WHERE company_id = :company_id
             ORDER BY name ASC'
        );
        $stmt->execute($this->companyParams());
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = Customer::fromArray($row);
        }

        return $list;
    }

    /**
     * @return array{items: list<Customer>, total: int}
     */
    public function paginate(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $params = $this->companyParams();

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM customers WHERE company_id = :company_id');
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, company_id, created_at
             FROM customers
             WHERE company_id = :company_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = Customer::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function insert(string $name, string $email, ?string $phone): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO customers (name, email, phone, company_id)
             VALUES (:name, :email, :phone, :company_id)
             RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company_id' => $this->companyId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, string $name, string $email, ?string $phone): void
    {
        $stmt = $this->db->prepare(
            'UPDATE customers
             SET name = :name, email = :email, phone = :phone
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company_id' => $this->companyId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM customers WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $id,
            'company_id' => $this->companyId(),
        ]);
    }

    public function emailExistsForOther(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM customers
             WHERE LOWER(email) = LOWER(:email)
               AND id <> :id
               AND company_id = :company_id'
        );
        $stmt->execute([
            'email' => $email,
            'id' => $excludeId,
            'company_id' => $this->companyId(),
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countAll(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM customers WHERE company_id = :company_id');
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }
}
