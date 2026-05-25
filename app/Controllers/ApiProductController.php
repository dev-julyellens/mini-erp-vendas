<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ProductRepository;

final class ApiProductController extends Controller
{
    public function index(): void
    {
        $repo = new ProductRepository();
        $products = $repo->allOrderedByName();

        $data = [];
        foreach ($products as $p)
        {
            $data[] = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'category_id' => $p->categoryId,
                'category_name' => $p->categoryName,
                'unit_of_measure' => $p->unitOfMeasure,
                'cost_price' => $p->costPrice,
                'margin_percent' => $p->marginPercent,
                'markup_percent' => $p->markupPercent,
                'price' => $p->price,
                'stock' => $p->stock,
                'min_stock' => $p->minStock,
                'type' => $p->type,
                'low_stock' => $p->isLowStock(),
            ];
        }

        $this->json(['data' => $data]);
    }
}
