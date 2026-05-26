<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Product;

/**
 * DTO público de produto para API (sem custo/margem).
 */
final class ApiProductPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'category_id' => $product->categoryId,
            'category_name' => $product->categoryName,
            'unit_of_measure' => $product->unitOfMeasure,
            'price' => $product->price,
            'stock' => $product->stock,
            'min_stock' => $product->minStock,
            'type' => $product->type,
            'estimated_time_minutes' => $product->estimatedTimeMinutes,
            'estimated_time_label' => $product->estimatedTimeLabel(),
            'low_stock' => $product->isLowStock(),
        ];
    }
}
