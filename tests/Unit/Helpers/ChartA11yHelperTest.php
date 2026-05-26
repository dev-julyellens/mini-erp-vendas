<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ChartA11yHelper;
use PHPUnit\Framework\TestCase;

final class ChartA11yHelperTest extends TestCase
{
    public function testBuildsRowsWithCurrency(): void
    {
        $rows = ChartA11yHelper::tableRows(
            ['Jan', 'Fev'],
            [
                ['label' => 'Vendas', 'data' => [100.5, 200], 'currency' => true],
            ]
        );

        self::assertSame([
            ['Jan', 'R$ 100,50'],
            ['Fev', 'R$ 200,00'],
        ], $rows);
    }

    public function testColumnHeadersIncludeDatasetLabels(): void
    {
        $headers = ChartA11yHelper::columnHeaders(
            ['A'],
            [
                ['label' => 'Entradas', 'data' => [1]],
                ['label' => 'Saídas', 'data' => [2]],
            ]
        );

        self::assertSame(['Período', 'Entradas', 'Saídas'], $headers);
    }
}
