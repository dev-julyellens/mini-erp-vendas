<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Category;
use PDO;

final class CategoryRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, created_at FROM categories WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    public function findByName(string $name): ?Category
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, created_at FROM categories WHERE LOWER(name) = LOWER(:name)'
        );
        $stmt->execute(['name' => trim($name)]);
        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    /**
     * @return list<Category>
     */
    public function allOrderedByName(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, description, created_at FROM categories ORDER BY name ASC'
        );
        $rows = $stmt->fetchAll();
        $list = [];
        foreach ($rows as $row)
        {
            $list[] = Category::fromArray($row);
        }

        return $list;
    }

    public function insert(string $name, ?string $description): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, description) VALUES (:name, :description) RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'description' => $description,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, string $name, ?string $description): void
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET name = :name, description = :description WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countProductsLinked(int $categoryId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products WHERE category_id = :id'
        );
        $stmt->execute(['id' => $categoryId]);

        return (int) $stmt->fetchColumn();
    }
}
