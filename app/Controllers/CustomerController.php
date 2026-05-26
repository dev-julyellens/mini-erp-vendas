<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\CustomerRepository;
use App\Services\CustomerService;

final class CustomerController extends Controller
{
    private const PER_PAGE = 8;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $repo = new CustomerRepository();
        $result = $repo->paginate($page, self::PER_PAGE);

        $this->view('customers/index', [
            'customers' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->view('customers/form', [
            'customer' => null,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $service = new CustomerService();
        try
        {
            $service->create(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                isset($_POST['phone']) ? (string) $_POST['phone'] : null
            );
            Flash::success('Customer created successfully.');
            $this->redirect('/customers');
        }
        catch (ValidationException $e)
        {
            $this->view('customers/form', [
                'customer' => null,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new CustomerRepository();
        $customer = $repo->findById($id);
        if ($customer === null)
        {
            Flash::error('Cliente não encontrado.');
            $this->redirect('/customers');
        }

        $this->view('customers/form', [
            'customer' => $customer,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new CustomerService();
        try
        {
            $service->update(
                $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                isset($_POST['phone']) ? (string) $_POST['phone'] : null
            );
            Flash::success('Customer updated successfully.');
            $this->redirect('/customers');
        }
        catch (ValidationException $e)
        {
            $repo = new CustomerRepository();
            $customer = $repo->findById($id);
            $this->view('customers/form', [
                'customer' => $customer,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new CustomerService();
        try
        {
            $service->delete($id);
            Flash::success('Customer removed.');
        }
        catch (ValidationException $e)
        {
            $msg = implode(' ', $e->getErrors());
            Flash::error($msg !== '' ? $msg : 'Unable to remove customer.');
        }
        catch (\PDOException $e)
        {
            Flash::error('Database error while removing customer.');
        }

        $this->redirect('/customers');
    }
}
