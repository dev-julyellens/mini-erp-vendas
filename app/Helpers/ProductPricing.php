<?php

declare(strict_types=1);

namespace App\Helpers;

final class ProductPricing
{
    public const TYPE_PRODUCT = 'product';
    public const TYPE_SERVICE = 'service';

    /** @var list<string> */
    public const UNITS = ['UN', 'CX', 'KG', 'G', 'L', 'ML', 'M', 'M2', 'HR', 'PC'];

    /**
     * @return array{margin: ?string, markup: ?string}
     */
    public static function computeMargins(string $costPrice, string $salePrice): array
    {
        $cost = (float) Money::normalizeDecimal($costPrice);
        $price = (float) Money::normalizeDecimal($salePrice);

        if ($price <= 0)
        {
            return ['margin' => null, 'markup' => null];
        }

        $margin = $cost > 0 ? (($price - $cost) / $price) * 100 : null;
        $markup = $cost > 0 ? (($price - $cost) / $cost) * 100 : null;

        return [
            'margin' => $margin !== null ? number_format($margin, 2, '.', '') : null,
            'markup' => $markup !== null ? number_format($markup, 2, '.', '') : null,
        ];
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, [self::TYPE_PRODUCT, self::TYPE_SERVICE], true);
    }

    public static function isValidUnit(string $unit): bool
    {
        return in_array(strtoupper(trim($unit)), self::UNITS, true);
    }
}
