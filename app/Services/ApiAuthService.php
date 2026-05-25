<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Models\User;
use App\Repositories\UserRepository;

final class ApiAuthService
{
    private UserRepository $users;
    private JwtService $jwt;

    public function __construct(?UserRepository $users = null, ?JwtService $jwt = null)
    {
        $this->users = $users ?? new UserRepository();
        $this->jwt = $jwt ?? new JwtService();
    }

    /**
     * @return array{token: string, token_type: string, expires_in: int, user: array{id: int, name: string, email: string, role: string}}
     */
    public function login(string $email, string $password): array
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

        $token = $this->jwt->createToken($user);
        Audit::record('login', 'usuarios', $user->id, null, AuditService::userSnapshot($user), $user->id);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->ttlSeconds(),
            'user' => $this->userPayload($user),
        ];
    }

    public function resolveUserFromToken(string $token): ?User
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

        return $user;
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
