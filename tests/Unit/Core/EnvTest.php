<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private ?string $tempEnvPath = null;

    /** @var array<string, string|null> */
    private array $envBackup = [];

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $previous)
        {
            if ($previous === null)
            {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
            else
            {
                putenv($key . '=' . $previous);
                $_ENV[$key] = $previous;
                $_SERVER[$key] = $previous;
            }
        }
        $this->envBackup = [];

        if ($this->tempEnvPath !== null && is_file($this->tempEnvPath))
        {
            unlink($this->tempEnvPath);
        }
        $this->tempEnvPath = null;

        parent::tearDown();
    }

    public function testLoadValidEnvFile(): void
    {
        $key = 'ENV_TEST_KEY_' . bin2hex(random_bytes(4));
        $this->tempEnvPath = $this->createTempEnvFile($key . "=valor_teste\n");

        $this->backupEnvKey($key);

        Env::load($this->tempEnvPath);

        $this->assertSame('valor_teste', Env::get($key));
    }

    public function testLoadInvalidEnvFileThrowsRuntimeException(): void
    {
        $this->tempEnvPath = $this->createTempEnvFile("CHAVE_INVALIDA=valor com espacos sem aspas\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arquivo .env inválido');

        Env::load($this->tempEnvPath);
    }

    public function testLoadMissingFileIsNoOp(): void
    {
        $path = sys_get_temp_dir() . '/mini-erp-env-inexistente-' . bin2hex(random_bytes(4)) . '.env';
        $this->assertFileDoesNotExist($path);

        Env::load($path);
        $this->assertTrue(true);
    }

    private function createTempEnvFile(string $contents): string
    {
        $path = sys_get_temp_dir() . '/mini-erp-env-' . bin2hex(random_bytes(6)) . '.env';
        file_put_contents($path, $contents);

        return $path;
    }

    private function backupEnvKey(string $key): void
    {
        $this->envBackup[$key] = array_key_exists($key, $_ENV) ? (string) $_ENV[$key] : null;
    }
}
