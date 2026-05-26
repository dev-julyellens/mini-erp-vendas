<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Helpers\Flash;
use App\Helpers\Redirect;
use App\Services\AuthService;
use App\Services\CompanyAuthService;
use App\Services\PasswordPolicyService;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'errors' => [],
            'old' => [],
            'flash' => Flash::pull(),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $service = new AuthService();
        try
        {
            $result = $service->authenticate(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );

            if (!$result['completed'])
            {
                $this->redirect('/select-company');
            }

            $intended = Redirect::sanitizeIntendedUrl($_SESSION['intended_url'] ?? '/');
            unset($_SESSION['intended_url']);

            Flash::success('Login realizado com sucesso.');
            $this->redirect($intended);
        }
        catch (ValidationException $e)
        {
            $this->view('auth/login', [
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ], 'layouts/auth');
        }
    }

    public function showSelectCompany(): void
    {
        $pendingId = Auth::peekPendingUserId();
        if ($pendingId === null && !Auth::check())
        {
            $this->redirect('/login');
        }

        $userId = $pendingId ?? Auth::id();
        if ($userId === null)
        {
            $this->redirect('/login');
        }

        $companyAuth = new CompanyAuthService();
        $companies = $companyAuth->listAccessibleCompanies($userId);

        if ($companies === [])
        {
            Flash::error('Nenhuma empresa disponível para este usuário.');
            Auth::logout();
            $this->redirect('/login');
        }

        if (count($companies) === 1 && $pendingId !== null)
        {
            $service = new AuthService();
            try
            {
                $service->selectCompany($companies[0]->id);
                Flash::success('Login realizado com sucesso.');
                $this->redirect('/');
            }
            catch (ValidationException $e)
            {
                Flash::error($e->getErrors()['company_id'] ?? 'Não foi possível selecionar a empresa.');
                $this->redirect('/login');
            }
        }

        $this->view('auth/select-company', [
            'errors' => [],
            'companies' => $companies,
            'canSwitch' => $pendingId === null && CompanyContext::hasSelected(),
            'flash' => Flash::pull(),
        ], 'layouts/auth');
    }

    public function selectCompany(): void
    {
        $service = new AuthService();
        $completingLogin = Auth::peekPendingUserId() !== null;
        try
        {
            $service->selectCompany((int) ($_POST['company_id'] ?? 0));

            if ($completingLogin)
            {
                $intended = Redirect::sanitizeIntendedUrl($_SESSION['intended_url'] ?? '/');
                unset($_SESSION['intended_url']);
                Flash::success('Login realizado com sucesso.');
                $this->redirect($intended);
            }

            Flash::success('Empresa alterada com sucesso.');
            $this->redirect('/');
        }
        catch (ValidationException $e)
        {
            $userId = Auth::peekPendingUserId() ?? Auth::id();
            $companies = $userId !== null
                ? (new CompanyAuthService())->listAccessibleCompanies($userId)
                : [];

            $this->view('auth/select-company', [
                'errors' => $e->getErrors(),
                'companies' => $companies,
                'canSwitch' => Auth::peekPendingUserId() === null,
                'flash' => Flash::pull(),
            ], 'layouts/auth');
        }
    }

    public function logout(): void
    {
        $service = new AuthService();
        $service->logout();
        Flash::success('Sessão encerrada.');
        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password', [
            'errors' => [],
            'old' => [],
            'flash' => Flash::pull(),
            'resetUrl' => null,
        ], 'layouts/auth');
    }

    public function forgotPassword(): void
    {
        $service = new AuthService();
        try
        {
            $result = $service->requestPasswordReset((string) ($_POST['email'] ?? ''));
            Flash::success($result['message']);
            $this->view('auth/forgot-password', [
                'errors' => [],
                'old' => [],
                'flash' => Flash::pull(),
                'resetUrl' => $result['reset_url'] ?? null,
            ], 'layouts/auth');
        }
        catch (ValidationException $e)
        {
            $this->view('auth/forgot-password', [
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
                'resetUrl' => null,
            ], 'layouts/auth');
        }
    }

    public function showResetPassword(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $service = new AuthService();

        if (!$service->tokenIsValid($token))
        {
            Flash::error('Link de recuperação inválido ou expirado.');
            $this->redirect('/forgot-password');
        }

        $this->view('auth/reset-password', [
            'token' => $token,
            'errors' => [],
            'flash' => Flash::pull(),
            'passwordHint' => (new PasswordPolicyService())->requirementsHint(),
        ], 'layouts/auth');
    }

    public function resetPassword(): void
    {
        $service = new AuthService();
        $token = (string) ($_POST['token'] ?? '');

        try
        {
            $service->resetPassword(
                $token,
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['password_confirm'] ?? '')
            );
            Flash::success('Senha redefinida. Faça login com a nova senha.');
            $this->redirect('/login');
        }
        catch (ValidationException $e)
        {
            if (!$service->tokenIsValid($token))
            {
                Flash::error('Link de recuperação inválido ou expirado.');
                $this->redirect('/forgot-password');
            }

            $this->view('auth/reset-password', [
                'token' => $token,
                'errors' => $e->getErrors(),
                'flash' => Flash::pull(),
                'passwordHint' => (new PasswordPolicyService())->requirementsHint(),
            ], 'layouts/auth');
        }
    }
}
