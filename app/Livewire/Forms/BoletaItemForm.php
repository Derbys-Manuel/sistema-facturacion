<?php

namespace App\Livewire\Forms;

use App\Models\SaleDocumentItem;
use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Enums\Sunat\AffecType;


class BoletaItemForm extends Form
{
    #[Validate('required')]
    public $igvAffectationType = AffecType::GRAVADO->value;
    #[Validate('required')]
    public $code;
    #[Validate('required')]
    public $description;
    #[Validate('required')]
    public $unit;
    #[Validate('required|numeric|min:0')]
    public $quantity = 1;
    #[Validate('required|numeric|min:0')]
    public $unitValue = 0;
    #[Validate('required|numeric|min:0')]
    public $itemValue = 0;
    #[Validate('required|numeric|min:0')]
    public $unitPrice = 0;
    #[Validate('required')]
    public $igvBaseAmount = 0;
    #[Validate('required|numeric|min:0')]
    public $igvPercent = 18;
    #[Validate('required|numeric|min:0')]
    public $igvAmount = 0;
    #[Validate('required|numeric|min:0')]
    public $taxesTotal = 0;

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
