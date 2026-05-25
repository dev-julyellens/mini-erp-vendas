<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

final class CompanyContext
{
    private static ?int $jwtCompanyId = null;

    public static function id(): ?int
    {
        if (self::$jwtCompanyId !== null)
        {
            return self::$jwtCompanyId;
        }

        $snapshot = Auth::sessionSnapshot();
        if ($snapshot === null || !isset($snapshot['company_id']))
        {
            return null;
        }

        $companyId = (int) $snapshot['company_id'];

        return $companyId > 0 ? $companyId : null;
    }

    public static function name(): ?string
    {
        $snapshot = Auth::sessionSnapshot();
        if ($snapshot === null || !isset($snapshot['company_name']))
        {
            return null;
        }

        $name = trim((string) $snapshot['company_name']);

        return $name !== '' ? $name : null;
    }

    public static function requireId(): int
    {
        $id = self::id();
        if ($id === null)
        {
            throw new RuntimeException('Empresa não selecionada na sessão.');
        }

        return $id;
    }

    public static function hasSelected(): bool
    {
        return self::id() !== null;
    }

    public static function setJwtCompanyId(int $companyId): void
    {
        self::$jwtCompanyId = $companyId > 0 ? $companyId : null;
    }

    public static function clearJwt(): void
    {
        self::$jwtCompanyId = null;
    }

    /**
     * @return array{company_id: int, company_name: string}
     */
    public static function sessionCompanyPayload(int $companyId, string $companyName): array
    {
        return [
            'company_id' => $companyId,
            'company_name' => $companyName,
        ];
    }
}
