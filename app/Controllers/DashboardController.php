<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $orders = new OrderRepository();
        $products = new ProductRepository();
        $customers = new CustomerRepository();

        $this->view('dashboard/index', [
            'ordersCount' => $orders->countAll(),
            'productsCount' => $products->countAll(),
            'customersCount' => $customers->countAll(),
            'lowStockProducts' => $products->findLowStock(),
            'flash' => Flash::pull(),
        ]);
    }
}
