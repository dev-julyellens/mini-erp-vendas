<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Services\PixChargeService;

final class PixChargeController extends Controller
{
    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $service = new PixChargeService();
        $charge = $service->findById($id);

        if ($charge === null)
        {
            Flash::error('Cobrança PIX não encontrada.');
            $this->redirect('finance');

            return;
        }

        $this->view('finance/pix/charge', [
            'charge' => $charge,
            'flash' => Flash::pull(),
        ]);
    }

    public function receipt(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $service = new PixChargeService();
        $charge = $service->findById($id);

        if ($charge === null)
        {
            Flash::error('Cobrança PIX não encontrada.');
            $this->redirect('finance');

            return;
        }

        if (!$charge->isPaid())
        {
            Flash::error('Comprovante disponível apenas após confirmação do pagamento.');
            $this->redirect('finance/pix/charge?id=' . $id);

            return;
        }

        $this->view('finance/pix/receipt', [
            'charge' => $charge,
            'flash' => Flash::pull(),
        ]);
    }

    public function status(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0)
        {
            $this->json(['success' => false, 'message' => 'ID inválido.'], 400);

            return;
        }

        try
        {
            $service = new PixChargeService();
            $result = $service->refreshStatus($id);
            $this->json(['success' => true, 'data' => $result]);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
    }

    public function createForAccount(): void
    {
        $arId = (int) ($_POST['accounts_receivable_id'] ?? 0);
        $amount = trim((string) ($_POST['amount'] ?? ''));

        if ($arId <= 0)
        {
            Flash::error('Conta a receber inválida.');
            $this->redirect('finance/accounts-receivable');

            return;
        }

        try
        {
            $service = new PixChargeService();
            $chargeId = $service->createForAccountsReceivable($arId, $amount);
            Flash::success('Cobrança PIX gerada. Escaneie o QR Code ou copie o código.');
            $this->redirect('finance/pix/charge?id=' . $chargeId);
        }
        catch (ValidationException $e)
        {
            $first = reset($e->getErrors());
            Flash::error(is_string($first) ? $first : 'Não foi possível gerar a cobrança PIX.');
            $this->redirect('finance/accounts-receivable/receive?id=' . $arId);
        }
    }

    public function simulatePay(): void
    {
        $id = (int) ($_POST['charge_id'] ?? 0);
        if ($id <= 0)
        {
            Flash::error('Cobrança inválida.');
            $this->redirect('finance');

            return;
        }

        try
        {
            $service = new PixChargeService();
            $service->simulateMockPayment($id);
            Flash::success('Pagamento PIX simulado e conciliado.');
            $this->redirect('finance/pix/receipt?id=' . $id);
        }
        catch (ValidationException $e)
        {
            $first = reset($e->getErrors());
            Flash::error(is_string($first) ? $first : 'Não foi possível simular o pagamento.');
            $this->redirect('finance/pix/charge?id=' . $id);
        }
    }

    public function createForInstallment(): void
    {
        $installmentId = (int) ($_POST['installment_id'] ?? 0);
        if ($installmentId <= 0)
        {
            Flash::error('Parcela inválida.');
            $this->redirect('finance/installments/open');

            return;
        }

        try
        {
            $service = new PixChargeService();
            $chargeId = $service->createForInstallment($installmentId);
            Flash::success('Cobrança PIX gerada para a parcela.');
            $this->redirect('finance/pix/charge?id=' . $chargeId);
        }
        catch (ValidationException $e)
        {
            $first = reset($e->getErrors());
            Flash::error(is_string($first) ? $first : 'Não foi possível gerar a cobrança PIX.');
            $this->redirect('finance/installments/pay?id=' . $installmentId);
        }
    }
}
