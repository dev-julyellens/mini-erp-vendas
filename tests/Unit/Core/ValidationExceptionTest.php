<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\NotFoundException;
use App\Core\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testValidationExceptionExposesErrors(): void
    {
        $e = new ValidationException(['email' => 'Invalid']);
        $this->assertSame(['email' => 'Invalid'], $e->getErrors());
    }

    public function testNotFoundExceptionIsValidationException(): void
    {
        $e = new NotFoundException('Customer');
        $this->assertInstanceOf(ValidationException::class, $e);
        $this->assertArrayHasKey('id', $e->getErrors());
    }
}
