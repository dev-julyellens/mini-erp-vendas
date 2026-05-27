<?php

declare(strict_types=1);

namespace App\Helpers;

final class PgCli
{
    /**
     * @param list<string> $args
     * @param array<string, string> $env
     * @return array{exitCode: int, output: string}
     */
    public static function run(string $binary, array $args, array $env = []): array
    {
        $command = escapeshellarg($binary);
        foreach ($args as $arg)
        {
            $command .= ' ' . escapeshellarg($arg);
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $baseEnv = getenv();
        if (!is_array($baseEnv))
        {
            $baseEnv = [];
        }

        $processEnv = array_merge($baseEnv, $env);

        $process = proc_open($command, $descriptorSpec, $pipes, null, $processEnv);
        if (!is_resource($process))
        {
            return ['exitCode' => 1, 'output' => 'Não foi possível iniciar o processo.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $output = trim((string) $stdout . "\n" . (string) $stderr);

        return ['exitCode' => $exitCode, 'output' => $output];
    }

    public static function resolveBinary(string $configured, string $fallbackName): string
    {
        if ($configured !== '')
        {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows')
        {
            $programFiles = getenv('ProgramFiles');
            if (is_string($programFiles) && $programFiles !== '')
            {
                $glob = glob($programFiles . DIRECTORY_SEPARATOR . 'PostgreSQL' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $fallbackName . '.exe');
                if ($glob !== false && isset($glob[0]))
                {
                    return $glob[0];
                }
            }
        }

        return $fallbackName;
    }
}
