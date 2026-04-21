<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Product;
use PDO;

final class ProductRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id, bool $forUpdate = false): ?Product
    {
        $sql = 'SELECT * FROM products WHERE id = :id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Product::fromArray($row) : null;
    }

    /**
     * @return list<Product>
     */
    public function allOrderedByName(): array
    {
        $stmt = $this->db->query('SELECT * FROM products ORDER BY name ASC');
        $rows = $stmt->fetchAll();
        $list = [];
        foreach ($rows as $row) {
            $list[] = Product::fromArray($row);
        }
        return $list;
    }

    /**
     * @return array{items: list<Product>, total: int}
     */
    public function paginate(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT * FROM products ORDER BY name ASC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = Product::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function insert(string $name, ?string $description, string $price, int $stock): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (name, description, price, stock)
             VALUES (:name, :description, :price, :stock) RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, string $name, ?string $description, string $price, int $stock): void
    {
        $stmt = $this->db->prepare(
            'UPDATE products SET name = :name, description = :description, price = :price, stock = :stock
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function decrementStock(int $productId, int $quantity): void
    {
        $stmt = $this->db->prepare(
            'UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty2'
        );
        $stmt->execute([
            'qty' => $quantity,
            'id' => $productId,
            'qty2' => $quantity,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Stock update failed for product ' . $productId);
        }
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    /**
     * @return list<Product>
     */
    public function findLowStock(int $threshold): array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE stock < :t ORDER BY stock ASC, name ASC');
        $stmt->execute(['t' => $threshold]);
        $rows = $stmt->fetchAll();
        $list = [];
        foreach ($rows as $row) {
            $list[] = Product::fromArray($row);
        }
        return $list;
    }
}
