<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Repositories\CustomerRepository;

final class CustomerService
{
    private CustomerRepository $customers;

    public function __construct(?CustomerRepository $customers = null)
    {
        $this->customers = $customers ?? new CustomerRepository();
    }

    /**
     * @return array<string, string>
     */
    private function validate(string $name, string $email, ?string $phone, ?int $excludeId = null): array
    {
        $errors = [];
        $name = trim($name);
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required.';
        }

        if ($email !== '' && $excludeId === null && $this->customers->findByEmail($email) !== null) {
            $errors['email'] = 'Email already registered.';
        }

        if ($email !== '' && $excludeId !== null && $this->customers->emailExistsForOther($email, $excludeId)) {
            $errors['email'] = 'Email already registered.';
        }

        $phone = $phone !== null ? trim($phone) : null;
        if ($phone === '') {
            $phone = null;
        }

        return ['errors' => $errors, 'name' => $name, 'email' => $email, 'phone' => $phone];
    }

    public function create(string $name, string $email, ?string $phone): int
    {
        $v = $this->validate($name, $email, $phone, null);
        if ($v['errors'] !== []) {
            throw new ValidationException($v['errors']);
        }

        return $this->customers->insert($v['name'], $v['email'], $v['phone']);
    }

    public function update(int $id, string $name, string $email, ?string $phone): void
    {
        if ($this->customers->findById($id) === null) {
            throw new ValidationException(['id' => 'Customer not found.']);
        }

        $v = $this->validate($name, $email, $phone, $id);
        if ($v['errors'] !== []) {
            throw new ValidationException($v['errors']);
        }

        $this->customers->update($id, $v['name'], $v['email'], $v['phone']);
    }

    public function delete(int $id): void
    {
        if ($this->customers->findById($id) === null) {
            throw new ValidationException(['id' => 'Customer not found.']);
        }

        try {
            $this->customers->delete($id);
        } catch (\PDOException $e) {
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23503') {
                throw new ValidationException(['id' => 'Cannot delete customer with existing orders.']);
            }
            throw $e;
        }
    }
}
