<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class JwtService
{
    private string $secret;
    private int $ttl;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $jwt = $config['jwt'] ?? [];
        $this->secret = (string) ($jwt['secret'] ?? 'mini-erp-dev-jwt-secret-change-in-production');
        $this->ttl = max(60, (int) ($jwt['ttl'] ?? 3600));
    }

    public function createToken(User $user, int $companyId): string
    {
        $now = time();
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'company_id' => $companyId,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ];

        return $this->encode($payload);
    }

    public function ttlSeconds(): int
    {
        return $this->ttl;
    }

    /**
     * @return array{sub: int, email: string, role: string, company_id: int, iat: int, exp: int}|null
     */
    public function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
        {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $expectedSignature = $this->sign($encodedHeader . '.' . $encodedPayload);

        if (!hash_equals($expectedSignature, $encodedSignature))
        {
            return null;
        }

        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        if ($headerJson === null || $payloadJson === null)
        {
            return null;
        }

        try
        {
            $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $e)
        {
            return null;
        }

        if (!is_array($header) || !is_array($payload))
        {
            return null;
        }

        if (($header['alg'] ?? '') !== 'HS256')
        {
            return null;
        }

        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp <= time())
        {
            return null;
        }

        $sub = (int) ($payload['sub'] ?? 0);
        $companyId = (int) ($payload['company_id'] ?? 0);
        if ($sub <= 0 || $companyId <= 0)
        {
            return null;
        }

        return [
            'sub' => $sub,
            'email' => (string) ($payload['email'] ?? ''),
            'role' => (string) ($payload['role'] ?? ''),
            'company_id' => $companyId,
            'iat' => (int) ($payload['iat'] ?? 0),
            'exp' => $exp,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($header . '.' . $body);

        return $header . '.' . $body . '.' . $signature;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0)
        {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
