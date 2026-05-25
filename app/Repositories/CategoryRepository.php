<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Category;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class CategoryRepository
{
    use CompanyScope;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, company_id, created_at
             FROM categories
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    public function findByName(string $name): ?Category
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, company_id, created_at
             FROM categories
             WHERE LOWER(name) = LOWER(:name) AND company_id = :company_id'
        );
        $stmt->execute([
            'name' => trim($name),
            'company_id' => $this->companyId(),
        ]);
        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    /**
     * @return list<Category>
     */
    public function allOrderedByName(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, company_id, created_at
             FROM categories
             WHERE company_id = :company_id
             ORDER BY name ASC'
        );
        $stmt->execute($this->companyParams());
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = Category::fromArray($row);
        }

        return $list;
    }

    public function insert(string $name, ?string $description): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, description, company_id)
             VALUES (:name, :description, :company_id)
             RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'company_id' => $this->companyId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, string $name, ?string $description): void
    {
        $stmt = $this->db->prepare(
            'UPDATE categories
             SET name = :name, description = :description
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'company_id' => $this->companyId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM categories WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $id,
            'company_id' => $this->companyId(),
        ]);
    }

    public function countProductsLinked(int $categoryId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products
             WHERE category_id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $categoryId,
            'company_id' => $this->companyId(),
        ]);

        return (int) $stmt->fetchColumn();
    }
}
