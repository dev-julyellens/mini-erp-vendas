<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\ValidationException;
use App\Services\QuoteService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class QuoteServiceTest extends TestCase
{
    public function testNormalizeLinesRejectsEmptyCart(): void
    {
        $this->expectException(ValidationException::class);
        $this->invokeNormalizeLines([]);
    }

    public function testNormalizeLinesMergesDuplicateProducts(): void
    {
        $lines = $this->invokeNormalizeLines([
            ['product_id' => 3, 'quantity' => 1],
            ['product_id' => 3, 'quantity' => 4],
        ]);

        $this->assertCount(1, $lines);
        $this->assertSame(3, $lines[0]['product_id']);
        $this->assertSame(5, $lines[0]['quantity']);
    }

    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     * @return list<array{product_id: int, quantity: int}>
     */
    private function invokeNormalizeLines(array $lines): array
    {
        $service = new QuoteService();
        $method = new ReflectionMethod(QuoteService::class, 'normalizeLines');
        $method->setAccessible(true);

        /** @var list<array{product_id: int, quantity: int}> $result */
        $result = $method->invoke($service, $lines);

        return $result;
    }
}
