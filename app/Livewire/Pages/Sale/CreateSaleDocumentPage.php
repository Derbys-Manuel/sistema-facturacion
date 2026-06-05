<?php

namespace App\Livewire\Pages\Sale;

use App\Enums\DocumentStatus;
use App\Enums\Sunat\CreditNoteReasonType;
use App\Enums\Sunat\DiscountType;
use App\Enums\Sunat\DocSunatType;
use App\Livewire\Forms\ClientForm;
use App\Livewire\Forms\DiscountForm;
use App\Livewire\Forms\SaleForm;
use App\Livewire\Forms\SaleItemForm;
use App\Models\SaleDocument;
use App\Services\SaleService;
use App\Services\SerieService;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateSaleDocumentPage extends Component
{
    public SaleForm $sale;

    public SaleItemForm $saleItem;

    public DiscountForm $discount;

    public ClientForm $client;

    public string $docSunatType = '';

    public ?string $bolClient = null;

    public array $items = [];

    public array $clients = [];

    public ?string $selectedClientLabel = null;

    public array $affectedDocTypeOptions = [];

    public array $reasonOptions = [];

    public array $affectedDocuments = [];

    public ?string $selectedAffectedLabel = null;

    public bool $pdfPreviewOpen = false;

    public ?string $pdfPreviewUrl = null;

    public ?string $savedSaleId = null;

    public ?string $editingSaleId = null;

    public function mount(SaleService $saleService, ?string $docSunatType = null): void
    {
        $this->docSunatType = $this->normalizeDocSunatType($docSunatType);

        $this->sale->docSunatType = $this->docSunatType;
        $this->sale->dateIssue = now('America/Lima')->format('Y-m-d H:i:s');
        $this->sale->dateExpiration = now('America/Lima')->format('Y-m-d H:i:s');

        if ($this->isCreditNote()) {
            $this->sale->affectedDocSunatType = $this->sale->affectedDocSunatType
                ?? DocSunatType::BOLETA->value;

            $this->affectedDocTypeOptions = [
                ['value' => DocSunatType::BOLETA->value, 'label' => 'Boleta'],
                ['value' => DocSunatType::FACTURA->value, 'label' => 'Factura'],
            ];

            $this->reasonOptions = CreditNoteReasonType::options();
        }

        if ($this->isBoleta()) {
            $this->bolClient = $this->bolClient ?? 'hide';
        }

        $editSaleId = request()->query('edit');
        $duplicateSaleId = request()->query('duplicate');
        $affectedSaleId = request()->query('affected');

        if ($this->isCreditNote() && filled($affectedSaleId)) {
            $this->selectAffectedDocument($saleService, (string) $affectedSaleId);

            return;
        }

        if (filled($editSaleId)) {
            $this->loadSaleIntoForm((string) $editSaleId, duplicate: false, saleService: $saleService);

            return;
        }

        if (filled($duplicateSaleId)) {
            $this->loadSaleIntoForm((string) $duplicateSaleId, duplicate: true, saleService: $saleService);
        }
    }

    private function normalizeDocSunatType(?string $docSunatType): string
    {
        $docSunatType = filled($docSunatType) ? (string) $docSunatType : null;

        if ($docSunatType === null) {
            $routeName = (string) (request()->route()?->getName() ?? '');

            $docSunatType = match ($routeName) {
                'create-factura' => DocSunatType::FACTURA->value,
                'create-nota-credito' => DocSunatType::NOTA_CREDITO->value,
                default => DocSunatType::BOLETA->value,
            };
        }

        if (! in_array($docSunatType, [
            DocSunatType::BOLETA->value,
            DocSunatType::FACTURA->value,
            DocSunatType::NOTA_CREDITO->value,
        ], true)) {
            abort(404);
        }

        return $docSunatType;
    }

    private function isBoleta(): bool
    {
        return $this->docSunatType === DocSunatType::BOLETA->value;
    }

    private function isFactura(): bool
    {
        return $this->docSunatType === DocSunatType::FACTURA->value;
    }

    private function isCreditNote(): bool
    {
        return $this->docSunatType === DocSunatType::NOTA_CREDITO->value;
    }

    private function loadSaleIntoForm(string $saleId, bool $duplicate, SaleService $saleService): void
    {
        $sale = SaleDocument::query()
            ->with(['items.discounts', 'discounts', 'client', 'company'])
            ->findOrFail($saleId);

        $docType = $sale->doc_sunat_type?->value ?? (string) ($sale->docSunatType?->value ?? $sale->docSunatType ?? '');
        if ($docType !== $this->docSunatType) {
            $queryKey = $duplicate ? 'duplicate' : 'edit';

            $this->redirect(match ($docType) {
                DocSunatType::BOLETA->value => route('create-boleta', [$queryKey => $saleId]),
                DocSunatType::FACTURA->value => route('create-factura', [$queryKey => $saleId]),
                DocSunatType::NOTA_CREDITO->value => route('create-nota-credito', [$queryKey => $saleId]),
                default => route('vouchers'),
            }, navigate: true);

            return;
        }

        if (! $duplicate) {
            if ($sale->status === DocumentStatus::APPROVED) {
                Flux::toast(
                    heading: 'Alerta',
                    text: 'No se puede editar un comprobante aprobado',
                    variant: 'warning',
                    duration: 3000
                );
                $this->redirectRoute('vouchers', navigate: true);

                return;
            }

            $this->editingSaleId = (string) $sale->id;
            $this->savedSaleId = (string) $sale->id;
        }

        $data = $sale->toArray();

        $this->sale->companyId = filled($data['companyId'] ?? null)
            ? (string) $data['companyId']
            : $this->sale->companyId;

        $this->sale->clientId = filled($data['clientId'] ?? null)
            ? (string) $data['clientId']
            : null;

        $this->sale->additionalInfo = $data['additionalInfo'] ?? null;

        if ($this->isCreditNote()) {
            $this->sale->affectedSaleDocumentId = (string) ($data['affectedSaleDocumentId'] ?? $this->sale->affectedSaleDocumentId);
            $this->sale->affectedDocSunatType = (string) ($data['affectedDocSunatType'] ?? $this->sale->affectedDocSunatType);
            $this->sale->affectedSerie = (string) ($data['affectedSerie'] ?? $this->sale->affectedSerie);
            $this->sale->affectedCorrelative = (string) ($data['affectedCorrelative'] ?? $this->sale->affectedCorrelative);
            $this->sale->noteReasonCode = (string) ($data['noteReasonCode'] ?? $this->sale->noteReasonCode);
            $this->sale->noteReasonDescription = (string) ($data['noteReasonDescription'] ?? $this->sale->noteReasonDescription);
        }

        if (! $duplicate) {
            $this->sale->dateIssue = $data['dateIssue'] ?? $this->sale->dateIssue;
            $this->sale->dateExpiration = $data['dateExpiration'] ?? $this->sale->dateExpiration;
        }

        $this->items = $saleService->hydrateItemsForSunatFromDatabase($data['items'] ?? []);

        $client = data_get($data, 'client');
        $clientId = (string) ($data['clientId'] ?? '');
        if ($clientId !== '' && is_array($client)) {
            $label = (string) ((string) ($client['name'] ?? '') ?: (string) ($client['tradeName'] ?? ''));
            $label = Str::limit($label, 12, '...').' - '.(string) ($client['documentNumber'] ?? '');

            if ($this->isBoleta()) {
                $this->bolClient = 'show';
            }

            $this->selectedClientLabel = $label;
            $this->clients = [
                ['value' => $clientId, 'label' => $label],
            ];
        } else {
            if ($this->isBoleta()) {
                $this->bolClient = 'hide';
            }

            $this->clearClient();
        }

        if ($this->isCreditNote() && filled($this->sale->affectedSaleDocumentId) && filled($this->sale->affectedSerie) && filled($this->sale->affectedCorrelative)) {
            $this->selectedAffectedLabel = $this->sale->affectedSerie.'-'.$this->sale->affectedCorrelative;
        }

        $this->fillSavedTotals($data);
    }

    public function editItem(int $index): void
    {
        $item = $this->items[$index] ?? null;

        if (! is_array($item)) {
            return;
        }

        $this->dispatch('edit-sale-item', index: $index, item: $item);

        Flux::modal('sale-item')->show();
    }

    public function deletedItem(int $index, SaleService $saleService): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        $saleService->applyTotals($this->sale, $this->items);
    }

    public function toggleGlobalDiscount(SaleService $saleService): void
    {
        $enabled = (bool) data_get($this->sale->discounts, '0.enabled', false);

        if ($enabled) {
            $this->sale->discounts = [];
        } else {
            $this->sale->discounts = [
                $saleService->newDiscount(DiscountType::GLOBAL->value),
            ];
        }

        $saleService->applyTotals($this->sale, $this->items);
    }

    public function recalculateGlobalDiscountFromAmount(SaleService $saleService): void
    {
        data_set($this->sale->discounts, '0.mode', 'amount');

        $saleService->applyTotals($this->sale, $this->items);
    }

    public function recalculateGlobalDiscountFromPercent(SaleService $saleService): void
    {
        data_set($this->sale->discounts, '0.mode', 'percent');

        $saleService->applyTotals($this->sale, $this->items);
    }

    public function searchClient(string $q = ''): void
    {
        if ($this->isCreditNote()) {
            $affectedDocSunatType = (string) ($this->sale->affectedDocSunatType ?? DocSunatType::BOLETA->value);

            $this->clients = $affectedDocSunatType === DocSunatType::FACTURA->value
                ? $this->client->searchWithoutDni($q)
                : $this->client->search($q);

            return;
        }

        $this->clients = $this->isFactura()
            ? $this->client->searchWithoutDni($q)
            : $this->client->search($q);
    }

    public function selectClient(?string $id = null, ?string $label = null): void
    {
        if (blank($id)) {
            return;
        }

        $this->sale->clientId = $id;

        if (filled($label)) {
            if (str_contains($label, ' - ')) {
                [$name, $documentNumber] = explode(' - ', $label, 2);
                $label = Str::limit(trim($name), 12, '...').' - '.trim($documentNumber);
            } else {
                $label = Str::limit($label, 12, '...');
            }
        }

        $this->selectedClientLabel = $label;
    }

    public function clearClient(): void
    {
        $this->sale->clientId = null;
        $this->selectedClientLabel = null;
        $this->clients = [];
    }

    public function updatedBolClient($value): void
    {
        if (! $this->isBoleta()) {
            return;
        }

        if ($value === 'hide') {
            $this->clearClient();
        }
    }

    public function updatedSaleAffectedDocSunatType(): void
    {
        if (! $this->isCreditNote()) {
            return;
        }

        $this->clearAffectedDocument();
    }

    public function searchAffectedDocument(string $q = ''): void
    {
        if (! $this->isCreditNote()) {
            return;
        }

        if (blank($this->sale->companyId)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar una empresa',
                variant: 'warning',
                duration: 2000
            );
            $this->affectedDocuments = [];

            return;
        }

        $this->affectedDocuments = $this->sale->searchAffectedDocuments(
            companyId: (string) $this->sale->companyId,
            affectedDocSunatType: $this->sale->affectedDocSunatType,
            q: $q,
        );
    }

    public function selectAffectedDocument(SaleService $saleService, ?string $id = null, ?string $label = null): void
    {
        if (! $this->isCreditNote()) {
            return;
        }

        if (blank($id)) {
            return;
        }

        $this->sale->affectedSaleDocumentId = $id;

        $sale = SaleDocument::query()
            ->with(['items.discounts', 'discounts', 'client', 'company'])
            ->findOrFail($id);

        if ($sale->status !== DocumentStatus::APPROVED) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Solo puede afectar comprobantes aprobados',
                variant: 'warning',
                duration: 2500
            );
            $this->clearAffectedDocument();

            return;
        }

        $data = $sale->toArray();
        $this->sale->affectedDocSunatType = (string) ($data['docSunatType'] ?? '');
        $this->sale->affectedSerie = (string) ($data['serie'] ?? '');
        $this->sale->affectedCorrelative = (string) ($data['correlative'] ?? '');
        $this->sale->companyId = (string) ($data['companyId'] ?? '');
        $this->sale->clientId = filled($data['clientId'] ?? null)
            ? (string) $data['clientId']
            : null;

        $this->selectedAffectedLabel = filled($label)
            ? $label
            : (string) (($data['serie'] ?? '').'-'.($data['correlative'] ?? ''));

        $client = $data['client'] ?? null;
        $clientLabel = null;

        if (is_array($client)) {
            $clientName = (string) (($client['name'] ?? '') ?: ($client['tradeName'] ?? ''));
            $clientDocumentNumber = (string) ($client['documentNumber'] ?? '');
            $clientLabel = Str::limit($clientName, 12, '...').' - '.$clientDocumentNumber;
        }

        $this->selectedClientLabel = $clientLabel;
        $this->clients = filled($this->sale->clientId) && filled($clientLabel)
            ? [
                [
                    'value' => $this->sale->clientId,
                    'label' => $clientLabel,
                ],
            ]
            : [];

        $this->items = $saleService->hydrateItemsForSunatFromDatabase($data['items'] ?? []);
        $this->fillSavedTotals($data);
    }

    private function fillSavedTotals(array $data): void
    {
        foreach ([
            'totalTaxed',
            'totalExempted',
            'totalUnaffected',
            'totalExport',
            'totalFree',
            'totalIgv',
            'totalIgvFree',
            'icbper',
            'totalTaxes',
            'saleValue',
            'subTotal',
            'totalSale',
            'rounding',
            'total',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $this->sale->{$key} = $data[$key];
            }
        }
    }

    public function clearAffectedDocument(): void
    {
        if (! $this->isCreditNote()) {
            return;
        }

        $this->sale->affectedSaleDocumentId = null;
        $this->sale->affectedSerie = null;
        $this->sale->affectedCorrelative = null;
        $this->selectedAffectedLabel = null;

        $this->sale->clientId = null;
        $this->selectedClientLabel = null;
        $this->affectedDocuments = [];
        $this->clients = [];

        $this->items = [];
        $this->sale->discounts = [];
        $this->sale->total = 0;
        $this->sale->saleValue = 0;
        $this->sale->totalTaxes = 0;
        $this->sale->totalIgv = 0;
    }

    public function resetForm(): void
    {
        $companyId = $this->isCreditNote() ? $this->sale->companyId : null;

        $this->sale->reset();
        $this->saleItem->reset();
        $this->discount->reset();

        if ($this->isCreditNote()) {
            $this->sale->companyId = $companyId;
        }

        $this->items = [];
        $this->clients = [];
        $this->selectedClientLabel = null;

        $this->affectedDocuments = [];
        $this->selectedAffectedLabel = null;

        $this->savedSaleId = null;
        $this->editingSaleId = null;

        if ($this->isBoleta()) {
            $this->bolClient = 'hide';
        }

        $this->sale->dateIssue = now('America/Lima')->format('Y-m-d H:i:s');
        $this->sale->dateExpiration = now('America/Lima')->format('Y-m-d H:i:s');
        $this->sale->docSunatType = $this->docSunatType;
        $this->sale->discounts = [];

        if ($this->isCreditNote()) {
            $this->sale->affectedDocSunatType = DocSunatType::BOLETA->value;
            $this->affectedDocuments = [];
        }
    }

    public function openPdfPreview(?string $url = null): void
    {
        $this->pdfPreviewUrl = filled($url) ? $url : null;
        $this->pdfPreviewOpen = filled($this->pdfPreviewUrl);
    }

    public function closePdfPreview(): void
    {
        $this->pdfPreviewOpen = false;
        $this->pdfPreviewUrl = null;
    }

    public function startNewDocument(): void
    {
        $this->closePdfPreview();
        $this->resetForm();
    }

    public function goToVouchers(): void
    {
        $this->closePdfPreview();
        $this->redirectRoute('vouchers', navigate: true);
    }

    #[On('sale-item-created')]
    public function saleItemCreated(array $item, SaleService $saleService): void
    {
        $this->items[] = $item;
        $saleService->applyTotals($this->sale, $this->items);
        $this->dispatch('reset-sale-item-modal');
    }

    #[On('sale-item-updated')]
    public function saleItemUpdated(int $index, array $item, SaleService $saleService): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $this->items[$index] = $item;
        $saleService->applyTotals($this->sale, $this->items);
        $this->dispatch('reset-sale-item-modal');
    }

    #[On('created-client')]
    public function clientCreated(array $client): void
    {
        $id = $client['id'];
        $label = ($client['name'] ?: $client['tradeName']);
        $label = Str::limit($label, 12, '...');
        $label = $label.' - '.$client['documentNumber'];

        $this->sale->clientId = $id;
        $this->selectedClientLabel = $label;

        $this->clients = [
            [
                'value' => $id,
                'label' => $label,
            ],
        ];
    }

    #[On('pdf-modal-closed')]
    public function resetFromModal(): void
    {
        $this->resetForm();
    }

    public function save(SerieService $serieService): void
    {
        if (! $this->sale->companyId) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar una empresa',
                variant: 'warning',
                duration: 2000
            );

            return;
        }

        if ($this->isFactura() && ! $this->sale->clientId) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar un cliente',
                variant: 'warning',
                duration: 2000
            );

            return;
        }

        if ($this->isCreditNote()) {
            if (! $this->sale->affectedSaleDocumentId) {
                Flux::toast(
                    heading: 'Alerta',
                    text: 'Debe seleccionar el documento afectado',
                    variant: 'warning',
                    duration: 2500
                );

                return;
            }

            if ($this->sale->affectedDocSunatType === DocSunatType::FACTURA->value && ! $this->sale->clientId) {
                Flux::toast(
                    heading: 'Alerta',
                    text: 'Debe de seleccionar un cliente',
                    variant: 'warning',
                    duration: 2500
                );

                return;
            }
        }

        try {
            $this->sale->items = $this->items;

            $result = filled($this->editingSaleId)
                ? $this->sale->updateExisting($this->editingSaleId, $this->saleItem, $this->discount)
                : $this->sale->store($this->saleItem, $serieService, $this->discount);

            Flux::toast(
                heading: 'Alerta',
                text: filled($this->editingSaleId)
                    ? 'El documento se actualizó con éxito'
                    : 'El documento se guardó con éxito',
                variant: 'success',
                duration: 3000
            );

            $this->savedSaleId = $result['saleId'];
            $this->openPdfPreview($result['pdfUrl'] ?? null);
            Flux::modal('confirm')->show();
        } catch (\Throwable $th) {
            Flux::toast(
                heading: 'Error',
                text: $th->getMessage() ?: match (true) {
                    $this->isCreditNote() => 'No se pudo guardar la nota de crédito',
                    $this->isFactura() => 'No se pudo guardar ni enviar el comprobante',
                    default => 'No se pudo guardar el comprobante',
                },
                variant: 'warning',
                duration: 4000
            );

            report($th);

            if ($this->isCreditNote() && app()->environment('testing')) {
                throw $th;
            }
        }
    }

    public function render()
    {
        return view('livewire.pages.sale.create-sale-document-page');
    }
}
