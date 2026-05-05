<?php

namespace App\Enums\Sunat;

enum DiscountType: string
{
    case ITEM = '00';         // descuento item
    case GLOBAL = '02';         // descuento global
    case ADVANCE = '04';    // descuento por anticipo de pago

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}