<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Auth;
use App\Helpers\Flash;
use App\Services\LgpdConsentService;

final class LgpdController extends Controller
{
    public function showConsent(): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('/login');
        }

        $service = new LgpdConsentService();
        if ($service->hasCurrentConsent($user->id))
        {
            $this->redirect('/');
        }

        $this->view('lgpd/consent', [
            'policyVersion' => $service->currentPolicyVersion(),
            'errors' => [],
            'flash' => Flash::pull(),
        ], 'layouts/auth');
    }

    public function storeConsent(): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('/login');
        }

        $service = new LgpdConsentService();
        try
        {
            $service->recordConsent($user->id, isset($_POST['accept']));
            Flash::success('Consentimento registrado com sucesso.');
            $this->redirect('/');
        }
        catch (ValidationException $e)
        {
            $this->view('lgpd/consent', [
                'policyVersion' => $service->currentPolicyVersion(),
                'errors' => $e->getErrors(),
                'flash' => Flash::pull(),
            ], 'layouts/auth');
        }
    }
}
