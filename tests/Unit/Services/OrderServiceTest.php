<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\ValidationException;
use App\Services\OrderService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class OrderServiceTest extends TestCase
{
    public function testNormalizeLinesRejectsEmptyCart(): void
    {
        $this->expectException(ValidationException::class);
        $this->invokeNormalizeLines([]);
    }

    public function testNormalizeLinesMergesDuplicateProducts(): void
    {
        $lines = $this->invokeNormalizeLines([
            ['product_id' => 5, 'quantity' => 2],
            ['product_id' => 5, 'quantity' => 3],
            ['product_id' => 2, 'quantity' => 1],
        ]);

        $this->assertCount(2, $lines);
        $byId = [];
        foreach ($lines as $line)
        {
            $byId[$line['product_id']] = $line['quantity'];
        }
        $this->assertSame(5, $byId[5]); // 2 + 3
        $this->assertSame(1, $byId[2]);
    }

    public function testNormalizeLinesRejectsInvalidProductOrQuantity(): void
    {
        $this->expectException(ValidationException::class);
        $this->invokeNormalizeLines([
            ['product_id' => 0, 'quantity' => 1],
        ]);
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('paid', OrderService::STATUS_PAID);
        $this->assertSame('pending', OrderService::STATUS_PENDING);
    }

    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     * @return list<array{product_id: int, quantity: int}>
     */
    private function invokeNormalizeLines(array $lines): array
    {
        $service = new OrderService();
        $method = new ReflectionMethod(OrderService::class, 'normalizeLines');
        $method->setAccessible(true);

        /** @var list<array{product_id: int, quantity: int}> $result */
        $result = $method->invoke($service, $lines);

        return $result;
    }
}
