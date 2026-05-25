<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\ProductPricing;

final class Product
{
    public int $id;
    public string $name;
    public ?string $description;
    public string $sku;
    public ?string $barcode;
    public ?int $categoryId;
    public ?string $categoryName;
    public string $unitOfMeasure;
    public string $costPrice;
    public ?string $marginPercent;
    public ?string $markupPercent;
    public string $price;
    public int $stock;
    public int $minStock;
    public string $type;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->name = (string) $row['name'];
        $m->description = isset($row['description']) && $row['description'] !== null
            ? (string) $row['description']
            : null;
        $m->sku = (string) ($row['sku'] ?? '');
        $m->barcode = isset($row['barcode']) && $row['barcode'] !== null && $row['barcode'] !== ''
            ? (string) $row['barcode']
            : null;
        $m->categoryId = isset($row['category_id']) && $row['category_id'] !== null
            ? (int) $row['category_id']
            : null;
        $m->categoryName = isset($row['category_name']) && $row['category_name'] !== null
            ? (string) $row['category_name']
            : null;
        $m->unitOfMeasure = (string) ($row['unit_of_measure'] ?? 'UN');
        $m->costPrice = (string) ($row['cost_price'] ?? '0');
        $m->marginPercent = isset($row['margin_percent']) && $row['margin_percent'] !== null
            ? (string) $row['margin_percent']
            : null;
        $m->markupPercent = isset($row['markup_percent']) && $row['markup_percent'] !== null
            ? (string) $row['markup_percent']
            : null;
        $m->price = (string) $row['price'];
        $m->stock = (int) $row['stock'];
        $m->minStock = (int) ($row['min_stock'] ?? 5);
        $m->type = (string) ($row['type'] ?? ProductPricing::TYPE_PRODUCT);

        return $m;
    }

    public function isService(): bool
    {
        return $this->type === ProductPricing::TYPE_SERVICE;
    }

    public function isLowStock(): bool
    {
        if ($this->isService())
        {
            return false;
        }

        return $this->stock < $this->minStock;
    }

    public function typeLabel(): string
    {
        return $this->isService() ? 'Serviço' : 'Produto';
    }
}
