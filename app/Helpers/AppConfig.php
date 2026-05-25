<?php

declare(strict_types=1);

namespace App\Helpers;

final class AppConfig
{
    public static function isDebug(): bool
    {
        $flag = getenv('APP_DEBUG');
        if ($flag === false || $flag === '')
        {
            return false;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }
}
