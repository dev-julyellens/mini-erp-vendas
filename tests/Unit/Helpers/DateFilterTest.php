<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\DateFilter;
use PHPUnit\Framework\TestCase;

final class DateFilterTest extends TestCase
{
    public function testEmptyDateIsValid(): void
    {
        $this->assertSame([], DateFilter::validateOptionalIsoDate('', 'date_from'));
    }

    public function testValidIsoDate(): void
    {
        $this->assertSame([], DateFilter::validateOptionalIsoDate('2026-05-26', 'date_from'));
    }

    public function testRejectsInvalidFormat(): void
    {
        $errors = DateFilter::validateOptionalIsoDate('26/05/2026', 'date_from');
        $this->assertArrayHasKey('date_from', $errors);
    }

    public function testRejectsInvalidCalendarDate(): void
    {
        $errors = DateFilter::validateOptionalIsoDate('2026-02-30', 'date_to');
        $this->assertArrayHasKey('date_to', $errors);
    }
}
