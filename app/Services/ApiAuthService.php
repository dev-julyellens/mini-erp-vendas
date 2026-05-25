<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Models\User;
use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;

final class ApiAuthService
{
    private UserRepository $users;
    private JwtService $jwt;
    private CompanyRepository $companies;

    public function __construct(
        ?UserRepository $users = null,
        ?JwtService $jwt = null,
        ?CompanyRepository $companies = null
    )
    {
        $this->users = $users ?? new UserRepository();
        $this->jwt = $jwt ?? new JwtService();
        $this->companies = $companies ?? new CompanyRepository();
    }

    /**
     * @return array{
     *   token: string,
     *   token_type: string,
     *   expires_in: int,
     *   user: array{id: int, name: string, email: string, role: string, company_id: int, company_name: string}
     * }
     */
    public function login(string $email, string $password, ?int $companyId = null): array
    {
        $errors = [];
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $errors['email'] = 'Informe um e-mail válido.';
        }

        if ($password === '')
        {
            $errors['password'] = 'Informe a senha.';
        }

        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->active || !password_verify($password, $user->password_hash))
        {
            throw new ValidationException(['login' => 'E-mail ou senha inválidos.']);
        }

        $accessible = $this->companies->listActiveForUser($user->id);
        if ($accessible === [])
        {
            throw new ValidationException(['login' => 'Usuário sem empresa vinculada.']);
        }

        if ($companyId === null && count($accessible) === 1)
        {
            $companyId = $accessible[0]->id;
        }

        if ($companyId === null)
        {
            $list = array_map(static fn($c) => $c->toSelectArray(), $accessible);
            throw new ValidationException([
                'company_id' => 'Informe company_id. Empresas disponíveis: ' . json_encode($list, JSON_UNESCAPED_UNICODE),
            ]);
        }

        if (!$this->companies->userHasAccess($user->id, $companyId))
        {
            throw new ValidationException(['company_id' => 'Você não tem acesso a esta empresa.']);
        }

        $company = $this->companies->findById($companyId);
        if ($company === null)
        {
            throw new ValidationException(['company_id' => 'Empresa inválida ou inativa.']);
        }

        $token = $this->jwt->createToken($user, $company->id);
        Audit::record('login', 'usuarios', $user->id, null, AuditService::userSnapshot($user), $user->id);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->ttlSeconds(),
            'user' => $this->userPayload($user, $company->id, $company->name),
        ];
    }

    /**
     * @return array{user: User, company_id: int, company_name: string}|null
     */
    public function resolveUserFromToken(string $token): ?array
    {
        $claims = $this->jwt->decodeToken($token);
        if ($claims === null)
        {
            return null;
        }

        $user = $this->users->findById($claims['sub']);
        if ($user === null || !$user->active)
        {
            return null;
        }

        if (!$this->companies->userHasAccess($user->id, $claims['company_id']))
        {
            return null;
        }

        $company = $this->companies->findById($claims['company_id']);
        if ($company === null)
        {
            return null;
        }

        return [
            'user' => $user,
            'company_id' => $company->id,
            'company_name' => $company->name,
        ];
    }

    /**
     * @return array{id: int, name: string, email: string, role: string, company_id: int, company_name: string}
     */
    private function userPayload(User $user, int $companyId, string $companyName): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company_id' => $companyId,
            'company_name' => $companyName,
        ];
    }
}
