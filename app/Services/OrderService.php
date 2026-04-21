<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Money;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use PDO;
use PDOException;

final class OrderService
{
    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     */
    public function placeOrder(int $customerId, array $lines): int
    {
        $normalized = $this->normalizeLines($lines);

        $pdo = Database::getConnection();
        $customerRepo = new CustomerRepository($pdo);
        if ($customerRepo->findById($customerId) === null) {
            throw new ValidationException(['customer_id' => 'Customer not found.']);
        }

        usort(
            $normalized,
            static function (array $a, array $b): int {
                return $a['product_id'] <=> $b['product_id'];
            }
        );

        $pdo->beginTransaction();

        try {
            $productRepo = new ProductRepository($pdo);
            $orderRepo = new OrderRepository($pdo);
            $itemRepo = new OrderItemRepository($pdo);

            $prepared = [];
            $total = '0.00';

            foreach ($normalized as $line) {
                $productId = (int) $line['product_id'];
                $quantity = (int) $line['quantity'];

                $product = $productRepo->findById($productId, true);
                if ($product === null) {
                    throw new ValidationException(['items' => 'Product not found: ' . $productId]);
                }

                if ($quantity <= 0) {
                    throw new ValidationException(['items' => 'Quantity must be positive.']);
                }

                if ($product->stock < $quantity) {
                    throw new ValidationException([
                        'items' => sprintf(
                            'Insufficient stock for "%s". Available: %d, requested: %d.',
                            $product->name,
                            $product->stock,
                            $quantity
                        ),
                    ]);
                }

                /*
                 * Historical pricing (audit / sales integrity):
                 * The product catalog price is copied into `order_items.unit_price` at the moment
                 * the sale is finalized. Future catalog price edits must not rewrite past sales;
                 * reports and order details always reflect what was charged when the order was placed.
                 */
                $unitPriceAtSaleMoment = $product->price;
                $subtotal = Money::mul($unitPriceAtSaleMoment, $quantity);
                $total = Money::add($total, $subtotal);

                $prepared[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceAtSaleMoment,
                    'subtotal' => $subtotal,
                ];
            }

            $orderId = $orderRepo->insert($customerId, $total);

            foreach ($prepared as $row) {
                $itemRepo->insert(
                    $orderId,
                    (int) $row['product_id'],
                    (int) $row['quantity'],
                    (string) $row['unit_price'],
                    (string) $row['subtotal']
                );
                $productRepo->decrementStock((int) $row['product_id'], (int) $row['quantity']);
            }

            $pdo->commit();

            return $orderId;
        } catch (ValidationException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     * @return list<array{product_id: int, quantity: int}>
     */
    private function normalizeLines(array $lines): array
    {
        if ($lines === []) {
            throw new ValidationException(['items' => 'The sale must contain at least one item.']);
        }

        /** @var array<int, int> $merged */
        $merged = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw new ValidationException(['items' => 'Each line needs a valid product and a positive quantity.']);
            }

            if (!isset($merged[$productId])) {
                $merged[$productId] = 0;
            }
            $merged[$productId] += $quantity;
        }

        $out = [];
        foreach ($merged as $pid => $qty) {
            $out[] = ['product_id' => $pid, 'quantity' => $qty];
        }

        return $out;
    }
}
