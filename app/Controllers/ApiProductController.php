<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\ApiProductPresenter;
use App\Repositories\ProductRepository;

final class ApiProductController extends Controller
{
    public function index(): void
    {
        $repo = new ProductRepository();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 50)));

        $result = $repo->paginate($page, $perPage, []);

        $data = [];
        foreach ($result['items'] as $product)
        {
            $data[] = ApiProductPresenter::toArray($product);
        }

        $this->json([
            'data' => $data,
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);
    }
}
