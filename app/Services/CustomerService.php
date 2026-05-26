<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;
use App\Repositories\CustomerRepository;

final class CustomerService
{
    private CustomerRepository $customers;

    public function __construct(?CustomerRepository $customers = null)
    {
        $this->customers = $customers ?? new CustomerRepository();
    }

    /**
     * @return array{errors: array<string, string>, name: string, email: string, phone: ?string}
     */
    private function validate(string $name, string $email, ?string $phone, ?int $excludeId = null): array
    {
        $nameResult = Validator::requiredString($name, 'name', 'Name is required.');
        $emailResult = Validator::email($email);
        $errors = Validator::mergeErrors($nameResult['errors'], $emailResult['errors']);
        $name = $nameResult['value'];
        $email = $emailResult['value'];

        if ($email !== '' && $excludeId === null && $this->customers->findByEmail($email) !== null)
        {
            $errors['email'] = 'Email already registered.';
        }

        if ($email !== '' && $excludeId !== null && $this->customers->emailExistsForOther($email, $excludeId))
        {
            $errors['email'] = 'Email already registered.';
        }

        $phone = InputSanitizer::phone($phone);

        return ['errors' => $errors, 'name' => $name, 'email' => $email, 'phone' => $phone];
    }

    public function create(string $name, string $email, ?string $phone): int
    {
        (new PlanLimitService())->assertCanCreateAsValidation('customers_max');

        $v = $this->validate($name, $email, $phone, null);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $id = $this->customers->insert($v['name'], $v['email'], $v['phone']);
        $created = $this->customers->findById($id);
        if ($created !== null)
        {
            Audit::record('criar', 'clientes', $id, null, AuditService::customerSnapshot($created));
        }

        return $id;
    }

    public function update(int $id, string $name, string $email, ?string $phone): void
    {
        $existing = $this->customers->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Customer not found.']);
        }

        $v = $this->validate($name, $email, $phone, $id);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $oldSnapshot = AuditService::customerSnapshot($existing);
        $this->customers->update($id, $v['name'], $v['email'], $v['phone']);
        $updated = $this->customers->findById($id);
        if ($updated !== null)
        {
            Audit::record('editar', 'clientes', $id, $oldSnapshot, AuditService::customerSnapshot($updated));
        }
    }

    public function delete(int $id): void
    {
        $existing = $this->customers->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Customer not found.']);
        }

        $oldSnapshot = AuditService::customerSnapshot($existing);

        try
        {
            $this->customers->delete($id);
            Audit::record('excluir', 'clientes', $id, $oldSnapshot, null);
        }
        catch (\PDOException $e)
        {
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23503')
            {
                throw new ValidationException(['id' => 'Cannot delete customer with existing orders.']);
            }
            throw $e;
        }
    }
}
