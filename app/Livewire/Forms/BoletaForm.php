<?php

namespace App\Livewire\Forms;

use App\Enums\DocumentStatus;
use App\Models\SaleDocument;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Enums\DocumentType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use Illuminate\Support\Facades\DB;
use App\Services\SaleCreateService;
use App\Services\SunatService;
use App\Livewire\Forms\BoletaItemForm;
use Flux\Flux;

class BoletaForm extends Form
{
    #[Validate('required')]
    public $documentType = DocumentType::SALE->value;

    #[Validate('nullable|string|max:5')]
    public $ubl_version = '2.1';

    #[Validate('required')]
    public $docSunatType = DocSunatType::BOLETA->value;

    #[Validate('required')]
    public $operationType = OperationType::INTERNAL_SALE->value;

    #[Validate('required')]
    public $paymentForm = PaymentForm::CASH->value;

    #[Validate('nullable|string|max:10')]
    public $currency = 'PEN';
                
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

    #[Validate('required')]
    public $dateIssue;

    #[Validate('required')]
    public $dateExpiration;

    #[Validate('nullable|string')]
    public $additionalInfo = null;

    #[Validate('boolean')]
    public $isActive = true;

    #[Validate('nullable|string')]
    public ?string $companyId;

    #[Validate('nullable|string')]
    public ?string $clientId;
    #[Validate('required|array|min:1')]
    public array $items = [];
 
    public function store(BoletaItemForm $itemForm){
        DB::beginTransaction();
        try {
            $sunatService = app(SunatService::class);            
            $data = $this->validate();
            $sale = SaleDocument::create([
                'document_type' => $data['documentType'],
                'ubl_version' => $data['ubl_version'],
                'doc_sunat_type' => $data['docSunatType'],
                'operation_type' => $data['operationType'],
                'payment_form' => $data['paymentForm'],
                'currency' => $data['currency'],

                'serie' => 'FF01',
                'correlative' => '0001',

                'credit_days' => $data['creditDays'],
                'num_quota' => $data['numQuota'],
                'total_taxed' => $data['totalTaxed'],
                'total_exempted' => $data['totalExempted'],
                'total_unaffected' => $data['totalUnaffected'],
                'total_export' => $data['totalExport'],
                'total_free' => $data['totalFree'],
                'total_igv' => $data['totalIgv'],
                'total_igv_free' => $data['totalIgvFree'],
                'icbper' => $data['icbper'],
                'total_taxes' => $data['totalTaxes'],
                'sale_value' => $data['saleValue'],
                'sub_total' => $data['subTotal'],
                'total_sale' => $data['totalSale'],
                'rounding' => $data['rounding'],
                'total' => $data['total'],

                'date_issue' => $data['dateIssue'],
                'date_expiration' => $data['dateExpiration'],
                'additional_info' => $data['additionalInfo'],
                'status' => DocumentStatus::DRAFT->value,
                'company_id' => $data['companyId'] ?? null,
                'client_id' => $data['clientId'] ?? null,                            
            ]); 

            $itemForm->store($data['items'], $sale->id);
            $response = $sunatService->send($data);
            $sunatSuccess = $response['sunatResponse']['success'] ?? false;
            $sale->update([
                'xml' => $response['xml'] ?? null,
                'hash' => $response['hash'] ?? null,
                'cdr' => $response['sunatResponse'] ?? null,
                'status' => $sunatSuccess
                    ? DocumentStatus::APPROVED->value
                    : DocumentStatus::REJECTED->value,
            ]);
            DB::commit();
            Flux::toast(
                heading: $sunatSuccess ? 'SUNAT' : 'Comprobante rechazado',
                text: $sunatSuccess
                    ? 'Comprobante aceptado por SUNAT'
                    : ($response['sunatResponse']['error']['message'] ?? 'SUNAT rechazó el comprobante'),
                variant: $sunatSuccess ? 'success' : 'warning',
                duration: 4000
            );
            return [
                'sale' => $sale,
                'sunat' => $response,
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            Flux::toast(
                heading: 'Error',
                text: 'No se pudo guardar ni enviar el comprobante',
                variant: 'error'            
            );
            throw $th;
        }
    }
}    