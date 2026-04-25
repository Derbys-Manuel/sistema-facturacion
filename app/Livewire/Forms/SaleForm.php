<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Enums\DocumentType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;

class SaleForm extends Form
{
    #[Validate('required')]
    public $documentType = DocumentType::SALE;

    #[Validate('nullable|string|max:5')]
    public $ubl_version = '2.1';

    #[Validate('required')]
    public $docSunatType = DocSunatType::BOLETA;

    #[Validate('required')]
    public $operationType = OperationType::INTERNAL_SALE;

    #[Validate('required')]
    public $paymentForm = PaymentForm::CASH;

    #[Validate('nullable|string|max:10')]
    public $currency = 'PEN';

    #[Validate('required|string|max:4')]
    public $serie = '';

    #[Validate('required|string|max:10')]
    public $correlative = '';

    #[Validate('nullable|integer|min:0')]
    public $creditDays = null;

    #[Validate('nullable|integer|min:0')]
    public $numQuota = null;

    #[Validate('numeric|min:0')]
    public $totalTaxed = 0;

    #[Validate('numeric|min:0')]
    public $totalExempted = 0;

    #[Validate('numeric|min:0')]
    public $totalUnaffected = 0;

    #[Validate('numeric|min:0')]
    public $totalExport = 0;

    #[Validate('numeric|min:0')]
    public $totalFree = 0;

    #[Validate('numeric|min:0')]
    public $totalIgv = 0;

    #[Validate('numeric|min:0')]
    public $totalIgvFree = 0;

    #[Validate('numeric|min:0')]
    public $icbper = 0;

    #[Validate('numeric|min:0')]
    public $totalTaxes = 0;

    #[Validate('numeric|min:0')]
    public $saleValue = 0;

    #[Validate('numeric|min:0')]
    public $subTotal = 0;

    #[Validate('numeric|min:0')]
    public $totalSale = 0;

    #[Validate('numeric')]
    public $rounding = 0;

    #[Validate('numeric|min:0')]
    public $total = 0;

    #[Validate('nullable|boolean')]
    public $sunatState = null;

    #[Validate('nullable|string|max:255')]
    public $hash = null;

    #[Validate('nullable|string')]
    public $xml = null;

    #[Validate('nullable|array')]
    public $cdr = null;

    #[Validate('nullable|array')]
    public $legends = null;

    #[Validate('required')]
    public $dateIssue;

    #[Validate('required')]
    public $dateExpiration;

    #[Validate('nullable|string')]
    public $additionalInfo = null;

    #[Validate('boolean')]
    public $isActive = true;

    #[Validate('required')]
    public $companyId;

    #[Validate('required')]
    public $clientId;
}