<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Helpers\ProductPricing;
use App\Models\Product;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class ProductRepository
{
    use CompanyScope;
    private const SELECT_COLUMNS = '
        p.id, p.name, p.description, p.sku, p.barcode, p.category_id,
        p.unit_of_measure, p.cost_price, p.margin_percent, p.markup_percent,
        p.price, p.stock, p.min_stock, p.type, p.estimated_time_minutes,
        c.name AS category_name';

    private const FROM_JOIN = ' FROM products p LEFT JOIN categories c ON c.id = p.category_id AND c.company_id = p.company_id ';

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id, bool $forUpdate = false): ?Product
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN
            . ' WHERE p.id = :id AND p.company_id = :company_id';
        if ($forUpdate)
        {
            $sql .= ' FOR UPDATE OF p';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    public function findBySku(string $sku, ?int $excludeId = null): ?Product
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN
            . ' WHERE UPPER(p.sku) = UPPER(:sku) AND p.company_id = :company_id';
        $params = ['sku' => trim($sku), 'company_id' => $this->companyId()];
        if ($excludeId !== null)
        {
            $sql .= ' AND p.id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    public function findByBarcode(string $barcode, ?int $excludeId = null): ?Product
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN
            . ' WHERE p.barcode = :barcode AND p.company_id = :company_id';
        $params = ['barcode' => trim($barcode), 'company_id' => $this->companyId()];
        if ($excludeId !== null)
        {
            $sql .= ' AND p.id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? Product::fromArray($row) : null;
    }

    /**
     * @return list<Product>
     */
    public function allOrderedByName(): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN
                . ' WHERE p.company_id = :company_id ORDER BY p.name ASC'
        );
        $stmt->execute($this->companyParams());

        return $this->mapRows($stmt->fetchAll());
    }

    /**
     * Produtos físicos (exclui serviços) para inventário.
     *
     * @return list<Product>
     */
    public function listPhysicalProductsOrderedByName(): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN
                . " WHERE p.company_id = :company_id AND p.type = 'product' ORDER BY p.name ASC"
        );
        $stmt->execute($this->companyParams());

        return $this->mapRows($stmt->fetchAll());
    }

    /**
     * @param array{q?: string, category_id?: int, type?: string, low_stock?: bool} $filters
     * @return array{items: list<Product>, total: int}
     */
    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = ['p.company_id = :company_id'];
        $params = $this->companyParams();

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '')
        {
            $where[] = "(p.name || ' ' || p.sku || ' ' || COALESCE(p.barcode, '')) ILIKE :q ESCAPE '\\'";
            $params['q'] = '%' . self::escapeIlike($q) . '%';
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0)
        {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '' && ProductPricing::isValidType($type))
        {
            $where[] = 'p.type = :type';
            $params['type'] = $type;
        }

        if (!empty($filters['low_stock']))
        {
            $where[] = "p.type = 'product' AND p.stock < p.min_stock";
        }

        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM products p' . $whereSql);
        $this->bindNamedParams($countStmt, $params);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN . $whereSql
            . ' ORDER BY p.name ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        $this->bindNamedParams($stmt, $params);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $this->mapRows($stmt->fetchAll()), 'total' => $total];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (
                name, description, sku, barcode, category_id, unit_of_measure,
                cost_price, margin_percent, markup_percent, price, stock, min_stock, type,
                estimated_time_minutes, company_id
             ) VALUES (
                :name, :description, :sku, :barcode, :category_id, :unit_of_measure,
                :cost_price, :margin_percent, :markup_percent, :price, :stock, :min_stock, :type,
                :estimated_time_minutes, :company_id
             ) RETURNING id'
        );
        $data['company_id'] = $this->companyId();
        $stmt->execute($data);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE products SET
                name = :name,
                description = :description,
                sku = :sku,
                barcode = :barcode,
                category_id = :category_id,
                unit_of_measure = :unit_of_measure,
                cost_price = :cost_price,
                margin_percent = :margin_percent,
                markup_percent = :markup_percent,
                price = :price,
                stock = :stock,
                min_stock = :min_stock,
                type = :type,
                estimated_time_minutes = :estimated_time_minutes
             WHERE id = :id AND company_id = :company_id'
        );
        $data['company_id'] = $this->companyId();
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id AND company_id = :company_id');
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    public function decrementStock(int $productId, int $quantity): void
    {
        $this->adjustStock($productId, -abs($quantity));
    }

    public function incrementStock(int $productId, int $quantity): void
    {
        $this->adjustStock($productId, abs($quantity));
    }

    public function adjustStock(int $productId, int $delta): void
    {
        if ($delta === 0)
        {
            return;
        }

        if ($delta > 0)
        {
            $stmt = $this->db->prepare(
                'UPDATE products SET stock = stock + :qty WHERE id = :id AND company_id = :company_id'
            );
            $stmt->execute([
                'qty' => $delta,
                'id' => $productId,
                'company_id' => $this->companyId(),
            ]);
        }
        else
        {
            $qty = abs($delta);
            $stmt = $this->db->prepare(
                'UPDATE products SET stock = stock - :qty
                 WHERE id = :id AND stock >= :qty2 AND company_id = :company_id'
            );
            $stmt->execute([
                'qty' => $qty,
                'id' => $productId,
                'qty2' => $qty,
                'company_id' => $this->companyId(),
            ]);
        }

        if ($stmt->rowCount() === 0)
        {
            throw new \RuntimeException('Stock update failed for product ' . $productId);
        }
    }

    public function countAll(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE company_id = :company_id');
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<Product>
     */
    public function findLowStock(): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOIN
                . " WHERE p.company_id = :company_id AND p.type = 'product'
                   AND p.stock < p.min_stock ORDER BY p.stock ASC, p.name ASC"
        );
        $stmt->execute($this->companyParams());

        return $this->mapRows($stmt->fetchAll());
    }

    private static function escapeIlike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function bindNamedParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value)
        {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<Product>
     */
    private function mapRows(array $rows): array
    {
        $list = [];
        foreach ($rows as $row)
        {
            $list[] = Product::fromArray($row);
        }

        return $list;
    }
}
