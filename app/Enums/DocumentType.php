<?php
namespace App\Enums;

enum DocumentType: string
{
    case SALE = 'sale';
    case CREDIT_NOTE = 'credit_note';
    case DEBIT_NOTE = 'debit_note';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

