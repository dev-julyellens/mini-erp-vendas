<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Models\Payment;
use App\Repositories\CustomerRepository;
use App\Services\AccountsReceivableService;
use App\Services\PaymentService;

final class AccountsReceivableController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== ''
            ? (int) $_GET['customer_id']
            : null;
        if ($customerId !== null && $customerId <= 0)
        {
            $customerId = null;
        }
        $dueFrom = isset($_GET['due_from']) ? trim((string) $_GET['due_from']) : '';
        $dueTo = isset($_GET['due_to']) ? trim((string) $_GET['due_to']) : '';
        $overdueOnly = isset($_GET['overdue']) && $_GET['overdue'] === '1';

        $service = new AccountsReceivableService();
        $result = $service->search(
            $page,
            self::PER_PAGE,
            $status !== '' ? $status : null,
            $customerId,
            $dueFrom !== '' ? $dueFrom : null,
            $dueTo !== '' ? $dueTo : null,
            $overdueOnly
        );

        $filters = [
            'status' => $status,
            'customer_id' => $customerId,
            'due_from' => $dueFrom,
            'due_to' => $dueTo,
            'overdue' => $overdueOnly ? '1' : '',
        ];

        $customers = new CustomerRepository();

        $this->view('finance/accounts-receivable/index', [
            'accounts' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'customers' => $customers->allOrderedByName(),
            'filters' => $filters,
            'paginationQuery' => AccountsReceivableService::filterQueryParams($filters),
            'statuses' => \App\Models\AccountsReceivable::STATUSES,
            'statusLabels' => \App\Models\AccountsReceivable::STATUS_LABELS,
            'flash' => Flash::pull(),
        ]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $service = new AccountsReceivableService();
        $detail = $service->findDetail($id);

        if ($detail === null)
        {
            Flash::error('Conta a receber não encontrada.');
            $this->redirect('finance/accounts-receivable');

            return;
        }

        $this->view('finance/accounts-receivable/show', [
            'account' => $detail['account'],
            'payments' => $detail['payments'],
            'methodLabels' => Payment::METHOD_LABELS,
            'flash' => Flash::pull(),
        ]);
    }

    public function receive(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $service = new AccountsReceivableService();
        $detail = $service->findDetail($id);

        if ($detail === null)
        {
            Flash::error('Conta a receber não encontrada.');
            $this->redirect('finance/accounts-receivable');

            return;
        }

        $account = $detail['account'];
        if (!$account->canReceive())
        {
            Flash::error('Esta conta não aceita novos recebimentos.');
            $this->redirect('finance/accounts-receivable/show?id=' . $id);

            return;
        }

        $this->view('finance/accounts-receivable/receive', [
            'account' => $account,
            'methods' => Payment::METHODS,
            'methodLabels' => Payment::METHOD_LABELS,
            'errors' => [],
            'old' => [
                'amount' => $account->remaining_amount ?? $account->amount,
                'payment_method' => 'pix',
                'paid_at' => (new \DateTimeImmutable())->format('Y-m-d\TH:i'),
                'notes' => '',
            ],
            'flash' => Flash::pull(),
        ]);
    }

    public function storePayment(): void
    {
        $id = (int) ($_POST['accounts_receivable_id'] ?? 0);
        if ($id <= 0)
        {
            Flash::error('Conta a receber inválida.');
            $this->redirect('finance/accounts-receivable');

            return;
        }

        $amount = trim((string) ($_POST['amount'] ?? ''));
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
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'paid_at' => $paidAtRaw,
            'notes' => $notes ?? '',
        ];

        if ($fieldErrors !== [])
        {
            $this->renderReceiveForm($id, $old, $fieldErrors);

            return;
        }

        try
        {
            $service = new PaymentService();
            $service->receive($id, $amount, $paymentMethod, $paidAt, $notes !== '' ? $notes : null);
            Flash::success('Recebimento registrado com sucesso.');
            $this->redirect('finance/accounts-receivable/show?id=' . $id);
        }
        catch (ValidationException $e)
        {
            $this->renderReceiveForm($id, $old, $e->getErrors());
        }
    }

    /**
     * @param array<string, string> $old
     * @param array<string, string> $errors
     */
    private function renderReceiveForm(int $id, array $old, array $errors): void
    {
        $arService = new AccountsReceivableService();
        $detail = $arService->findDetail($id);

        if ($detail === null)
        {
            Flash::error('Conta a receber não encontrada.');
            $this->redirect('finance/accounts-receivable');

            return;
        }

        $this->view('finance/accounts-receivable/receive', [
            'account' => $detail['account'],
            'methods' => Payment::METHODS,
            'methodLabels' => Payment::METHOD_LABELS,
            'errors' => $errors,
            'old' => $old,
            'flash' => Flash::pull(),
        ]);
    }
}
