<?php
namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'borrador';
    case APPROVED = 'aprobada';
    case OBSERVED = 'observada';
    case REJECTED = 'rechazada';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

