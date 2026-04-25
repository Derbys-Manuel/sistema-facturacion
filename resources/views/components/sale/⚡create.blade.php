<?php

use Livewire\Component;
use App\Livewire\Forms\SaleForm;
use App\Livewire\Forms\SaleItemForm;
use App\Services\SaleCreateService;
use App\Enums\Sunat\PaymentForm;

new class extends Component
{
    public SaleForm $sale;
    public SaleItemForm $saleItem;

    public array $items = [];

    public string $skuSearch = '';
    public string $packSearch = '';
    public string $clientSearch = '';

    public array $skuOptions = [
        ['value' => '1', 'label' => 'Producto ejemplo'],
        ['value' => '2', 'label' => 'Producto 2'],
    ];

    public array $packOptions = [
        ['value' => '1', 'label' => 'Pack ejemplo'],
        ['value' => '2', 'label' => 'Pack premium'],
    ];

    public array $clientOptions = [
        ['value' => '1', 'label' => 'Cliente 1'],
        ['value' => '2', 'label' => 'Cliente 2'],
    ];

    public function mount()
    {
        $this->sale->dateIssue = now()->format('d-m-Y H:i:s');
        $this->sale->dateExpiration = now()->format('d-m-Y H:i:s');
    }

    public function addItem(SaleCreateService $service)
    {
        $this->saleItem->validate();

        $this->items = $service->addItem($this->items, $this->saleItem);

        $service->applyTotals($this->sale, $this->items);

        $this->saleItem->reset();
    }

    public function openOption(string $id)
    {
        // Acción opcional para botón dentro de una opción
    }

    public function save()
    {
        $this->validate();
    }
};
?>

<div class="grid gap-4 grid-cols-[4fr_2.5fr] mt-3 h-[82vh]">

    <section class="rounded-2xl bg-gray-100 shadow-sm flex flex-col overflow-hidden">
        <div class="border-b border-zinc-200 p-4">
            <div class="flex items-center gap-2">
                <flux:icon.archive-box class="w-4 h-4 text-zinc-700" />

                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
                    Productos
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-3 mt-4">
                <x-form.searchable-select
                    label="Producto"
                    placeholder="Seleccionar producto..."
                    search-placeholder="Buscar SKU..."
                    search-model="skuSearch"
                    value-model="saleItem.skuId"
                    :options="$skuOptions"
                    option-button-text="Ver"
                    option-button-action="openOption"
                />


            </div>
        </div>

        <div class="flex-1 overflow-auto p-4">
            <div class="rounded-xl border border-dashed border-zinc-200 p-8 text-center text-sm text-zinc-500">
                Aún no agregas items.
            </div>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3">
            <div class="flex items-center justify-between">
                <span class="text-xs text-zinc-500">
                    Total costo items
                </span>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                    S/ {{ number_format($sale->total ?? 0, 2) }}
                </div>
            </div>
        </div>
    </section>

    <form wire:submit.prevent="save" class="contents">
        <aside class="rounded-2xl bg-gray-100 shadow-sm flex flex-col overflow-hidden">

            <div class="border-b border-zinc-200 px-4 py-4">
                <div class="flex items-center gap-2">
                    <flux:icon.document-text class="w-4 h-4 text-zinc-700" />

                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
                        Datos de documento
                    </h2>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-4 space-y-4">

                <div class="grid grid-cols-2 gap-4">
        
                </div>

                <flux:input
                    label="Nota"
                    wire:model.live="sale.additionalInfo"
                />

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                    <p class="text-xs font-semibold text-zinc-800">
                        Resumen
                    </p>

                    <div class="mt-3 space-y-2 text-xs text-zinc-600">
                        <div class="flex justify-between gap-3">
                            <span>Documento</span>
                            <span class="font-semibold text-zinc-900">
                                {{ $sale->docSunatType ?: '-' }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Cliente</span>
                            <span class="font-semibold text-zinc-900">
                                {{ $sale->clientId ?: '-' }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Moneda</span>
                            <span class="font-semibold text-zinc-900">
                                {{ $sale->currency ?: '-' }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Forma pago</span>
                            <span class="font-semibold text-zinc-900">
                                {{ $sale->paymentForm ?: '-' }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Fecha emisión</span>
                            <span class="font-semibold text-zinc-900">
                                {{ $sale->dateIssue }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Total</span>
                            <span class="font-semibold text-zinc-900">
                                S/ {{ number_format($sale->total ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="border-t border-zinc-200 px-4 py-3">
                <div class="flex gap-2">
                    <flux:button variant="ghost" type="button" class="flex-1">
                        Cerrar
                    </flux:button>

                    <flux:button variant="primary" type="submit" class="flex-1">
                        Guardar
                    </flux:button>
                </div>
            </div>

        </aside>
    </form>

    <flux:modal
        name="client-create"
        class="max-w-lg"
        scroll="body"
        :dismissible="false"
        class="bg-gray-100"
    >
        <livewire:client.create />
    </flux:modal>

</div>