<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Enums\Sunat\AffecType;


class SaleItemForm extends Form
{
    #[Validate('required')]
    public $igvAffectationType = AffecType::GRAVADO;
    #[Validate('required')]
    public $code;
    #[Validate('required')]
    public $description;
    #[Validate('required')]
    public $unit;
    #[Validate('required|numeric|min:0')]
    public $quantity = 1.00;
    #[Validate('required|numeric|min:0')]
    public $unitValue = 0.00;
    #[Validate('required|numeric|min:0')]
    public $itemValue = 0.00;
    #[Validate('required|numeric|min:0')]
    public $unitPrice = 0.00;
    #[Validate('required')]
    public $igvBaseAmount = 0.00;
    #[Validate('required|numeric|min:0')]
    public $igvPercent = 0.00;
    #[Validate('required|numeric|min:0')]
    public $igvAmount = 0.00;
    #[Validate('required|numeric|min:0')]
    public $taxesTotal = 0.00;
}
