<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\AppConfig;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Models\User;
use App\Repositories\UserRepository;

final class AuthService
{
    private const RESET_TOKEN_TTL_HOURS = 2;

    private UserRepository $users;
    private CompanyAuthService $companyAuth;
    private PasswordPolicyService $passwordPolicy;
    private MailService $mail;

    public function __construct(
        ?UserRepository $users = null,
        ?CompanyAuthService $companyAuth = null,
        ?PasswordPolicyService $passwordPolicy = null,
        ?MailService $mail = null
    )
    {
        $this->users = $users ?? new UserRepository();
        $this->companyAuth = $companyAuth ?? new CompanyAuthService();
        $this->passwordPolicy = $passwordPolicy ?? new PasswordPolicyService();
        $this->mail = $mail ?? new MailService();
    }

    /**
     * Valida credenciais. Se uma empresa, conclui login; se várias, deixa pendente.
     *
     * @return array{completed: bool, user: User, companies?: list<\App\Models\Company>}
     */
    public function authenticate(string $email, string $password): array
    {
        $user = $this->validateCredentials($email, $password);
        $flow = $this->companyAuth->resolvePostCredentialFlow($user->id);

        if ($flow['auto'] && isset($flow['company']))
        {
            $this->companyAuth->completeLoginWithCompany($user, $flow['company']->id);
            Audit::record('login', 'usuarios', $user->id, null, AuditService::userSnapshot($user), $user->id);

            return ['completed' => true, 'user' => $user];
        }

        Auth::setPendingUserId($user->id);

        return [
            'completed' => false,
            'user' => $user,
            'companies' => $flow['companies'] ?? [],
        ];
    }

    public function selectCompany(int $companyId): User
    {
        $userId = Auth::peekPendingUserId();
        if ($userId === null)
        {
            $snapshot = Auth::sessionSnapshot();
            $userId = $snapshot !== null ? (int) ($snapshot['id'] ?? 0) : 0;
        }

        if ($userId <= 0)
        {
            throw new ValidationException(['login' => 'Sessão expirada. Faça login novamente.']);
        }

        $user = $this->users->findById($userId);
        if ($user === null || !$user->active)
        {
            Auth::logout();
            throw new ValidationException(['login' => 'Sessão expirada. Faça login novamente.']);
        }

        $this->companyAuth->completeLoginWithCompany($user, $companyId);
        Auth::pullPendingUserId();
        Audit::record('login', 'usuarios', $user->id, null, AuditService::userSnapshot($user), $user->id);

        return $user;
    }

    public function switchCompany(int $companyId): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            throw new ValidationException(['login' => 'Não autenticado.']);
        }

        $this->companyAuth->switchCompany($user->id, $companyId);
    }

    private function validateCredentials(string $email, string $password): User
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

        $sent = $this->mail->sendPasswordReset($user->email, $user->name, $resetUrl);
        if (!$sent)
        {
            Logger::warning('Não foi possível enviar e-mail de redefinição de senha.', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

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
    /**
     * @return array<string, string>
     */
    private function validatePasswordFields(string $password, string $passwordConfirm): array
    {
        return $this->passwordPolicy->validate($password, $passwordConfirm);
    }
}
