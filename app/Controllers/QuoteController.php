<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Models\Quote;
use App\Repositories\CustomerRepository;
use App\Repositories\QuoteItemRepository;
use App\Repositories\QuoteRepository;
use App\Repositories\ProductRepository;
use App\Services\InstallmentService;
use App\Services\QuoteService;

final class QuoteController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== ''
            ? (int) $_GET['customer_id']
            : null;
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

        $repo = new QuoteRepository();
        $result = $repo->paginateFiltered(
            $page,
            self::PER_PAGE,
            $customerId,
            $status !== '' ? $status : null
        );

        $this->view('quotes/index', [
            'quotes' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'customers' => (new CustomerRepository())->allOrderedByName(),
            'filters' => [
                'customer_id' => $customerId,
                'status' => $status,
            ],
            'statusOptions' => Quote::STATUSES,
            'flash' => Flash::pull(),
        ]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $quote = (new QuoteRepository())->findById($id);
        if ($quote === null)
        {
            Flash::error('Orçamento não encontrado.');
            $this->redirect('/quotes');
        }

        $this->view('quotes/show', [
            'quote' => $quote,
            'items' => (new QuoteItemRepository())->findByQuoteId($id),
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->view('quotes/create', [
            'customers' => (new CustomerRepository())->allOrderedByName(),
            'products' => (new ProductRepository())->allOrderedByName(),
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $isJson = str_contains($contentType, 'application/json');
        $payload = $isJson ? $this->readJsonBody() : $_POST;

        $customerId = (int) ($payload['customer_id'] ?? 0);
        $items = $payload['items'] ?? [];
        if (!is_array($items))
        {
            $items = [];
        }
        $validUntil = isset($payload['valid_until']) ? trim((string) $payload['valid_until']) : null;
        $notes = isset($payload['notes']) ? trim((string) $payload['notes']) : null;

        $service = new QuoteService();

        try
        {
            $quoteId = $service->create($customerId, $items, $validUntil, $notes);
            if ($isJson)
            {
                $this->json(['success' => true, 'quote_id' => $quoteId, 'message' => 'Orçamento registrado com sucesso.']);
            }
            Flash::success('Orçamento #' . $quoteId . ' registrado com sucesso.');
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
        catch (ValidationException $e)
        {
            if ($isJson)
            {
                $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
            }
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/quotes/create');
        }
        catch (\Throwable $e)
        {
            if ($isJson)
            {
                $this->json(['success' => false, 'message' => 'Erro inesperado ao salvar o orçamento.'], 500);
            }
            Flash::error('Erro inesperado ao salvar o orçamento.');
            $this->redirect('/quotes/create');
        }
    }

    public function convert(): void
    {
        $quoteId = (int) ($_POST['id'] ?? 0);
        $installmentCount = InstallmentService::normalizeInstallmentCount($_POST['installment_count'] ?? 1);

        if ($quoteId <= 0)
        {
            Flash::error('Orçamento inválido.');
            $this->redirect('/quotes');
        }

        try
        {
            $orderId = (new QuoteService())->convertToOrder($quoteId, $installmentCount);
            Flash::success('Orçamento #' . $quoteId . ' convertido na venda #' . $orderId . '.');
            $this->redirect('/orders/show?id=' . $orderId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
        catch (\Throwable $e)
        {
            Flash::error('Erro inesperado ao converter o orçamento.');
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
    }

    public function cancel(): void
    {
        $quoteId = (int) ($_POST['id'] ?? 0);
        if ($quoteId <= 0)
        {
            Flash::error('Orçamento inválido.');
            $this->redirect('/quotes');
        }

        try
        {
            (new QuoteService())->cancel($quoteId);
            Flash::success('Orçamento #' . $quoteId . ' cancelado.');
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
        catch (\Throwable $e)
        {
            Flash::error('Erro inesperado ao cancelar o orçamento.');
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
    }

    public function markSent(): void
    {
        $quoteId = (int) ($_POST['id'] ?? 0);
        if ($quoteId <= 0)
        {
            Flash::error('Orçamento inválido.');
            $this->redirect('/quotes');
        }

        try
        {
            (new QuoteService())->markSent($quoteId);
            Flash::success('Orçamento marcado como enviado.');
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/quotes/show?id=' . $quoteId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '')
        {
            return [];
        }

        try
        {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : [];
        }
        catch (\JsonException $e)
        {
            return [];
        }
    }
}
