<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Helpers\Redirect;
use App\Services\AuthService;

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
            $service->login(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );

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
            ], 'layouts/auth');
        }
    }
}
