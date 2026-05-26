<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\ValidationException;
use App\Services\ApiAuthService;
use App\Services\JwtService;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequiresPostgresTrait;

final class ApiAuthServiceIntegrationTest extends TestCase
{
    use RequiresPostgresTrait;

    protected function tearDown(): void
    {
        $this->resetTestContext();
        parent::tearDown();
    }

    public function testLoginWithSeedAdminReturnsValidJwt(): void
    {
        $this->requirePostgres();

        $service = new ApiAuthService();
        $result = $service->login('admin@mini-erp.local', 'Admin@123', 1);

        $this->assertNotEmpty($result['token']);
        $this->assertSame('Bearer', $result['token_type']);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame('admin@mini-erp.local', $result['user']['email']);

        $payload = (new JwtService())->decodeToken($result['token']);
        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['company_id']);
        $this->assertSame('admin@mini-erp.local', $payload['email']);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->requirePostgres();

        $service = new ApiAuthService();

        $this->expectException(ValidationException::class);
        $service->login('admin@mini-erp.local', 'wrong-password', 1);
    }
}
