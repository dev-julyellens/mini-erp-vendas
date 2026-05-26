<?php

declare(strict_types=1);

namespace App\Core;

final class PlanLimitExceededException extends \RuntimeException
{
    public function __construct(
        private readonly string $limitKey,
        private readonly int $limit,
        private readonly int $current,
        string $message = ''
    )
    {
        if ($message === '')
        {
            $message = sprintf(
                'Limite do plano atingido (%s: %d de %d).',
                $limitKey,
                $current,
                $limit
            );
        }

        parent::__construct($message);
    }

    public function limitKey(): string
    {
        return $this->limitKey;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function current(): int
    {
        return $this->current;
    }

    /**
     * @return array<string, string>
     */
    public function toFieldErrors(): array
    {
        return [
            'plan_limit' => $this->getMessage(),
        ];
    }
}
