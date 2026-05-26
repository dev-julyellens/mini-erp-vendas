<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testAddTwoDecimals(): void
    {
        $this->assertSame('15.50', Money::add('10.25', '5.25'));
    }

    public function testSplitAmountLastInstallmentAbsorbsRemainder(): void
    {
        $parts = Money::splitAmount('100.00', 3);
        $this->assertCount(3, $parts);
        $this->assertSame('100.00', Money::add(Money::add($parts[0], $parts[1]), $parts[2]));
    }

    public function testNormalizeDecimalBrazilianFormat(): void
    {
        $this->assertSame('1234.56', Money::normalizeDecimal('1.234,56'));
    }

    public function testValidatePositiveRejectsZero(): void
    {
        $this->assertFalse(Money::validatePositive('0.00'));
        $this->assertTrue(Money::validatePositive('0.01'));
    }
}
