<?php

use Livewire\Component;
use App\Enums\DocumentStatus;
use App\Enums\Sunat\CreditNoteReasonType;
use App\Enums\Sunat\DocSunatType;
use App\Livewire\Forms\ClientForm;
use App\Livewire\Forms\DiscountForm;
use App\Livewire\Forms\SaleForm;
use App\Livewire\Forms\SaleItemForm;
use App\Models\SaleDocument;
use App\Services\SaleService;
use App\Services\SerieService;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component
{
    public SaleForm $sale;
    public SaleItemForm $saleItem;
    public DiscountForm $discount;
    public ClientForm $client;

    public array $items = [];

    public array $affectedDocTypeOptions = [];
    public array $reasonOptions = [];
    public array $affectedDocuments = [];

    public ?string $selectedAffectedLabel = null;

    public array $clients = [];
    public ?string $selectedClientLabel = null;

    public bool $pdfPreviewOpen = false;
    public ?string $pdfPreviewUrl = null;
    public ?string $savedSaleId = null;
    public ?string $editingSaleId = null;

    public function mount(SaleService $saleService): void
    {
        $this->sale->docSunatType = DocSunatType::NOTA_CREDITO->value;
        $this->sale->dateIssue = now('America/Lima')->format('Y-m-d H:i:s');
        $this->sale->dateExpiration = now('America/Lima')->format('Y-m-d H:i:s');

        $this->sale->affectedDocSunatType = $this->sale->affectedDocSunatType
            ?? DocSunatType::BOLETA->value;

        $this->affectedDocTypeOptions = [
            ['value' => DocSunatType::BOLETA->value, 'label' => 'Boleta'],
            ['value' => DocSunatType::FACTURA->value, 'label' => 'Factura'],
        ];

        $this->reasonOptions = CreditNoteReasonType::options();

        $editSaleId = request()->query('edit');
        $duplicateSaleId = request()->query('duplicate');
        $affectedSaleId = request()->query('affected');

        if (filled($affectedSaleId)) {
            $this->selectAffectedDocument($saleService, (string) $affectedSaleId);
        } elseif (filled($editSaleId)) {
            $this->loadSaleIntoForm((string) $editSaleId, duplicate: false, saleService: $saleService);
        } elseif (filled($duplicateSaleId)) {
            $this->loadSaleIntoForm((string) $duplicateSaleId, duplicate: true, saleService: $saleService);
        }
    }

    private function loadSaleIntoForm(string $saleId, bool $duplicate, SaleService $saleService): void
    {
        $sale = SaleDocument::query()
            ->with(['items.discounts', 'discounts', 'client', 'company'])
            ->findOrFail($saleId);
        $docType = $sale->doc_sunat_type?->value ?? (string) ($sale->docSunatType?->value ?? $sale->docSunatType ?? '');
        if ($docType !== DocSunatType::NOTA_CREDITO->value) {
            $queryKey = $duplicate ? 'duplicate' : 'edit';
            $this->redirect(match ($docType) {
                DocSunatType::BOLETA->value => route('create-boleta', [$queryKey => $saleId]),
                DocSunatType::FACTURA->value => route('create-factura', [$queryKey => $saleId]),
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

        $this->sale->companyId = (string) ($data['companyId'] ?? $this->sale->companyId);
        $this->sale->clientId = (string) ($data['clientId'] ?? $this->sale->clientId);
        $this->sale->additionalInfo = $data['additionalInfo'] ?? null;

        $this->sale->affectedSaleDocumentId = (string) ($data['affectedSaleDocumentId'] ?? $this->sale->affectedSaleDocumentId);
        $this->sale->affectedDocSunatType = (string) ($data['affectedDocSunatType'] ?? $this->sale->affectedDocSunatType);
        $this->sale->affectedSerie = (string) ($data['affectedSerie'] ?? $this->sale->affectedSerie);
        $this->sale->affectedCorrelative = (string) ($data['affectedCorrelative'] ?? $this->sale->affectedCorrelative);
        $this->sale->noteReasonCode = (string) ($data['noteReasonCode'] ?? $this->sale->noteReasonCode);
        $this->sale->noteReasonDescription = (string) ($data['noteReasonDescription'] ?? $this->sale->noteReasonDescription);

        if (! $duplicate) {
            $this->sale->dateIssue = $data['dateIssue'] ?? $this->sale->dateIssue;
            $this->sale->dateExpiration = $data['dateExpiration'] ?? $this->sale->dateExpiration;
        }

        $this->items = $saleService->hydrateItemsForSunatFromDatabase($data['items'] ?? []);

        $client = data_get($data, 'client');
        $clientId = (string) ($data['clientId'] ?? '');

        if ($clientId !== '' && is_array($client)) {
            $label = (string) ((string) ($client['name'] ?? '') ?: (string) ($client['tradeName'] ?? ''));
            $label = Str::limit($label, 12, '...') . ' - ' . (string) ($client['documentNumber'] ?? '');
            $this->selectedClientLabel = $label;
            $this->clients = [
                ['value' => $clientId, 'label' => $label],
            ];
        } else {
            $this->selectedClientLabel = null;
            $this->clients = [];
        }
        if (filled($this->sale->affectedSaleDocumentId) && filled($this->sale->affectedSerie) && filled($this->sale->affectedCorrelative)) {
            $this->selectedAffectedLabel = $this->sale->affectedSerie . '-' . $this->sale->affectedCorrelative;
        }
        $saleService->applyTotals($this->sale, $this->items);
    }

    public function updatedSaleAffectedDocSunatType(): void
    {
        $this->clearAffectedDocument();
    }

    public function searchClient(string $q = ''): void
    {
        $affectedDocSunatType = (string) ($this->sale->affectedDocSunatType ?? DocSunatType::BOLETA->value);
        $this->clients = $affectedDocSunatType === DocSunatType::FACTURA->value
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
                $label = Str::limit(trim($name), 12, '...') . ' - ' . trim($documentNumber);
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

    public function searchAffectedDocument(string $q = ''): void
    {
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
            : (string) (($data['serie'] ?? '') . '-' . ($data['correlative'] ?? ''));

        $client = $data['client'] ?? null;
        $clientLabel = null;
        if (is_array($client)) {
            $clientName = (string) (($client['name'] ?? '') ?: ($client['tradeName'] ?? ''));
            $clientDocumentNumber = (string) ($client['documentNumber'] ?? '');

            $clientLabel = Str::limit($clientName, 12, '...') . ' - ' . $clientDocumentNumber;
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
        $saleService->applyTotals($this->sale, $this->items);
    }

    public function clearAffectedDocument(): void
    {
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

    public function editItem(int $index): void
    {
        $item = $this->items[$index] ?? null;
        if (! is_array($item)) {
            return;
        }
        $this->dispatch('edit-sale-item', index: $index, item: $item);
        Flux::modal('sale-item')->show();
    }

    public function deletedItem(SaleService $saleService, int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $saleService->applyTotals($this->sale, $this->items);
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

    public function startNewCreditNote(): void
    {
        $this->closePdfPreview();
        $this->resetForm();
    }

    public function goToVouchers(): void
    {
        $this->closePdfPreview();
        $this->redirectRoute('vouchers', navigate: true);
    }

    public function resetForm(): void
    {
        $this->sale->reset();
        $this->saleItem->reset();
        $this->discount->reset();

        $this->items = [];
        $this->affectedDocuments = [];
        $this->selectedAffectedLabel = null;

        $this->clients = [];
        $this->selectedClientLabel = null;

        $this->savedSaleId = null;
        $this->editingSaleId = null;

        $this->sale->dateIssue = now('America/Lima')->format('Y-m-d H:i:s');
        $this->sale->dateExpiration = now('America/Lima')->format('Y-m-d H:i:s');
        $this->sale->docSunatType = DocSunatType::NOTA_CREDITO->value;
        $this->sale->discounts = [];
        $this->sale->affectedDocSunatType = DocSunatType::BOLETA->value;
    }

    #[On('sale-item-created')]
    public function saleItemCreated(SaleService $saleService, array $item): void
    {
        $this->items[] = $item;
        $saleService->applyTotals($this->sale, $this->items);
        $this->dispatch('reset-sale-item-modal');
    }

    #[On('sale-item-updated')]
    public function saleItemUpdated(SaleService $saleService, int $index, array $item): void
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
        $label = $label . ' - ' . $client['documentNumber'];
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
        if (! $this->sale->affectedSaleDocumentId) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe seleccionar el documento afectado',
                variant: 'warning',
                duration: 2500
            );
            return;
        }
        if ($this->sale->affectedDocSunatType === DocSunatType::FACTURA->value) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar un cliente',
                variant: 'warning',
                duration: 2500
            );
            return;
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
                text: $th->getMessage() ?: 'No se pudo guardar la nota de crédito',
                variant: 'warning',
                duration: 4000
            );
            report($th);
            if (app()->environment('testing')) {
                throw $th;
            }
        }
    }
};
?>
<div>
    <div class="grid gap-4 grid-cols-[4fr_2.5fr] h-[88vh] overflow-auto scrollbar-thin-stable">
        <section class="flex flex-col overflow-auto rounded-sm bg-white scrollbar-thin-stable">
            <div class="space-y-3 mb-2">
                <div class="flex justify-end">
                    <flux:modal.trigger name="sale-item">
                        <x-form.button
                            variant="success"
                            type="button"
                            leftIcon="plus"
                            size="sm"
                        >
                            Agregar
                        </x-form.button>
                    </flux:modal.trigger>
                </div>
            </div>
            <div class="flex-1">
                <x-ui.table striped dense scroll-class="h-[55vh]">
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Descripción</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Cant.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Precio u.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Total</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </x-slot>
                    @forelse ($items as $index => $item)
                        <tr wire:key="item-{{ $index }}">
                            <x-ui.table.cell dense>
                                <div class="truncate font-medium text-zinc-800">
                                    {{ $item['description'] ?? '-' }}
                                </div>
                                <div class="mt-0.5 truncate text-xs text-zinc-500 font-mono">
                                    {{ $item['code'] ?? '-' }}
                                </div>
                            </x-ui.table.cell>
                            <x-ui.table.cell dense>
                                <span class="text-sm tabular-nums">
                                    {{ number_format((float) ($item['quantity'] ?? 0), 4) }}
                                </span>
                            </x-ui.table.cell>
                            <x-ui.table.cell dense>
                                <span class="text-sm tabular-nums">
                                    S/ {{ number_format((float) ($item['unitPrice'] ?? 0), 2) }}
                                </span>
                            </x-ui.table.cell>
                            <x-ui.table.cell dense>
                                <span class="text-sm font-semibold tabular-nums">
                                    S/ {{ number_format((float) ($item['total'] ?? 0), 2) }}
                                </span>
                            </x-ui.table.cell>
                            <x-ui.table.cell dense class="text-right">
                                <div class="flex justify-end gap-1">
                                    <x-form.button
                                        variant="ghost"
                                        size="sm"
                                        type="button"
                                        leftIcon="pencil"
                                        wire:click="editItem({{ $index }})"
                                    />
                                    <x-form.button
                                        variant="danger"
                                        size="sm"
                                        type="button"
                                        leftIcon="trash"
                                        wire:click="deletedItem({{ $index }})"
                                    />
                                </div>
                            </x-ui.table.cell>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">
                                No ha agregado ningún producto
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
                <div class="px-4 py-0 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">Base imponible</span>
                        <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                            S/ {{ number_format((float) ($sale->saleValue ?? 0), 2) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">Total IGV</span>
                        <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                            S/ {{ number_format((float) ($sale->totalTaxes ?? 0), 2) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-zinc-200 pt-2 mt-2">
                        <span class="text-sm font-semibold text-zinc-800">Total nota</span>
                        <div class="rounded-sm bg-emerald-600 px-3 py-1 text-sm font-bold text-white tabular-nums">
                            S/ {{ number_format((float) ($sale->total ?? 0), 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <form wire:submit.prevent="save" class="contents" id="sale-form">
            <aside class="scrollbar-thin-stable flex flex-col overflow-auto rounded-sm shadow-inner border border-zinc-200/80 bg-white">
                <div class="bg-gray-100/70 px-5 py-4 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100/80 text-emerald-700 ring-1 ring-sky-200/50">
                            <flux:icon.document-text class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold tracking-tight text-zinc-800">
                                Datos de nota de crédito
                            </h2>
                            <p class="text-xs text-zinc-500">
                                Documento afectado, cliente y motivo
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex-1 space-y-2 overflow-auto pt-4 pl-4 pb-4 pr-2 scrollbar-thin-stable">
                    <div class="grid grid-cols-[1fr_0.6fr] gap-3">                        
                        <x-form.select
                            label="Tipo doc. afect."
                            type="simple"
                            icon-left="document-text"
                            wire:model.live="sale.affectedDocSunatType"
                            :options="$affectedDocTypeOptions"
                            :error="$errors->first('sale.affectedDocSunatType')"
                        />
                        <x-form.select
                            wire:key="affected-document-select-{{ $sale->affectedSaleDocumentId ?? 'empty' }}"
                            label="Documento afectado"
                            placeholder="Buscar comprobante..."
                            search-placeholder="Escribe serie-correlativo"
                            icon-left="magnifying-glass"
                            :clearable="true"
                            :options="$affectedDocuments"
                            :selected-label="$selectedAffectedLabel"
                            search-action="searchAffectedDocument"
                            select-action="selectAffectedDocument"
                            clear-action="clearAffectedDocument"
                            :error="$errors->first('sale.affectedSaleDocumentId')"
                        />
                    </div>
                    <x-form.select
                        label="Motivo"
                        type="simple"
                        placeholder="Seleccionar motivo..."
                        icon-left="document-text"
                        wire:model.defer="sale.noteReasonCode"
                        :options="$reasonOptions"
                        :error="$errors->first('sale.noteReasonCode')"
                    />
                    <x-form.input
                        label="Descripción del motivo"
                        wire:model.defer="sale.noteReasonDescription"
                        placeholder="Ej: Anulación por devolución..."
                        icon-left="document-text"
                        :error="$errors->first('sale.noteReasonDescription')"
                    />
                    <div class="grid grid-cols-[1fr_auto] gap-3 items-end">
                        <x-form.select
                            wire:key="client-select-{{ $sale->clientId ?? 'empty' }}"
                            type="backend"
                            label="Cliente"
                            placeholder="Buscar cliente..."
                            search-placeholder="Escribe nombre o documento..."
                            icon-left="user"
                            :clearable="true"
                            :options="$clients"
                            :selected-label="$selectedClientLabel"
                            search-action="searchClient"
                            select-action="selectClient"
                            clear-action="clearClient"
                            :error="$errors->first('sale.clientId')"
                        />
                        <flux:modal.trigger name="client-create">
                            <x-form.button
                                variant="success"
                                size="icon"
                                type="button"
                                leftIcon="plus"
                            />
                        </flux:modal.trigger>
                    </div>
                    <x-form.date
                        label="Fecha de emisión"
                        wire:model.defer="sale.dateIssue"
                        :error="$errors->first('sale.dateIssue')"
                    />
                </div>
                <div class="bg-gray-100/70 px-4 py-3">
                    <div class="flex gap-2">
                        <x-form.button
                            variant="ghost"
                            type="button"
                            class="flex-1"
                            wire:loading.attr="disabled"
                            wire:click="resetForm"
                        >
                            Limpiar
                        </x-form.button>
                        <x-form.button
                            variant="success"
                            type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 min-h-10"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >
                            <span wire:loading.remove wire:target="save">
                                Guardar
                            </span>
                            <span
                                wire:loading.flex
                                wire:target="save"
                                class="hidden items-center justify-center gap-2"
                            >
                                <flux:icon.loading class="size-4 animate-spin" />
                                <span>Guardando...</span>
                            </span>
                        </x-form.button>
                    </div>
                </div>
            </aside>
        </form>
    </div>

    <flux:modal
        name="client-create"
        class="max-w-lg bg-gray-100"
        scroll="body"
        :dismissible="false"
        :closable="false"
    >
        <livewire:client.create />
    </flux:modal>

    <flux:modal
        name="sale-item"
        class="max-w-lg bg-gray-100"
        scroll="body"
        :dismissible="false"
        :closable="false"
    >
        <livewire:sale.modal-item />
    </flux:modal>

    <x-sale.pdf-preview-modal
        :open="$pdfPreviewOpen"
        :url="$pdfPreviewUrl"
        new-label="Ingresar nueva nota de crédito"
        new-action="startNewCreditNote"
        list-action="goToVouchers"
    />

    <livewire:send-modal :sale-id="$savedSaleId" :key="'send-modal-'.($savedSaleId ?? 'none')" />
</div>

@script
<script>
    const form = $wire.$el.querySelector('#sale-form')
    const syncCompany = (id) => {
        const companyId = id ?? localStorage.getItem('company-selector')
        if (companyId) {
            $wire.$set('sale.companyId', companyId, false)
        }
    }
    syncCompany()
    window.addEventListener('company-selected', (e) => {
        syncCompany(e?.detail?.id ?? null)
    })
    window.addEventListener('storage', (e) => {
        if (e.key === 'company-selector') {
            syncCompany(e.newValue)
        }
    })
    if (form && ! form.dataset.companySelectorBound) {
        form.dataset.companySelectorBound = '1'
        form.addEventListener('submit', () => {
            syncCompany()
        }, { capture: true })
    }
</script>
@endscript
