<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\ValidationException;
use App\Services\ApiAuthService;
use PHPUnit\Framework\TestCase;

final class ApiAuthServiceTest extends TestCase
{
    public function testLoginRejectsEmptyCredentials(): void
    {
        $service = new ApiAuthService();

        try
        {
            $service->login('', '');
            $this->fail('Deveria lançar ValidationException.');
        }
        catch (ValidationException $e)
        {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('password', $errors);
        }
    }

    public function testLoginRejectsInvalidEmailFormat(): void
    {
        $service = new ApiAuthService();

        $this->expectException(ValidationException::class);
        $service->login('not-an-email', 'secret');
    }
}
