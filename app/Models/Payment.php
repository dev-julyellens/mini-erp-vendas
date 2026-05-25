<?php

declare(strict_types=1);

namespace App\Models;

final class Payment
{
    /** @var list<string> */
    public const METHODS = ['dinheiro', 'pix', 'cartao', 'boleto'];

    /** @var array<string, string> */
    public const METHOD_LABELS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'cartao' => 'Cartão',
        'boleto' => 'Boleto',
    ];

    public int $id;
    public int $accounts_receivable_id;
    public string $amount;
    public string $payment_method;
    public string $paid_at;
    public ?int $received_by;
    public ?string $notes;
    public string $created_at;
    public ?string $received_by_name;

    public static function methodLabel(string $method): string
    {
        return self::METHOD_LABELS[$method] ?? $method;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->accounts_receivable_id = (int) $row['accounts_receivable_id'];
        $m->amount = (string) $row['amount'];
        $m->payment_method = (string) $row['payment_method'];
        $m->paid_at = (string) $row['paid_at'];
        $m->received_by = isset($row['received_by']) && $row['received_by'] !== null
            ? (int) $row['received_by']
            : null;
        $m->notes = isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : null;
        $m->created_at = (string) $row['created_at'];
        $m->received_by_name = isset($row['received_by_name']) ? (string) $row['received_by_name'] : null;

        return $m;
    }
}
