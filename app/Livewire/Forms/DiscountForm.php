<?php

namespace App\Livewire\Forms;

use App\Models\Discount;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DiscountForm extends Form
{
    #[Validate('required')]
    public $type;

    #[Validate('required|numeric|min:0')]
    public $baseAmount = 0;

    // Envía 0.20 para 20% (no 20).
    #[Validate('required|numeric|min:0')]
    public $factorPorcentage = 0;

    #[Validate('required|numeric|min:0')]
    public $discountAmount = 0;

    public function messages(): array
    {
        return [
            'type.required' => 'Debe seleccionar el tipo de descuento.',

            'baseAmount.required' => 'La base del descuento es obligatoria.',
            'baseAmount.numeric' => 'La base del descuento debe ser numérica.',
            'baseAmount.min' => 'La base del descuento no puede ser menor a 0.',

            'factorPorcentage.required' => 'El factor del descuento es obligatorio.',
            'factorPorcentage.numeric' => 'El factor del descuento debe ser numérico.',
            'factorPorcentage.min' => 'El factor del descuento no puede ser menor a 0.',

            'discountAmount.required' => 'El monto del descuento es obligatorio.',
            'discountAmount.numeric' => 'El monto del descuento debe ser numérico.',
            'discountAmount.min' => 'El monto del descuento no puede ser menor a 0.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'type' => 'tipo de descuento',
            'baseAmount' => 'base del descuento',
            'factorPorcentage' => 'factor del descuento',
            'discountAmount' => 'monto del descuento',
        ];
    }

    public function store(array $discounts, ?string $saleDocumentId = null, ?string $saleDocumentItemId = null): void
    {
        $validatedDiscounts = $this->validateDiscounts($discounts);
        $now = now();
        $rows = [];

        foreach ($validatedDiscounts as $discount) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'type' => $discount['type'],
                'base_amount' => $discount['baseAmount'],
                'factor_porcentage' => $discount['factorPorcentage'],
                'discount_amount' => $discount['discountAmount'],
                'sale_document_id' => $saleDocumentId ?? null,
                'sale_document_item_id' => $saleDocumentItemId ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            Discount::query()->insert($rows);
        }
    }

    public function validateDiscounts(array $discounts): array
    {
        $validatedDiscounts = [];
        foreach ($discounts as $discount) {
            $this->reset();
            $this->fill($discount);
            $validatedDiscounts[] = $this->validate();
        }

        return $validatedDiscounts;
    }
}
