<?php

namespace App\Livewire\Forms;

use App\Models\SaleDocumentItem;
use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Enums\Sunat\AffecType;


class   SaleItemForm extends Form
{
    #[Validate('required')]
    public $igvAffectationType = AffecType::GRAVADO->value;
    #[Validate('required')]
    public $code;
    #[Validate('required')]
    public $description;
    #[Validate('required')]
    public $unit;
    #[Validate('required|numeric|min:0.01')]
    public $quantity = 1;
    #[Validate('required|numeric|min:0')]
    public $unitValue = 0;
    #[Validate('required|numeric|min:0')]
    public $itemValue = 0;
    #[Validate('required|numeric|min:0.01')]
    public $unitPrice = 0;
    #[Validate('required')]
    public $igvBaseAmount = 0;
    #[Validate('required|numeric|min:0')]
    public $igvPercent = 18;
    #[Validate('required|numeric|min:0')]
    public $igvAmount = 0;
    #[Validate('required|numeric|min:0')]
    public $taxesTotal = 0;

    public function messages(): array
    {
        return [
            'igvAffectationType.required' => 'Debe seleccionar el tipo de afectación IGV.',
            'code.required' => 'El código del producto es obligatorio.',
            'description.required' => 'La descripción del producto es obligatoria.',
            'unit.required' => 'La unidad del producto es obligatoria.',

            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser numérica.',
            'quantity.min' => 'La cantidad no puede ser menor a 0.01.',

            'unitValue.required' => 'El valor unitario es obligatorio.',
            'unitValue.numeric' => 'El valor unitario debe ser numérico.',
            'unitValue.min' => 'El valor unitario no puede ser menor a 0.',

            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.numeric' => 'El precio unitario debe ser numérico.',
            'unitPrice.min' => 'El precio unitario no puede ser menor a 0.01.',

            'igvPercent.required' => 'El porcentaje de IGV es obligatorio.',
            'igvPercent.numeric' => 'El porcentaje de IGV debe ser numérico.',

            'igvAmount.required' => 'El monto de IGV es obligatorio.',
            'igvAmount.numeric' => 'El monto de IGV debe ser numérico.',

            'taxesTotal.required' => 'El total de impuestos es obligatorio.',
            'taxesTotal.numeric' => 'El total de impuestos debe ser numérico.',
        ];
    }
    public function validationAttributes(): array
    {
        return [
            'igvAffectationType' => 'tipo de afectación IGV',
            'code' => 'código',
            'description' => 'descripción',
            'unit' => 'unidad',
            'quantity' => 'cantidad',
            'unitValue' => 'valor unitario',
            'itemValue' => 'valor del item',
            'unitPrice' => 'precio unitario',
            'igvBaseAmount' => 'base imponible IGV',
            'igvPercent' => 'porcentaje IGV',
            'igvAmount' => 'monto IGV',
            'taxesTotal' => 'total de impuestos',
        ];
    }
    public function store(array $items, string $docId): void
    {
        $validatedItems = $this->validateItems($items);
        foreach ($validatedItems as $item) {
            SaleDocumentItem::create([
                'sale_document_id' => $docId,
                'igv_affectation_type' => $item['igvAffectationType'],
                'code' => $item['code'],
                'description' => $item['description'],
                'unit' => $item['unit'],
                'quantity' => $item['quantity'],

                'unit_value' => $item['unitValue'],
                'item_value' => $item['itemValue'],
                'unit_price' => $item['unitPrice'],

                'igv_base_amount' => $item['igvBaseAmount'],
                'igv_percent' => $item['igvPercent'],
                'igv_amount' => $item['igvAmount'],
                'total_taxes' => $item['taxesTotal'],
            ]);
        }
    }
    public function validateItems(array $items): array
    {
        $validatedItems = [];
        foreach ($items as $item) {
            $this->reset();
            $this->fill($item);
            $validatedItems[] = $this->validate();
        }
        return $validatedItems;
    }
}
