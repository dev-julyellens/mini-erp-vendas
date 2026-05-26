<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\SecurityBootstrap;
use PHPUnit\Framework\TestCase;

final class SecurityBootstrapTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_DEBUG');
        putenv('APP_ENV');
        putenv('JWT_SECRET');
        parent::tearDown();
    }

    public function testSkipsChecksUnderPhpunit(): void
    {
        putenv('APP_DEBUG=false');
        putenv('JWT_SECRET=');
        SecurityBootstrap::assertSafeConfiguration();
        $this->assertTrue(true);
    }
}
