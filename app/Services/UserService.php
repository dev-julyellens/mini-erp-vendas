<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Helpers\Validator;
use App\Models\User;
use App\Repositories\UserRepository;

final class UserService
{
    /** @var list<string> */
    public const GLOBAL_ROLES = ['admin', 'vendedor', 'financeiro', 'estoque'];

    private UserRepository $users;
    private PasswordPolicyService $passwordPolicy;

    public function __construct(
        ?UserRepository $users = null,
        ?PasswordPolicyService $passwordPolicy = null
    )
    {
        $this->users = $users ?? new UserRepository();
        $this->passwordPolicy = $passwordPolicy ?? new PasswordPolicyService();
    }

    /**
     * @return array{items: list<User>, total: int}
     */
    public function list(int $page, int $perPage, ?string $search, ?string $status): array
    {
        $activeOnly = match ($status)
        {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        return $this->users->paginate($page, $perPage, $search, $activeOnly);
    }

    public function find(int $id): ?User
    {
        return $this->users->findById($id);
    }

    public function create(
        string $name,
        string $email,
        string $role,
        string $password,
        string $passwordConfirm
    ): int
    {
        $v = $this->validateProfile($name, $email, $role, null);
        $passErrors = $this->passwordPolicy->validate($password, $passwordConfirm);
        $errors = Validator::mergeErrors($v['errors'], $passErrors);

        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try
        {
            $id = $this->users->insert($v['name'], $v['email'], $hash, $v['role']);
            $pdo->commit();
            Audit::record('criar', 'usuarios', $id, null, ['email' => $v['email'], 'role' => $v['role']]);

            return $id;
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $id, string $name, string $email, string $role): void
    {
        $existing = $this->users->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        $v = $this->validateProfile($name, $email, $role, $id);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $this->users->update($id, $v['name'], $v['email'], $v['role']);
        Audit::record(
            'editar',
            'usuarios',
            $id,
            ['email' => $existing->email, 'role' => $existing->role],
            ['email' => $v['email'], 'role' => $v['role']]
        );
    }

    public function setActive(int $id, bool $active): void
    {
        $existing = $this->users->findById($id);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        if (!$active && Auth::id() === $id)
        {
            throw new ValidationException(['id' => 'Você não pode desativar sua própria conta.']);
        }

        $this->users->setActive($id, $active);
        Audit::record(
            $active ? 'ativar' : 'desativar',
            'usuarios',
            $id,
            ['active' => $existing->active],
            ['active' => $active]
        );
    }

    public function adminResetPassword(int $userId, string $password, string $passwordConfirm): void
    {
        (new PlatformAdminService())->assertPlatformAdmin();

        $errors = $this->passwordPolicy->validate($password, $passwordConfirm);
        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $user = $this->users->findById($userId);
        if ($user === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->users->updatePassword($userId, $hash);
        $this->users->deleteResetTokensForUser($userId);
        Audit::record('redefinir_senha', 'usuarios', $userId, null, ['admin_reset' => true]);
    }

    public function changeOwnPassword(int $userId, string $current, string $password, string $passwordConfirm): void
    {
        $user = $this->users->findById($userId);
        if ($user === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        if (!password_verify($current, $user->password_hash))
        {
            throw new ValidationException(['current_password' => 'Senha atual incorreta.']);
        }

        $errors = $this->passwordPolicy->validate($password, $passwordConfirm);
        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->users->updatePassword($userId, $hash);
        Audit::record('alterar_senha', 'usuarios', $userId, null, null, $userId);
    }

    public function updateOwnProfile(int $userId, string $name, string $email): void
    {
        $v = $this->validateProfile($name, $email, null, $userId, false);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $existing = $this->users->findById($userId);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        $this->users->update($userId, $v['name'], $v['email'], $existing->role);
        Audit::record('editar', 'usuarios', $userId, ['name' => $existing->name], ['name' => $v['name']], $userId);
    }

    /**
     * @return array{errors: array<string, string>, name: string, email: string, role: string}
     */
    private function validateProfile(
        string $name,
        string $email,
        ?string $role,
        ?int $excludeId,
        bool $validateRole = true
    ): array
    {
        $nameResult = Validator::requiredString($name, 'name', 'Nome é obrigatório.');
        $emailResult = Validator::email($email, 'email', 'Informe um e-mail válido.');
        $errors = Validator::mergeErrors($nameResult['errors'], $emailResult['errors']);

        $email = $emailResult['value'];
        if ($email !== '' && $excludeId === null && $this->users->findByEmail($email) !== null)
        {
            $errors['email'] = 'E-mail já cadastrado.';
        }
        if ($email !== '' && $excludeId !== null && $this->users->emailExistsForOther($email, $excludeId))
        {
            $errors['email'] = 'E-mail já cadastrado.';
        }

        $roleValue = '';
        if ($validateRole)
        {
            $roleValue = trim((string) $role);
            if (!in_array($roleValue, self::GLOBAL_ROLES, true))
            {
                $errors['role'] = 'Papel inválido.';
            }
        }

        return [
            'errors' => $errors,
            'name' => $nameResult['value'],
            'email' => $email,
            'role' => $roleValue,
        ];
    }
}
