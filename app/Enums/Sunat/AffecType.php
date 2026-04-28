<?php

namespace App\Enums\Sunat;
enum AffecType: string
{
    case GRAVADO = '10';        // Gravado
    case EXONERADO = '20';      // Exonerado
    case INAFECTO = '30';       // Inafecto
    case GRATUITO = '40';       // Gratuito

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}