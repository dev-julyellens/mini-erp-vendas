<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Models\Payment;
use App\Repositories\CustomerRepository;
use App\Repositories\PixChargeRepository;
use App\Services\InstallmentService;

final class InstallmentController extends Controller
{
    private const PER_PAGE = 15;

    public function overdue(): void
    {
        $this->renderList('overdue', 'Contas vencidas', 'Parcelas com vencimento ultrapassado');
    }

    public function open(): void
    {
        $this->renderList('open', 'Parcelas abertas', 'Pendentes e vencidas aguardando baixa');
    }

    public function history(): void
    {
        $this->renderList('history', 'Histórico de parcelas', 'Parcelas pagas ou canceladas');
    }

    public function pay(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $service = new InstallmentService();
        $installment = $service->findById($id);

        if ($installment === null)
        {
            Flash::error('Parcela não encontrada.');
            $this->redirect('finance/installments/open');

            return;
        }

        if (!$installment->canPay())
        {
            Flash::error('Esta parcela não aceita baixa.');
            $this->redirect('finance/installments/open');

            return;
        }

        $pixRepo = new PixChargeRepository();
        $pixRepo->expirePendingPastDue();
        $pendingPix = $pixRepo->findPendingByInstallmentId($id);

        $this->view('finance/installments/pay', [
            'installment' => $installment,
            'methods' => Payment::METHODS,
            'methodLabels' => Payment::METHOD_LABELS,
            'pendingPix' => $pendingPix,
            'errors' => [],
            'old' => [
                'payment_method' => 'pix',
                'paid_at' => (new \DateTimeImmutable())->format('Y-m-d\TH:i'),
                'notes' => '',
            ],
            'flash' => Flash::pull(),
        ]);
    }

    public function storePayment(): void
    {
        $id = (int) ($_POST['installment_id'] ?? 0);
        if ($id <= 0)
        {
            Flash::error('Parcela inválida.');
            $this->redirect('finance/installments/open');

            return;
        }

        $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));
        $paidAtRaw = trim((string) ($_POST['paid_at'] ?? ''));
        $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;

        $paidAt = null;
        $fieldErrors = [];
        if ($paidAtRaw !== '')
        {
            try
            {
                $paidAt = (new \DateTimeImmutable($paidAtRaw))->format('Y-m-d H:i:s');
            }
            catch (\Throwable $e)
            {
                $fieldErrors['paid_at'] = 'Data de pagamento inválida.';
            }
        }

        $old = [
            'payment_method' => $paymentMethod,
            'paid_at' => $paidAtRaw,
            'notes' => $notes ?? '',
        ];

        if ($fieldErrors !== [])
        {
            $this->renderPayForm($id, $old, $fieldErrors);

            return;
        }

        try
        {
            $service = new InstallmentService();
            $service->pay($id, $paymentMethod, $paidAt, $notes !== '' ? $notes : null);
            Flash::success('Baixa da parcela registrada com sucesso.');
            $this->redirect('finance/installments/history');
        }
        catch (ValidationException $e)
        {
            $this->renderPayForm($id, $old, $e->getErrors());
        }
    }

    /**
     * @param array<string, string> $old
     * @param array<string, string> $errors
     */
    private function renderPayForm(int $id, array $old, array $errors): void
    {
        $service = new InstallmentService();
        $installment = $service->findById($id);

        if ($installment === null)
        {
            Flash::error('Parcela não encontrada.');
            $this->redirect('finance/installments/open');

            return;
        }

        if (!$installment->canPay())
        {
            Flash::error('Esta parcela não aceita baixa.');
            $this->redirect('finance/installments/open');

            return;
        }

        $pixRepo = new PixChargeRepository();
        $pendingPix = $pixRepo->findPendingByInstallmentId($id);

        $this->view('finance/installments/pay', [
            'installment' => $installment,
            'methods' => Payment::METHODS,
            'methodLabels' => Payment::METHOD_LABELS,
            'pendingPix' => $pendingPix,
            'errors' => $errors,
            'old' => $old,
            'flash' => Flash::pull(),
        ]);
    }

    private function renderList(string $listType, string $title, string $subtitle): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== ''
            ? (int) $_GET['customer_id']
            : null;
        if ($customerId !== null && $customerId <= 0)
        {
            $customerId = null;
        }
        $dueFrom = isset($_GET['due_from']) ? trim((string) $_GET['due_from']) : '';
        $dueTo = isset($_GET['due_to']) ? trim((string) $_GET['due_to']) : '';

        $service = new InstallmentService();
        $result = $service->search(
            $page,
            self::PER_PAGE,
            $listType,
            $customerId,
            $dueFrom !== '' ? $dueFrom : null,
            $dueTo !== '' ? $dueTo : null
        );

        $filters = [
            'customer_id' => $customerId,
            'due_from' => $dueFrom,
            'due_to' => $dueTo,
        ];

        $customers = new CustomerRepository();

        $this->view('finance/installments/list', [
            'installments' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'customers' => $customers->allOrderedByName(),
            'filters' => $filters,
            'paginationQuery' => InstallmentService::filterQueryParams($filters),
            'listType' => $listType,
            'title' => $title,
            'subtitle' => $subtitle,
            'flash' => Flash::pull(),
        ]);
    }
}
