<?php

namespace App\Enums\Sunat;

enum CreditNoteReasonType: string
{
    case CANCEL_OPERATION = '01';
    case CANCEL_RUC_ERROR = '02';
    case DESCRIPTION_ERROR = '03';
    case GLOBAL_DISCOUNT = '04';
    case ITEM_DISCOUNT = '05';
    case TOTAL_RETURN = '06';
    case ITEM_RETURN = '07';
    case BONUS = '08';
    case VALUE_DECREASE = '09';
    case OTHER_CONCEPTS = '10';
    case EXPORT_OPERATION_ADJUSTMENT = '11';
    case IVAP_ADJUSTMENT = '12';
    case PAYMENT_NET_AMOUNT_CORRECTION = '13';

    public function label(): string
    {
        return match ($this) {
            self::CANCEL_OPERATION => 'Anulación de la operación',
            self::CANCEL_RUC_ERROR => 'Anulación por error en el RUC',
            self::DESCRIPTION_ERROR => 'Corrección por error en la descripción',
            self::GLOBAL_DISCOUNT => 'Descuento global',
            self::ITEM_DISCOUNT => 'Descuento por ítem',
            self::TOTAL_RETURN => 'Devolución total',
            self::ITEM_RETURN => 'Devolución por ítem',
            self::BONUS => 'Bonificación',
            self::VALUE_DECREASE => 'Disminución en el valor',
            self::OTHER_CONCEPTS => 'Otros conceptos',
            self::EXPORT_OPERATION_ADJUSTMENT => 'Ajustes de operaciones de exportación',
            self::IVAP_ADJUSTMENT => 'Ajustes afectos al IVAP',
            self::PAYMENT_NET_AMOUNT_CORRECTION => 'Corrección del monto neto pendiente de pago y/o fechas de vencimiento',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}