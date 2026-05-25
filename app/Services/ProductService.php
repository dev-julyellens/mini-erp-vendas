<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Money;
use App\Helpers\ProductPricing;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;

final class ProductService
{
    private ProductRepository $products;
    private CategoryRepository $categories;

    public function __construct(
        ?ProductRepository $products = null,
        ?CategoryRepository $categories = null
    )
    {
        $this->products = $products ?? new ProductRepository();
        $this->categories = $categories ?? new CategoryRepository();
    }

    /**
     * @param array<string, string> $input
     * @return array{errors: array<string, string>, data: array<string, mixed>}
     */
    private function validate(array $input, ?int $excludeId = null): array
    {
        $errors = [];

        $name = trim($input['name'] ?? '');
        if ($name === '')
        {
            $errors['name'] = 'Nome é obrigatório.';
        }

        $sku = strtoupper(trim($input['sku'] ?? ''));
        if ($sku === '')
        {
            $errors['sku'] = 'SKU é obrigatório.';
        }
        elseif (!preg_match('/^[A-Z0-9][A-Z0-9._-]{1,49}$/', $sku))
        {
            $errors['sku'] = 'SKU inválido (use letras, números, ponto, hífen ou underscore).';
        }
        elseif ($this->products->findBySku($sku, $excludeId) !== null)
        {
            $errors['sku'] = 'SKU já cadastrado.';
        }

        $barcode = trim($input['barcode'] ?? '');
        $barcode = $barcode !== '' ? $barcode : null;
        if ($barcode !== null && mb_strlen($barcode) > 50)
        {
            $errors['barcode'] = 'Código de barras deve ter no máximo 50 caracteres.';
        }
        elseif ($barcode !== null && $this->products->findByBarcode($barcode, $excludeId) !== null)
        {
            $errors['barcode'] = 'Código de barras já cadastrado.';
        }

        $categoryId = (int) ($input['category_id'] ?? 0);
        $categoryId = $categoryId > 0 ? $categoryId : null;
        if ($categoryId !== null && $this->categories->findById($categoryId) === null)
        {
            $errors['category_id'] = 'Categoria inválida.';
        }

        $unit = strtoupper(trim($input['unit_of_measure'] ?? 'UN'));
        if (!ProductPricing::isValidUnit($unit))
        {
            $errors['unit_of_measure'] = 'Unidade de medida inválida.';
        }

        $type = trim($input['type'] ?? ProductPricing::TYPE_PRODUCT);
        if (!ProductPricing::isValidType($type))
        {
            $errors['type'] = 'Tipo inválido.';
        }

        $description = isset($input['description']) ? trim((string) $input['description']) : null;
        if ($description === '')
        {
            $description = null;
        }

        $costRaw = trim($input['cost_price'] ?? '0');
        if ($costRaw === '')
        {
            $costRaw = '0';
        }
        if (!Money::validateNonNegativeDecimal($costRaw))
        {
            $errors['cost_price'] = 'Preço de custo deve ser zero ou positivo.';
        }
        else
        {
            $costRaw = Money::normalizeDecimal($costRaw);
        }

        $priceRaw = trim($input['price'] ?? '');
        if (!Money::validatePositive($priceRaw))
        {
            $errors['price'] = 'Preço de venda deve ser maior que zero.';
        }
        else
        {
            $priceRaw = Money::normalizeDecimal($priceRaw);
        }

        $margins = !isset($errors['price'], $errors['cost_price'])
            ? ProductPricing::computeMargins($costRaw, $priceRaw)
            : ['margin' => null, 'markup' => null];

        $minStockRaw = trim($input['min_stock'] ?? '0');
        if (!Money::validateNonNegativeInt($minStockRaw))
        {
            $errors['min_stock'] = 'Estoque mínimo deve ser um inteiro não negativo.';
        }
        $minStock = (int) $minStockRaw;

        $stockRaw = trim($input['stock'] ?? '0');
        if (!Money::validateNonNegativeInt($stockRaw))
        {
            $errors['stock'] = 'Estoque deve ser um inteiro não negativo.';
        }
        $stock = (int) $stockRaw;

        $estimatedRaw = trim($input['estimated_time_minutes'] ?? '');
        $estimatedMinutes = null;
        if ($estimatedRaw !== '')
        {
            if (!Money::validateNonNegativeInt($estimatedRaw) || (int) $estimatedRaw <= 0)
            {
                $errors['estimated_time_minutes'] = 'Tempo estimado deve ser um inteiro positivo (minutos).';
            }
            else
            {
                $estimatedMinutes = (int) $estimatedRaw;
            }
        }

        if ($type === ProductPricing::TYPE_SERVICE)
        {
            $stock = 0;
            $minStock = 0;
        }
        else
        {
            $estimatedMinutes = null;
        }

        return [
            'errors' => $errors,
            'data' => [
                'name' => $name,
                'description' => $description,
                'sku' => $sku,
                'barcode' => $barcode,
                'category_id' => $categoryId,
                'unit_of_measure' => $unit,
                'cost_price' => $costRaw,
                'margin_percent' => $margins['margin'],
                'markup_percent' => $margins['markup'],
                'price' => $priceRaw,
                'stock' => $stock,
                'min_stock' => $minStock,
                'type' => $type,
                'estimated_time_minutes' => $estimatedMinutes,
            ],
        ];
    }

    /**
     * @param array<string, string> $input
     */
    public function create(array $input): int
    {
        $v = $this->validate($input);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $data = $v['data'];
        $initialStock = (int) $data['stock'];
        $data['stock'] = 0;

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try
        {
            $productRepo = new ProductRepository($pdo);
            $id = $productRepo->insert($data);

            if (
                $data['type'] === ProductPricing::TYPE_PRODUCT
                && $initialStock > 0
            )
            {
                $stockService = new StockService(
                    new StockMovementRepository($pdo),
                    $productRepo,
                    $pdo
                );
                $stockService->apply(
                    'entrada',
                    $id,
                    $initialStock,
                    'product',
                    $id,
                    'Estoque inicial',
                    null,
                    false
                );
            }

            $pdo->commit();
        }
        catch (\PDOException $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23505')
            {
                throw new ValidationException(['sku' => 'SKU ou código de barras já cadastrado.']);
            }
            throw $e;
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }

        $created = $this->products->findById($id);
        if ($created !== null)
        {
            Audit::record('criar', 'produtos', $id, null, AuditService::productSnapshot($created));
        }

        return $id;
    }

    /**
     * @param array<string, string> $input
     */
    public function update(int $id, array $input): void
    {
        $existing = $this->products->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Produto não encontrado.']);
        }

        $v = $this->validate($input, $id);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $data = $v['data'];
        $isService = $data['type'] === ProductPricing::TYPE_SERVICE;
        $targetStock = $isService ? 0 : (int) $data['stock'];

        $oldSnapshot = AuditService::productSnapshot($existing);

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try
        {
            $productRepo = new ProductRepository($pdo);
            $stockService = new StockService(
                new StockMovementRepository($pdo),
                $productRepo,
                $pdo
            );

            if (
                !$existing->isService()
                && $isService
                && $existing->stock > 0
            )
            {
                $stockService->applyAbsoluteStock(
                    $id,
                    0,
                    'ajuste',
                    'Zeragem de estoque ao converter para serviço',
                    false
                );
            }

            $data['stock'] = $isService ? 0 : $existing->stock;
            $data['min_stock'] = $isService ? 0 : (int) $data['min_stock'];

            $productRepo->update($id, $data);

            if (
                !$isService
                && $existing->stock !== $targetStock
            )
            {
                $stockService->applyAbsoluteStock(
                    $id,
                    $targetStock,
                    'ajuste',
                    'Ajuste manual via cadastro de produto',
                    false
                );
            }

            $pdo->commit();
        }
        catch (\PDOException $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23505')
            {
                throw new ValidationException(['sku' => 'SKU ou código de barras já cadastrado.']);
            }
            throw $e;
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }

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
            throw new ValidationException(['id' => 'Produto não encontrado.']);
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
                throw new ValidationException(['id' => 'Não é possível excluir produto vinculado a vendas.']);
            }
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23505')
            {
                throw new ValidationException(['sku' => 'SKU ou código de barras já cadastrado.']);
            }
            throw $e;
        }
    }
}
