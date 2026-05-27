<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Models\InventoryCount;
use App\Repositories\InventoryCountLineRepository;
use App\Repositories\InventoryCountRepository;
use App\Services\InventoryCountService;

final class InventoryCountController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

        $repo = new InventoryCountRepository();
        $result = $repo->paginate(
            $page,
            self::PER_PAGE,
            $status !== '' ? $status : null
        );

        $open = $repo->paginate(1, 1, 'open');

        $this->view('inventory/index', [
            'counts' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => ['status' => $status],
            'statusOptions' => InventoryCount::STATUSES,
            'hasOpenCount' => $open['total'] > 0,
            'flash' => Flash::pull(),
        ]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $count = (new InventoryCountRepository())->findById($id);
        if ($count === null)
        {
            Flash::error('Inventário não encontrado.');
            $this->redirect('/inventory');
        }

        $lines = (new InventoryCountLineRepository())->findByInventoryCountId($id);
        $pending = 0;
        foreach ($lines as $line)
        {
            if ($line->counted_qty === null)
            {
                $pending++;
            }
        }

        $this->view('inventory/show', [
            'count' => $count,
            'lines' => $lines,
            'pendingLines' => $pending,
            'flash' => Flash::pull(),
        ]);
    }

    public function start(): void
    {
        $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;

        try
        {
            $countId = (new InventoryCountService())->start($notes);
            Flash::success('Inventário físico #' . $countId . ' iniciado.');
            $this->redirect('/inventory/show?id=' . $countId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/inventory');
        }
        catch (\Throwable $e)
        {
            Flash::error('Erro inesperado ao iniciar o inventário.');
            $this->redirect('/inventory');
        }
    }

    public function saveLines(): void
    {
        $countId = (int) ($_POST['inventory_count_id'] ?? 0);
        $lines = $_POST['lines'] ?? [];
        if (!is_array($lines))
        {
            $lines = [];
        }

        if ($countId <= 0)
        {
            Flash::error('Inventário inválido.');
            $this->redirect('/inventory');
        }

        $service = new InventoryCountService();

        $lineRepo = new InventoryCountLineRepository();

        try
        {
            foreach ($lines as $lineId => $qtyRaw)
            {
                $lineId = (int) $lineId;
                if ($lineId <= 0)
                {
                    continue;
                }
                if ($qtyRaw === '' || $qtyRaw === null)
                {
                    continue;
                }

                $line = $lineRepo->findById($lineId);
                if ($line === null || $line->inventory_count_id !== $countId)
                {
                    throw new ValidationException(['lines' => 'Linha de inventário inválida.']);
                }

                $service->updateLine($lineId, (int) $qtyRaw);
            }
            Flash::success('Contagens salvas.');
            $this->redirect('/inventory/show?id=' . $countId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/inventory/show?id=' . $countId);
        }
    }

    public function finalize(): void
    {
        $countId = (int) ($_POST['id'] ?? 0);
        if ($countId <= 0)
        {
            Flash::error('Inventário inválido.');
            $this->redirect('/inventory');
        }

        try
        {
            $adjustments = (new InventoryCountService())->finalize($countId);
            Flash::success(
                'Inventário finalizado.'
                    . ($adjustments > 0 ? ' ' . $adjustments . ' ajuste(s) de estoque aplicado(s).' : ' Nenhum ajuste necessário.')
            );
            $this->redirect('/inventory/show?id=' . $countId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/inventory/show?id=' . $countId);
        }
        catch (\Throwable $e)
        {
            Flash::error('Erro inesperado ao finalizar o inventário.');
            $this->redirect('/inventory/show?id=' . $countId);
        }
    }

    public function cancel(): void
    {
        $countId = (int) ($_POST['id'] ?? 0);
        if ($countId <= 0)
        {
            Flash::error('Inventário inválido.');
            $this->redirect('/inventory');
        }

        try
        {
            (new InventoryCountService())->cancel($countId);
            Flash::success('Inventário cancelado.');
            $this->redirect('/inventory');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/inventory/show?id=' . $countId);
        }
    }
}
