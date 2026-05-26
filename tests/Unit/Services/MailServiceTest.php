<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MailService;
use PHPUnit\Framework\TestCase;

final class MailServiceTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('MAIL_DRIVER=log');
        $this->logPath = dirname(__DIR__, 3) . '/storage/logs/mail.log';
        if (is_file($this->logPath))
        {
            unlink($this->logPath);
        }
    }

    public function testSendPasswordResetWritesToLogDriver(): void
    {
        $service = new MailService();
        $ok = $service->sendPasswordReset(
            'user@example.com',
            'Maria',
            'http://localhost/reset-password?token=abc'
        );

        $this->assertTrue($ok);
        $this->assertFileExists($this->logPath);
        $contents = file_get_contents($this->logPath);
        $this->assertIsString($contents);
        $this->assertStringContainsString('user@example.com', $contents);
        $this->assertStringContainsString('Redefinição de senha', $contents);
    }

    public function testSendRejectsInvalidEmail(): void
    {
        $service = new MailService();
        $this->assertFalse($service->send('not-email', 'Assunto', '<p>corpo</p>'));
    }
}
