<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\QuoteItem;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class QuoteItemRepository extends BaseRepository
{
    use CompanyScope;
    public function insert(int $quoteId, int $productId, int $quantity, string $unitPrice, string $subtotal): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO quote_items (quote_id, product_id, quantity, unit_price, subtotal)
             VALUES (:quote_id, :product_id, :quantity, :unit_price, :subtotal)'
        );
        $stmt->execute([
            'quote_id' => $quoteId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ]);
    }

    public function deleteByQuoteId(int $quoteId): void
    {
        $stmt = $this->db->prepare('DELETE FROM quote_items WHERE quote_id = :quote_id');
        $stmt->execute(['quote_id' => $quoteId]);
    }

    /**
     * @return list<QuoteItem>
     */
    public function findByQuoteId(int $quoteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT qi.*, p.name AS product_name, p.sku AS product_sku
             FROM quote_items qi
             INNER JOIN quotes q ON q.id = qi.quote_id
             INNER JOIN products p ON p.id = qi.product_id AND p.company_id = q.company_id
             WHERE qi.quote_id = :quote_id AND q.company_id = :company_id
             ORDER BY qi.id'
        );
        $stmt->execute([
            'quote_id' => $quoteId,
            'company_id' => $this->companyId(),
        ]);
        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = QuoteItem::fromArray($row);
        }

        return $items;
    }
}
