<?php

use Livewire\Component;
use App\Enums\Sunat\DocSunatType;
use App\Livewire\Forms\SaleForm;
use App\Livewire\Forms\SaleItemForm;
use App\Livewire\Forms\ClientForm;
use App\Livewire\Forms\ProductForm;
use App\Services\SaleService;
use App\Services\SunatService;
use App\Services\SerieService;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component
{
    public SaleForm $sale;
    public SaleItemForm $saleItem;
    public ClientForm $client;
    public ProductForm $product;
    public array $items = [];
    public array $products = [];
    public array $clients = [];
    public ?string $selectedClientLabel = null;
    public bool $pdfPreviewOpen = false;
    public ?string $pdfPreviewUrl = null;

    public function mount(): void
    {
        $this->sale->docSunatType = DocSunatType::FACTURA->value;
        $this->sale->dateIssue = now()->format('d-m-Y H:i:s');
        $this->sale->dateExpiration = now()->format('d-m-Y H:i:s');
    }

    public function recalculateItemFromTotal(int $index, SaleService $saleService): void
    {
        $item = $this->items[$index] ?? null;
        if (! is_array($item)) return;
        $this->items[$index] = $saleService->calculateItemFromTotal($item);
        $saleService->applyTotals($this->sale, $this->items);
    }

    public function recalculateItem(int $index, SaleService $saleService): void
    {
        $item = $this->items[$index] ?? null;
        $this->items[$index] = $saleService->calculateItem($item);
        $saleService->applyTotals($this->sale, $this->items);
    }

    public function searchClient(string $q = ''): void
    {
        $this->clients = $this->client->searchWithoutDni($q);
    }

    public function searchProduct(string $q = ''): void
    {
        $this->products = $this->product->search($q);
    }

    public function deletedItem(int $index, SaleService $saleService): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $saleService->applyTotals($this->sale, $this->items);
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
                $label = Str::limit(trim($name), 15, '...') . ' - ' . trim($documentNumber);
            } else {
                $label = Str::limit($label, 15, '...');
            }
        }
        $this->selectedClientLabel = $label;
    }

    public function selectProduct(?string $id, ?string $label, SaleService $saleService): void
    {
        if (blank($id)) {
            return;
        }
        $record = $this->product->getRecord($id);
        if (! $record) {
            return;
        }
        $this->saleItem->description = $record->name;
        $this->saleItem->code = $record->sku ?? "00000";
        $this->saleItem->unit = $record->unit ?? "NIU";
        $this->saleItem->quantity = 1;
        $this->saleItem->unitValue = $record->price ?? 0;
        $this->saleItem->unitPrice = $record->price ?? 0;
        
        $this->items = $saleService->addItem($this->items, $this->saleItem);
        $saleService->applyTotals($this->sale, $this->items);
        $this->saleItem->reset();
        $this->products = [];
    }

    public function clearClient(): void
    {
        $this->sale->clientId = null;
        $this->selectedClientLabel = null;
        $this->clients = [];
    }
    public function resetForm(): void
    {
        $this->sale->reset();
        $this->saleItem->reset();
        $this->items = [];
        $this->products = [];
        $this->clients = [];
        $this->selectedClientLabel = null;
        $this->sale->dateIssue = now()->format('Y-m-d H:i:s');
        $this->sale->dateExpiration = now()->format('Y-m-d H:i:s');
        $this->sale->docSunatType = DocSunatType::FACTURA->value;
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

    public function startNewInvoice(): void
    {
        $this->closePdfPreview();
        $this->resetForm();
    }

    public function goToVouchers(): void
    {
        $this->closePdfPreview();
        $this->redirectRoute('vouchers', navigate: true);
    }

    #[On('created-client')]
    public function clientCreated(array $client): void
    {
        $id = $client['id'];
        $label = ($client['name'] ?: $client['tradeName']);
        $label = Str::limit($label, 15, '...');
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
    #[On('created-product')]
    public function productCreated(array $product, SaleService $saleService): void
    {
        $this->selectProduct($product['id'] ?? null, null, $saleService);
    }

    public function save(SunatService $sunatService, SerieService $serieService): void
    {
        if(!$this->sale->companyId){
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar una empresa',
                variant: 'warning',
                duration:2000            
            );
            return;
        }
        if(!$this->sale->clientId){
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar un cliente',
                variant: 'warning',
                duration:2000            
            );
            return;
        }
        try {
            $this->sale->items = $this->items;
            $result = $this->sale->store(
                $this->saleItem,
                $sunatService,
                $serieService
            );
            $response = $result['sunat'] ?? [];
            $sunatSuccess = $response['sunatResponse']['success'] ?? false;
            Flux::toast(
                heading: $sunatSuccess ? 'SUNAT' : 'Comprobante rechazado',
                text: $sunatSuccess
                    ? 'Comprobante aceptado por SUNAT'
                    : ($response['sunatResponse']['error']['message'] ?? 'SUNAT rechazó el comprobante'),
                variant: $sunatSuccess ? 'success' : 'warning',
                duration: 4000
            );
            $this->openPdfPreview($result['pdfUrl'] ?? null);
            } catch (\Throwable $th) {
                Flux::toast(
                    heading: 'Error',
                    text: $th->getMessage() ?: 'No se pudo guardar ni enviar el comprobante',
                    variant: 'warning',
                    duration: 4000
                );
                report($th);
        }
    }
};
?>
<div>
    <div class="grid gap-4 grid-cols-[4fr_2.5fr] h-[88vh]">
        <section class="flex flex-col overflow-hidden rounded-sm bg-white">
            <div class="space-y-3 mb-3">
                <div class="grid grid-cols-[1fr_auto] gap-2">
                    <x-form.select
                        wire:key="product-select"
                        type="backend"
                        placeholder="Buscar producto..."
                        search-placeholder="Escribe nombre o unidad..."
                        icon-left="archive-box"
                        :clearable="false"
                        :clear-after-select="true"
                        :options="$products"
                        search-action="searchProduct"
                        select-action="selectProduct"
                    />
                    <flux:modal.trigger name="product-create">
                        <x-form.button
                            variant="success"
                            size="icon"
                            type="button"
                            leftIcon="plus"
                        />
                    </flux:modal.trigger>
                </div>
            </div>
            <div class="flex-1">
                <x-ui.table
                    :columns="['Descripción', 'Unidad', 'Cantidad', 'Precio unit.', 'Total', '']"
                    striped
                    dense
                    scroll-class="h-[64vh]"
                >
                    @forelse ($items as $index => $item)
                        <tr wire:key="item-{{ $index }}">
                            <x-ui.table.cell dense>
                                <div class="truncate font-medium text-zinc-800">
                                    {{ $item['description'] }}
                                </div>
                                <div class="mt-0.5 truncate text-xs text-zinc-500 font-mono">
                                    {{ $item['code'] ?? '-' }}
                                </div>
                            </x-ui.table.cell>
                            <x-ui.table.cell dense class="font-mono text-xs">
                                {{ $item['unit'] }}
                            </x-ui.table.cell>
                            <x-ui.table.cell dense class="w-18">
                                <x-form.input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live.blur="items.{{ $index }}.quantity"
                                    wire:blur="recalculateItem({{ $index }})"
                                    placeholder="0.00"
                                    :error="$errors->first('items.{{ $index }}.quantity')"
                                />
                            </x-ui.table.cell>
                            <x-ui.table.cell dense>
                                <x-form.input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live.blur="items.{{ $index }}.unitPrice"
                                    wire:blur="recalculateItem({{ $index }})"
                                    placeholder="0.00"
                                    :error="$errors->first('items.{{ $index }}.unitPrice')"
                                />
                            </x-ui.table.cell>
                            <x-ui.table.cell dense>
                                <x-form.input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live.blur="items.{{ $index }}.total"
                                    wire:blur="recalculateItemFromTotal({{ $index }})"
                                    placeholder="0.00"
                                    :error="$errors->first('items.{{ $index }}.total')"
                                />
                            </x-ui.table.cell>
                            <x-ui.table.cell dense class="text-right">
                                <x-form.button
                                    variant="danger"
                                    size="sm"
                                    type="button"
                                    leftIcon="trash"
                                    wire:click="deletedItem({{ $index }})"
                                >
                                    <span icon="trash"></span>
                                </x-form.button>
                            </x-ui.table.cell>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">
                                No ha agregado ningún producto
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
                <div class="px-4 py-2 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">
                            Monto base
                        </span>
                        <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                            S/ {{ number_format($sale->saleValue ?? 0, 2) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">
                            Total igv
                        </span>
                        <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                            S/ {{ number_format($sale->totalTaxes ?? 0, 2) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">
                            Total venta
                        </span>
                        <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                            S/ {{ number_format($sale->total ?? 0, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <form wire:submit.prevent="save" class="contents" id="sale-form">
            <aside class="flex flex-col overflow-hidden rounded-sm shadow-inner border border-zinc-200/80 bg-white">
                <div class="bg-gray-100/70 px-5 py-4 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100/80 text-emerald-700 ring-1 ring-sky-200/50">
                            <flux:icon.document-text class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold tracking-tight text-zinc-800">
                                Datos de factura
                            </h2>
                            <p class="text-xs text-zinc-500">
                                Cliente y observaciones
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex-1 space-y-4 overflow-auto p-4">
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
                    <x-form.input
                        label="Información adicional"
                        wire:model="sale.additionalInfo"
                        placeholder="Ingresa información"
                        icon-left="document-text"
                        :error="$errors->first('sale.additionalInfo')"
                    />
                </div>
                <div class="border-t border-zinc-200 px-4 py-3">
                    <div class="flex gap-2">
                        <x-form.button
                            variant="ghost"
                            type="button"
                            class="flex-1"
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
                            <span
                                wire:loading.remove
                                wire:target="save"
                                class="inline-flex items-center justify-center"
                            >
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
    >
        <livewire:client.create />
    </flux:modal>
    <flux:modal
        name="product-create"
        class="max-w-lg bg-gray-100"
        scroll="body"
        :dismissible="false"
    >
        <livewire:product.create />
    </flux:modal>

    <x-sale.pdf-preview-modal
        :open="$pdfPreviewOpen"
        :url="$pdfPreviewUrl"
        new-label="Ingresar nueva factura"
        new-action="startNewInvoice"
        list-action="goToVouchers"
    />
</div>
@script
<script>
    const form = $wire.$el.querySelector('#sale-form')
    if (form && ! form.dataset.companySelectorBound) {
        form.dataset.companySelectorBound = '1'
        form.addEventListener('submit', () => {
            const companyId = localStorage.getItem('company-selector')
            if (companyId) {
                $wire.$set('sale.companyId', companyId, false)
            }
        }, { capture: true })
    }
</script>
@endscript
