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
        foreach ($products as $p) {
            $data[] = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'price' => $p->price,
                'stock' => $p->stock,
                'low_stock' => $p->stock < 5,
            ];
        }

        $this->json(['data' => $data]);
    }
}
