<?php

namespace App\Enums\Sunat;

enum DocSunatType: string
{
    case FACTURA = '01';        // Factura
    case BOLETA = '03';        // Boleta
    case NOTA_CREDITO = '07';    // Nota de crédito
    case NOTA_DEBITO = '08';     // Nota de débito

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}