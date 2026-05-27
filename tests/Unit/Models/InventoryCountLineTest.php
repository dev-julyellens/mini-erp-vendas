<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\InventoryCountLine;
use PHPUnit\Framework\TestCase;

final class InventoryCountLineTest extends TestCase
{
    public function testVarianceWhenCountedIsNull(): void
    {
        $line = InventoryCountLine::fromArray([
            'id' => 1,
            'inventory_count_id' => 1,
            'product_id' => 1,
            'system_qty' => 10,
            'counted_qty' => null,
        ]);

        $this->assertNull($line->variance());
    }

    public function testVariancePositiveAndNegative(): void
    {
        $surplus = InventoryCountLine::fromArray([
            'id' => 1,
            'inventory_count_id' => 1,
            'product_id' => 1,
            'system_qty' => 10,
            'counted_qty' => 12,
        ]);
        $this->assertSame(2, $surplus->variance());

        $shortage = InventoryCountLine::fromArray([
            'id' => 2,
            'inventory_count_id' => 1,
            'product_id' => 2,
            'system_qty' => 10,
            'counted_qty' => 7,
        ]);
        $this->assertSame(-3, $shortage->variance());
    }
}
