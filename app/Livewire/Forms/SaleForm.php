<?php

namespace App\Livewire\Forms;

use App\Enums\DocumentStatus;
use App\Enums\Sunat\DocSunatType;
use Illuminate\Support\Str;
use App\Models\SaleDocument;
use App\Services\SerieService;
use App\Services\SaleService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Enums\DocumentType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use Illuminate\Support\Facades\DB;
use App\Services\SunatService;
use App\Livewire\Forms\SaleItemForm;
use App\Livewire\Forms\DiscountForm;

class SaleForm extends Form
{
    #[Validate('required')]
    public $documentType = DocumentType::SALE->value;

    #[Validate('nullable|string|max:5')]
    public $ublVersion = '2.1';

    #[Validate('required')]
    public $docSunatType = '';

    #[Validate('nullable|string|required_if:docSunatType,07,08')]
    public ?string $affectedSaleDocumentId = null;

    #[Validate('nullable|string|max:2|required_if:docSunatType,07,08')]
    public ?string $affectedDocSunatType = null;

    #[Validate('nullable|string|max:4|required_if:docSunatType,07,08')]
    public ?string $affectedSerie = null;

    #[Validate('nullable|string|max:10|required_if:docSunatType,07,08')]
    public ?string $affectedCorrelative = null;

    #[Validate('nullable|string|max:2|required_if:docSunatType,07')]
    public ?string $noteReasonCode = null;

    #[Validate('nullable|string|max:255|required_if:docSunatType,07')]
    public ?string $noteReasonDescription = null;

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
    public $sunatState = true;

    #[Validate('nullable|string')]
    public ?string $companyId = null;

    #[Validate('nullable|string')]
    public ?string $clientId = null;
    #[Validate('nullable|array|min:1')]
    public array $items = [];

    #[Validate('nullable|array')]
    public ?array $discounts = null;

    public function messages(): array
    {
        return [
            'documentType.required' => 'Debe seleccionar el tipo de documento.',
            'docSunatType.required' => 'Debe seleccionar el comprobante.',

            'operationType.required' => 'Debe seleccionar el tipo de operación.',
            'paymentForm.required' => 'Debe seleccionar la forma de pago.',

            'dateIssue.required' => 'La fecha de emisión es obligatoria.',
            'dateExpiration.required' => 'La fecha de vencimiento es obligatoria.',

            'companyId.required' => 'Debe seleccionar una empresa.',

            'items.required' => 'Debe agregar productos.',
            'items.min' => 'Debe agregar al menos un producto.',
            'items.array' => 'Los productos enviados no son válidos.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'companyId' => 'empresa',
            'clientId' => 'cliente',
            'dateIssue' => 'fecha de emisión',
            'dateExpiration' => 'fecha de vencimiento',
            'items' => 'productos',
            'discounts' => 'descuentos',
            'affectedSaleDocumentId' => 'documento afectado',
            'affectedDocSunatType' => 'tipo de documento afectado',
            'affectedSerie' => 'serie afectada',
            'affectedCorrelative' => 'correlativo afectado',
            'noteReasonCode' => 'código de motivo',
            'noteReasonDescription' => 'descripción del motivo',
        ];
    }
    public function store(
        SaleItemForm $itemForm, 
        SerieService $serieService,
        DiscountForm $discountForm
    ): array
    {
        $data = $this->validate();
        $sale = DB::transaction(function () use (
            $data,
            $itemForm,
            $serieService,
            $discountForm,
        ) {
            $affectedDocSunatType = in_array(
                (string) ($data['docSunatType'] ?? ''),
                [DocSunatType::NOTA_CREDITO->value, DocSunatType::NOTA_DEBITO->value],
                true,
            ) ? (string) ($data['affectedDocSunatType'] ?? '') : null;

            $serie = $serieService->getSerieForUpdate(
                $data['docSunatType'],
                $data['companyId'],
                filled($affectedDocSunatType) ? $affectedDocSunatType : null,
            );
            $nextCorrelative = $serieService->nextCorrelative((string) $serie->correlative);
            $serie->update([
                'correlative' => $nextCorrelative,
            ]);
            $sale = SaleDocument::create([
                'document_type' => $data['documentType'],
                'ubl_version' => $data['ublVersion'],
                'doc_sunat_type' => $data['docSunatType'],
                'operation_type' => $data['operationType'],
                'payment_form' => $data['paymentForm'],
                'currency' => $data['currency'],
                'serie' => $serie->code,
                'correlative' => $nextCorrelative,
                'affected_sale_document_id' => $data['affectedSaleDocumentId'],
                'affected_doc_sunat_type' => $data['affectedDocSunatType'],
                'affected_serie' => $data['affectedSerie'],
                'affected_correlative' => $data['affectedCorrelative'],
                'note_reason_code' => $data['noteReasonCode'],
                'note_reason_description' => $data['noteReasonDescription'],
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
                'sunat_state'=> true
            ])->load('company', 'client');
            $saleDiscounts = $data['discounts'] ?? [];
            if (is_array($saleDiscounts) && collect($saleDiscounts)->contains(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)) {
                $discountForm->store(
                    discounts: $saleDiscounts,
                    saleDocumentId: (string) $sale->id,
                    saleDocumentItemId: null
                );
            }
            $itemForm->store($data['items'], (string) $sale->id, $discountForm);
            return $sale;
        });

        $data['serie'] = (string) ($sale->serie ?? '');
        $data['correlative'] = (string) ($sale->correlative ?? '');
        $data['discounts'] = collect($data['discounts'] ?? [])
            ->filter(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)
            ->values()
            ->all();
        $data['items'] = collect($data['items'] ?? [])
            ->map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }
                $item['discounts'] = collect($item['discounts'] ?? [])
                    ->filter(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)
                    ->values()
                    ->all();
                return $item;
            })
            ->all();
        return [
            'saleId' => (string) $sale->id,
            'pdfUrl' => route('sale.pdf', $sale->id),
        ];
    }

    public function updateExisting(
        string $saleId,
        SaleItemForm $itemForm,
        DiscountForm $discountForm,
    ): array {
        $data = $this->validate();
        $sale = DB::transaction(function () use ($saleId, $data, $itemForm, $discountForm) {
            $sale = SaleDocument::query()
                ->with(['items.discounts', 'discounts'])
                ->findOrFail($saleId);
            if ($sale->status === DocumentStatus::APPROVED) {
                throw new \RuntimeException('No se puede editar un comprobante aprobado.');
            }
            $sale->update([
                'document_type' => $data['documentType'],
                'ubl_version' => $data['ublVersion'],
                'doc_sunat_type' => $data['docSunatType'],
                'operation_type' => $data['operationType'],
                'payment_form' => $data['paymentForm'],
                'currency' => $data['currency'],
                'affected_sale_document_id' => $data['affectedSaleDocumentId'],
                'affected_doc_sunat_type' => $data['affectedDocSunatType'],
                'affected_serie' => $data['affectedSerie'],
                'affected_correlative' => $data['affectedCorrelative'],
                'note_reason_code' => $data['noteReasonCode'],
                'note_reason_description' => $data['noteReasonDescription'],
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
                'company_id' => $data['companyId'] ?? null,
                'client_id' => $data['clientId'] ?? null,

                // al editar, se deja listo para reenvío
                'xml' => null,
                'hash' => null,
                'cdr' => null,
                'status' => DocumentStatus::DRAFT->value,
                'sunat_state' => true,
            ]);
            $sale->discounts()->delete();
            foreach ($sale->items as $item) {
                $item->discounts()->delete();
            }
            $sale->items()->delete();
            $saleDiscounts = $data['discounts'] ?? [];
            if (
                is_array($saleDiscounts)
                && collect($saleDiscounts)->contains(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)
            ) {
                $discountForm->store(
                    discounts: $saleDiscounts,
                    saleDocumentId: (string) $sale->id,
                    saleDocumentItemId: null
                );
            }
            $itemForm->store($data['items'], (string) $sale->id, $discountForm);
            return $sale->load('company', 'client');
        });

        return [
            'saleId' => (string) $sale->id,
            'pdfUrl' => route('sale.pdf', $sale->id),
        ];
    }
    public function send(string $saleId, SunatService $sunatService, SaleService $saleService): array
    {
        $sale = SaleDocument::query()
            ->with(['items', 'client', 'company', 'items.discounts'])
            ->findOrFail($saleId);

        $data = $sale->toArray();
        $data['items'] = $saleService->hydrateItemsForSunatFromDatabase($data['items'] ?? []);

        $docSunatType = (string) ($data['docSunatType'] ?? '');

        if ($docSunatType === DocSunatType::NOTA_CREDITO->value) {
            $affectedSaleDocumentId = (string) ($data['affectedSaleDocumentId'] ?? '');
            if (filled($affectedSaleDocumentId)) {
                $affected = SaleDocument::query()->find($affectedSaleDocumentId);

                if ($affected) {
                    $creditNoteTotal = round((float) ($data['total'] ?? 0), 2);
                    $affectedTotal = round((float) ($affected->total ?? 0), 2);

                    if ($creditNoteTotal > $affectedTotal) {
                        return [
                            'sunat' => [
                                'success' => false,
                                'error' => "La nota de crédito no puede exceder el total del comprobante afectado ({$affectedTotal}).",
                            ],
                        ];
                    }
                }
            }
        }

        $response = $sunatService->send($data, $sale);
        $sunatSuccess = $response['sunatResponse']['success'] ?? false;
        $sale->update([
            'xml' => $response['xml'] ?? null,
            'hash' => $response['hash'] ?? null,
            'cdr' => $response['sunatResponse'] ?? null,
            'status' => $sunatSuccess
                ? DocumentStatus::APPROVED->value
                : DocumentStatus::REJECTED->value,
        ]);
        return ['sunat' => $response];
    }
    public function list(
        ?bool $deletedBool = null,
        ?string $from = null,
        ?string $to = null,
        ?string $q = null,
        ?string $docSunatType = null,
        ?string $companyId = null,
    ): array {
        return $this->documentsQuery(
            deletedBool: $deletedBool,
            from: $from,
            to: $to,
            q: $q,
            docSunatType: $docSunatType,
            companyId: $companyId,
        )
            ->with(['items', 'client', 'company'])
            ->latest('date_issue')
            ->paginate(15)
            ->toArray();
    }
    public function summary(
        ?bool $deletedBool = null,
        ?string $from = null,
        ?string $to = null,
        ?string $q = null,
        ?string $docSunatType = null,
        ?string $companyId = null,
    ): array {
        $query = $this->documentsQuery(
            deletedBool: $deletedBool,
            from: $from,
            to: $to,
            q: $q,
            docSunatType: $docSunatType,
            companyId: $companyId,
        );
        $creditNoteType = DocSunatType::NOTA_CREDITO->value;
        $total = (float) (clone $query)
            ->selectRaw('coalesce(sum(case when doc_sunat_type = ? then -total else total end),0) as signed_total', [$creditNoteType])
            ->value('signed_total');
        $boletas = (float) (clone $query)
            ->where('doc_sunat_type', DocSunatType::BOLETA->value)
            ->sum('total');
        $facturas = (float) (clone $query)
            ->where('doc_sunat_type', DocSunatType::FACTURA->value)
            ->sum('total');
        $totalIgv = (float) (clone $query)
            ->selectRaw('coalesce(sum(case when doc_sunat_type = ? then -total_igv else total_igv end),0) as signed_total_igv', [$creditNoteType])
            ->value('signed_total_igv');
        $saleValue = (float) (clone $query)
            ->selectRaw('coalesce(sum(case when doc_sunat_type = ? then -sale_value else sale_value end),0) as signed_sale_value', [$creditNoteType])
            ->value('signed_sale_value');
        return [
            'boletas' => $boletas,
            'facturas' => $facturas,
            'totalIgv' => $totalIgv,
            'saleValue' => $saleValue,
            'total' => $total,
        ];
    }

    private function documentsQuery(
        ?bool $deletedBool = null,
        ?string $from = null,
        ?string $to = null,
        ?string $q = null,
        ?string $docSunatType = null,
        ?string $companyId = null,
    ): Builder {
        $q = filled($q) ? trim((string) $q) : null;

        return SaleDocument::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(
                $deletedBool,
                fn ($query) => $query->where('sunat_state', false),
                fn ($query) => $query->where(function ($q) {
                    $q->where('sunat_state', true)
                    ->orWhereNull('sunat_state');
                })
            )
            ->when($from, fn ($query) => $query->whereDate('date_issue', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('date_issue', '<=', $to))
            ->when($docSunatType, fn ($query) => $query->where('doc_sunat_type', $docSunatType))
            ->when(
                $q,
                fn ($query) => $query->where(
                    fn ($subQuery) => $subQuery
                        ->whereRaw("(serie || '-' || correlative) ilike ?", ["%{$q}%"])
                        ->orWhereHas(
                            'company',
                            fn ($companyQuery) => $companyQuery->where(
                                fn ($companySubQuery) => $companySubQuery
                                    ->where('company_name', 'ilike', "%{$q}%")
                                    ->orWhere('ruc', 'ilike', "%{$q}%")
                            )
                        )
                        ->orWhereHas(
                            'client',
                            fn ($clientQuery) => $clientQuery->where(
                                fn ($clientSubQuery) => $clientSubQuery
                                    ->where('trade_name', 'ilike', "%{$q}%")
                                    ->orWhere('name', 'ilike', "%{$q}%")
                                    ->orWhere('document_number', 'ilike', "%{$q}%")
                            )
                        )
                )
            );
    }
    public function searchAffectedDocuments(string $companyId, ?string $affectedDocSunatType = null, string $q = ''): array
    {
        $affectedDocSunatType = (string) ($affectedDocSunatType ?: DocSunatType::BOLETA->value);
        $q = filled($q) ? trim((string) $q) : null;
        return SaleDocument::query()
            ->where('company_id', $companyId)
            ->where('doc_sunat_type', $affectedDocSunatType)
            ->where('status', DocumentStatus::APPROVED->value)
            ->when(
                $q,
                fn ($query) => $query->whereRaw("(serie || '-' || correlative) ilike ?", ["%{$q}%"])
            )
            ->latest('date_issue')
            ->limit(20)
            ->get()
            ->map(function (SaleDocument $sale) {
                $data = $sale->toArray();

                $number = (string) (($data['serie'] ?? '') . '-' . ($data['correlative'] ?? ''));

                return [
                    'value' => (string) ($data['id'] ?? ''),
                    'label' => $number,
                ];
            })
            ->values()
            ->toArray();
    }

}    
