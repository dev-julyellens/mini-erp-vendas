<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\AppConfig;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Models\User;
use App\Repositories\UserRepository;

final class AuthService
{
    private const RESET_TOKEN_TTL_HOURS = 2;
    private const MIN_PASSWORD_LENGTH = 8;

    private UserRepository $users;

    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
    }

    public function login(string $email, string $password): User
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

        Auth::login($user);
        Audit::record('login', 'usuarios', $user->id, null, AuditService::userSnapshot($user), $user->id);

        return $user;
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user !== null)
        {
            Audit::record('logout', 'usuarios', $user->id, AuditService::userSnapshot($user), null, $user->id);
        }

        Auth::logout();
    }

    /**
     * @return array{message: string, reset_url?: string}
     */
    public function requestPasswordReset(string $email): array
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new ValidationException(['email' => 'Informe um e-mail válido.']);
        }

        $user = $this->users->findByEmail($email);
        $message = 'Se o e-mail estiver cadastrado, você receberá instruções para redefinir a senha.';

        if ($user === null || !$user->active)
        {
            return ['message' => $message];
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_TOKEN_TTL_HOURS * 3600);

        $this->users->insertResetToken($user->id, $tokenHash, $expiresAt);
        Audit::record(
            'solicitar_redefinir_senha',
            'usuarios',
            $user->id,
            null,
            ['email' => $user->email],
            $user->id
        );

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $base = rtrim((string) $config['base_url'], '/');
        $resetUrl = $base . '/reset-password?token=' . urlencode($token);

        $result = ['message' => $message];
        if (AppConfig::isDebug())
        {
            $result['reset_url'] = $resetUrl;
        }

        return $result;
    }

    public function resetPassword(string $token, string $password, string $passwordConfirm): void
    {
        $errors = $this->validatePasswordFields($password, $passwordConfirm);
        $token = trim($token);

        if ($token === '')
        {
            $errors['token'] = 'Link de recuperação inválido ou expirado.';
        }

        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $tokenHash = hash('sha256', $token);
        $userId = $this->users->findUserIdByValidResetToken($tokenHash);
        if ($userId === null)
        {
            throw new ValidationException(['token' => 'Link de recuperação inválido ou expirado.']);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db = Database::getConnection();
        $db->beginTransaction();
        try
        {
            $this->users->updatePassword($userId, $hash);
            $this->users->deleteResetToken($tokenHash);
            $this->users->deleteResetTokensForUser($userId);
            $db->commit();
        }
        catch (\Throwable $e)
        {
            if ($db->inTransaction())
            {
                $db->rollBack();
            }
            throw $e;
        }

        $user = $this->users->findById($userId);
        if ($user !== null)
        {
            Audit::record(
                'redefinir_senha',
                'usuarios',
                $userId,
                null,
                ['email' => $user->email],
                $userId
            );
        }
    }

    public function tokenIsValid(string $token): bool
    {
        $token = trim($token);
        if ($token === '')
        {
            return false;
        }

        return $this->users->findUserIdByValidResetToken(hash('sha256', $token)) !== null;
    }

    /**
     * @return array<string, string>
     */
    private function validatePasswordFields(string $password, string $passwordConfirm): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_PASSWORD_LENGTH)
        {
            $errors['password'] = 'A senha deve ter no mínimo ' . self::MIN_PASSWORD_LENGTH . ' caracteres.';
        }

        if ($password !== $passwordConfirm)
        {
            $errors['password_confirm'] = 'As senhas não conferem.';
        }

        return $errors;
    }
}
