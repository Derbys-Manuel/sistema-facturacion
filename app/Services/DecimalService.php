<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class DecimalService
{
    public static function of(string|int|float|null $value): BigDecimal
    {
        return BigDecimal::of((string) ($value ?? '0'));
    }

    public static function scale(BigDecimal $value, int $scale = 2): string
    {
        return (string) $value->toScale($scale, RoundingMode::HALF_UP);
    }
}