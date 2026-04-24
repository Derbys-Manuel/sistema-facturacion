<?php

namespace App\Enums\Sunat;

enum DocIdentityType: string
{
    case DNI = '1';         // Documento Nacional de Identidad
    case RUC = '6';         // Registro Único de Contribuyentes
    case PASSPORT = '7';    // Pasaporte
    case FOREIGN_CARD = '4';// Carné de extranjería

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}