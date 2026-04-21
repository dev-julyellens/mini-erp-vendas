<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Core\Database;
use App\Models\Customer;

final class CustomerRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Customer::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?Customer
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE LOWER(email) = LOWER(:email)');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? Customer::fromArray($row) : null;
    }

    /**
     * @return list<Customer>
     */
    public function allOrderedByName(): array
    {
        $stmt = $this->db->query('SELECT * FROM customers ORDER BY name ASC');
        $rows = $stmt->fetchAll();
        $list = [];
        foreach ($rows as $row)
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

        $countStmt = $this->db->query('SELECT COUNT(*) FROM customers');
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT * FROM customers ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row)
        {
            $items[] = Customer::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function insert(string $name, string $email, ?string $phone): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO customers (name, email, phone) VALUES (:name, :email, :phone) RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, string $name, string $email, ?string $phone): void
    {
        $stmt = $this->db->prepare(
            'UPDATE customers SET name = :name, email = :email, phone = :phone WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function emailExistsForOther(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM customers WHERE LOWER(email) = LOWER(:email) AND id <> :id'
        );
        $stmt->execute(['email' => $email, 'id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    }
}
