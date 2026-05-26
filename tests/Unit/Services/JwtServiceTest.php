<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    public function testCreateAndDecodeToken(): void
    {
        $user = new User();
        $user->id = 42;
        $user->email = 'api@test.local';
        $user->role = 'admin';

        $jwt = new JwtService();
        $token = $jwt->createToken($user, 1);

        $this->assertNotSame('', $token);

        $payload = $jwt->decodeToken($token);
        $this->assertIsArray($payload);
        $this->assertSame(42, $payload['sub']);
        $this->assertSame('api@test.local', $payload['email']);
        $this->assertSame(1, $payload['company_id']);
    }

    public function testDecodeRejectsTamperedToken(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'a@b.co';
        $user->role = 'admin';

        $jwt = new JwtService();
        $token = $jwt->createToken($user, 1);
        $tampered = substr($token, 0, -4) . 'xxxx';

        $this->assertNull($jwt->decodeToken($tampered));
    }
}
