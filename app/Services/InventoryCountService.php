<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Repositories\InventoryCountLineRepository;
use App\Repositories\InventoryCountRepository;
use App\Repositories\ProductRepository;
use PDO;

final class InventoryCountService
{
    public function start(?string $notes = null, ?int $userId = null): int
    {
        $userId = $userId ?? Auth::id();
        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '')
        {
            $notes = null;
        }

        $products = (new ProductRepository())->listPhysicalProductsOrderedByName();
        if ($products === [])
        {
            throw new ValidationException(['inventory' => 'Não há produtos físicos para inventariar.']);
        }

        $countId = Database::transaction(function (PDO $pdo) use ($notes, $userId, $products): int
        {
            $countRepo = new InventoryCountRepository($pdo);
            if ($countRepo->countOpen() > 0)
            {
                throw new ValidationException([
                    'inventory' => 'Já existe um inventário em andamento. Finalize ou cancele antes de abrir outro.',
                ]);
            }

            $lineRepo = new InventoryCountLineRepository($pdo);

            $countId = $countRepo->insert($notes, $userId);
            $lines = [];
            foreach ($products as $product)
            {
                $lines[] = [
                    'product_id' => $product->id,
                    'system_qty' => $product->stock,
                    'counted_qty' => null,
                ];
            }
            $lineRepo->insertBatch($countId, $lines);

            return $countId;
        });

        Audit::record('inventario_fisico', 'estoque', $countId, null, [
            'status' => 'open',
            'lines' => count($products),
        ], $userId);

        Logger::info('Inventário físico iniciado.', ['inventory_count_id' => $countId]);

        return $countId;
    }

    public function updateLine(int $lineId, int $countedQty): void
    {
        if ($countedQty < 0)
        {
            throw new ValidationException(['counted_qty' => 'Quantidade contada não pode ser negativa.']);
        }

        $lineRepo = new InventoryCountLineRepository();
        $line = $lineRepo->findById($lineId);
        if ($line === null)
        {
            throw new ValidationException(['line_id' => 'Linha do inventário não encontrada.']);
        }

        if (!$lineRepo->updateCountedQty($lineId, $countedQty))
        {
            throw new ValidationException(['status' => 'Inventário não está aberto para edição ou linha inválida.']);
        }
    }

    public function finalize(int $countId, ?int $userId = null): int
    {
        $userId = $userId ?? Auth::id();
        $adjustments = 0;

        Database::transaction(function (PDO $pdo) use ($countId, $userId, &$adjustments): void
        {
            $countRepo = new InventoryCountRepository($pdo);
            $lineRepo = new InventoryCountLineRepository($pdo);
            $count = $countRepo->findByIdForUpdate($countId);
            if ($count === null)
            {
                throw new ValidationException(['id' => 'Inventário não encontrado.']);
            }
            if (!$count->isOpen())
            {
                throw new ValidationException(['status' => 'Inventário já foi encerrado.']);
            }

            $lines = $lineRepo->findByInventoryCountId($countId);
            $uncounted = 0;
            foreach ($lines as $line)
            {
                if ($line->counted_qty === null)
                {
                    $uncounted++;
                }
            }
            if ($uncounted > 0)
            {
                throw new ValidationException([
                    'lines' => sprintf('Informe a contagem de todos os itens (%d pendente(s)).', $uncounted),
                ]);
            }

            $stockService = new StockService(
                null,
                new ProductRepository($pdo),
                $pdo
            );

            foreach ($lines as $line)
            {
                $variance = $line->variance();
                if ($variance === null || $variance === 0)
                {
                    continue;
                }

                $stockService->apply(
                    'inventario',
                    $line->product_id,
                    $variance,
                    'inventory_count',
                    $countId,
                    'Inventário físico #' . $countId,
                    $userId,
                    false,
                    true
                );
                $adjustments++;
            }

            $countRepo->markFinalized($countId, $userId);
        });

        Audit::record('inventario_fisico', 'estoque', $countId, ['status' => 'open'], [
            'status' => 'finalized',
            'adjustments' => $adjustments,
        ], $userId);

        Logger::info('Inventário físico finalizado.', [
            'inventory_count_id' => $countId,
            'adjustments' => $adjustments,
        ]);

        return $adjustments;
    }

    public function cancel(int $countId, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        Database::transaction(function (PDO $pdo) use ($countId): void
        {
            $countRepo = new InventoryCountRepository($pdo);
            $count = $countRepo->findByIdForUpdate($countId);
            if ($count === null)
            {
                throw new ValidationException(['id' => 'Inventário não encontrado.']);
            }
            if (!$count->isOpen())
            {
                throw new ValidationException(['status' => 'Somente inventários abertos podem ser cancelados.']);
            }
            $countRepo->markCanceled($countId);
        });

        Audit::record('inventario_fisico', 'estoque', $countId, ['status' => 'open'], ['status' => 'canceled'], $userId);
    }
}
