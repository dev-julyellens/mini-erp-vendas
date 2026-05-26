<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredStringRejectsEmpty(): void
    {
        $result = Validator::requiredString('   ', 'name', 'Name is required.');
        $this->assertSame('', $result['value']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testEmailRejectsInvalid(): void
    {
        $result = Validator::email('not-an-email');
        $this->assertNotEmpty($result['errors']);
    }

    public function testMergeErrorsCombinesFields(): void
    {
        $merged = Validator::mergeErrors(['a' => 'A'], ['b' => 'B']);
        $this->assertSame(['a' => 'A', 'b' => 'B'], $merged);
    }
}
