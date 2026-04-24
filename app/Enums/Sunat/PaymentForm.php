<?php

namespace App\Enums\Sunat;

enum PaymentForm: string
{
    case CASH = 'contado';
    case CREDIT = 'credito';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}