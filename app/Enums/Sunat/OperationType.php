<?php

namespace App\Enums\Sunat;
enum OperationType: string
{
    case INTERNAL_SALE = '0101';     // Venta interna
    case EXPORT = '0200';            // Exportación
    case FREE_TRANSFER = '1001';     // Transferencia gratuita
    case CONSIGNMENT = '1002';       // Consignación
    case SALE_OF_FIXED_ASSETS = '1003';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}