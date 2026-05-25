<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Money;
use App\Repositories\ProductRepository;

final class ProductService
{
    private ProductRepository $products;

    public function __construct(?ProductRepository $products = null)
    {
        $this->products = $products ?? new ProductRepository();
    }

    /**
     * @return array{errors: array<string, string>, name: string, description: ?string, price: string, stock: int}
     */
    private function validate(string $name, ?string $description, string $priceRaw, string $stockRaw): array
    {
        $errors = [];
        $name = trim($name);
        if ($name === '')
        {
            $errors['name'] = 'Name is required.';
        }

        $description = $description !== null ? trim($description) : null;
        if ($description === '')
        {
            $description = null;
        }

        $priceRaw = trim($priceRaw);
        if (!Money::validatePositive($priceRaw))
        {
            $errors['price'] = 'Price must be greater than zero.';
        }

        $stockRaw = trim($stockRaw);
        if (!Money::validateNonNegativeInt($stockRaw))
        {
            $errors['stock'] = 'Stock must be a non-negative integer.';
        }

        return [
            'errors' => $errors,
            'name' => $name,
            'description' => $description,
            'price' => $priceRaw,
            'stock' => (int) $stockRaw,
        ];
    }

    public function create(string $name, ?string $description, string $priceRaw, string $stockRaw): int
    {
        $v = $this->validate($name, $description, $priceRaw, $stockRaw);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $id = $this->products->insert($v['name'], $v['description'], $v['price'], $v['stock']);
        $created = $this->products->findById($id);
        if ($created !== null)
        {
            Audit::record('criar', 'produtos', $id, null, AuditService::productSnapshot($created));
        }

        return $id;
    }

    public function update(int $id, string $name, ?string $description, string $priceRaw, string $stockRaw): void
    {
        $existing = $this->products->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Product not found.']);
        }

        $v = $this->validate($name, $description, $priceRaw, $stockRaw);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $oldSnapshot = AuditService::productSnapshot($existing);
        $this->products->update($id, $v['name'], $v['description'], $v['price'], $v['stock']);
        $updated = $this->products->findById($id);
        if ($updated !== null)
        {
            Audit::record('editar', 'produtos', $id, $oldSnapshot, AuditService::productSnapshot($updated));
            if ($oldSnapshot['stock'] !== $updated->stock)
            {
                Audit::record(
                    'editar',
                    'estoque',
                    $id,
                    ['product_id' => $id, 'stock' => $oldSnapshot['stock']],
                    ['product_id' => $id, 'stock' => $updated->stock]
                );
            }
        }
    }

    public function delete(int $id): void
    {
        $existing = $this->products->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Product not found.']);
        }

        $oldSnapshot = AuditService::productSnapshot($existing);

        try
        {
            $this->products->delete($id);
            Audit::record('excluir', 'produtos', $id, $oldSnapshot, null);
        }
        catch (\PDOException $e)
        {
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23503')
            {
                throw new ValidationException(['id' => 'Cannot delete product linked to sales.']);
            }
            throw $e;
        }
    }
}
