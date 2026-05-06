<?php

use Livewire\Component;
use App\Livewire\Forms\SaleItemForm;
use App\Livewire\Forms\ProductForm;
use App\Enums\Sunat\DiscountType;
use App\Services\SaleService;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component
{
    public SaleItemForm $saleItem;
    public ProductForm $product;


    public array $products = [];
    public bool $calculating = false;
    public ?int $editingIndex = null;

    public function mount(SaleService $saleService): void
    {
        $this->resetItem($saleService);
    }

    #[On('edit-sale-item')]
    public function editSaleItem(int $index, array $item): void
    {
        $this->editingIndex = $index;

        $this->saleItem->id = $item['id'] ?? null;
        $this->saleItem->description = $item['description'] ?? null;
        $this->saleItem->code = $item['code'] ?? null;
        $this->saleItem->quantity = $item['quantity'] ?? 1;
        $this->saleItem->unit = $item['unit'] ?? 'NIU';
        $this->saleItem->unitPrice = $item['unitPrice'] ?? 0;
        $this->saleItem->unitValue = $item['unitValue'] ?? 0;
        $this->saleItem->total = $item['total'] ?? 0;
        $this->saleItem->igv = $item['igv'] ?? 0;
        $this->saleItem->igvPercent = $item['igvPercent'] ?? 18;
        $this->saleItem->igvAffectationType = $item['igvAffectationType'] ?? '10';
        $this->saleItem->discounts = $item['discounts'] ?? [];
    }

    public function searchProduct(string $q = ''): void
    {
        $this->products = $this->product->search($q);
    }

    public function selectProduct(?string $id, ?string $label): void
    {
        if (blank($id)) {
            return;
        }

        $record = $this->product->getRecord($id);

        if (! $record) {
            return;
        }

        $this->saleItem->id = $id;
        $this->saleItem->description = $record->name;
        $this->saleItem->code = $record->sku ?? '00000';
        $this->saleItem->unit = $record->unit ?? 'NIU';
        $this->saleItem->quantity = 1;
        $this->saleItem->unitPrice = $record->price ?? 0;
        $this->saleItem->igvPercent = 18;
        $this->saleItem->igvAffectationType = '10';

        $this->calculateFromPrice();

        $this->products = [
            [
                'value' => $id,
                'label' => $record->name,
            ],
        ];
    }

    public function updatedSaleItemQuantity(): void
    {
        $this->calculateFromPrice();
    }

    public function updatedSaleItemUnitPrice(): void
    {
        $this->calculateFromPrice();
    }

    public function updatedSaleItemTotal(): void
    {
        $this->calculateFromTotal();
    }

    public function calculateFromPrice(): void
    {
        if ($this->calculating) {
            return;
        }
        $this->calculating = true;
        try {
            $saleService = app(SaleService::class);
            $item = $saleService->calculateItem($this->itemPayload());
            $this->syncCalculatedItem($item);
        } finally {
            $this->calculating = false;
        }
    }

    public function calculateFromTotal(): void
    {
        if ($this->calculating) {
            return;
        }
        $this->calculating = true;
        try {
            $saleService = app(SaleService::class);
            $item = $saleService->calculateItemFromTotal($this->itemPayload());
            $this->syncCalculatedItem($item);
        } finally {
            $this->calculating = false;
        }
    }

    public function calculateFromDiscountAmount(): void
    {
        data_set($this->saleItem->discounts, '0.mode', 'amount');
        $saleService = app(SaleService::class);
        $item = $saleService->calculateItem(
            $this->itemPayload(),
            null,
            'amount'
        );
        $this->syncCalculatedItem($item);
    }

    public function calculateFromDiscountPercent(): void
    {
        data_set($this->saleItem->discounts, '0.mode', 'percent');
        $saleService = app(SaleService::class);
        $item = $saleService->calculateItem(
            $this->itemPayload(),
            null,
            'percent'
        );
        $this->syncCalculatedItem($item);
    }

    private function itemPayload(): array
    {
        return [
            'id' => $this->saleItem->id ?? null,
            'igvAffectationType' => $this->saleItem->igvAffectationType ?? '10',
            'code' => $this->saleItem->code ?? '00000',
            'description' => $this->saleItem->description ?? null,
            'unit' => $this->saleItem->unit ?? 'NIU',
            'quantity' => $this->saleItem->quantity ?: 1,
            'unitPrice' => $this->saleItem->unitPrice ?: 0,
            'unitValue' => $this->saleItem->unitValue ?? 0,
            'total' => $this->saleItem->total ?? 0,
            'igvPercent' => $this->saleItem->igvPercent ?? 18,
            'discounts' => $this->saleItem->discounts ?? [],
        ];
    }

    private function syncCalculatedItem(array $item): void
    {
        $this->saleItem->quantity = $item['quantity'] ?? 1;
        $this->saleItem->unitPrice = $item['unitPrice'] ?? 0;
        $this->saleItem->unitValue = $item['unitValue'] ?? 0;
        $this->saleItem->total = $item['total'] ?? 0;
        $this->saleItem->igv = $item['igv'] ?? 0;
        $this->saleItem->discounts = $item['discounts'] ?? [];
    }

    public function saveItem(): void
    {
        $saleService = app(SaleService::class);
        $item = $saleService->calculateItem($this->itemPayload());
        Flux::modal('sale-item')->close();
        if ($this->editingIndex !== null) {
            $this->dispatch(
                'sale-item-updated',
                index: $this->editingIndex,
                item: $item
            );
        } else {
            $this->dispatch('sale-item-created', item: $item);
        }
        $this->resetItem($saleService);
    }
    public function resetItem(SaleService $saleService): void
    {
        $this->editingIndex = null;

        $this->saleItem->reset();

        $this->saleItem->id = null;
        $this->saleItem->description = null;
        $this->saleItem->code = null;
        $this->saleItem->quantity = 1;
        $this->saleItem->unit = 'NIU';
        $this->saleItem->unitPrice = 0;
        $this->saleItem->unitValue = 0;
        $this->saleItem->total = 0;
        $this->saleItem->igv = 0;
        $this->saleItem->igvPercent = 18;
        $this->saleItem->igvAffectationType = '10';

        $this->saleItem->discounts = [
            $saleService->newDiscount(DiscountType::ITEM->value),
        ];

        $this->products = [];
    }
    #[On('reset-sale-item-modal')]
    public function resetModalAfterClose(): void
    {
        $this->resetItem(app(SaleService::class));
    }

    #[On('created-product')]
    public function productCreated(array $product): void
    {
        $this->selectProduct($product['id'] ?? null, null);
    }
};
?>

<div class="space-y-0">
    <div class="rounded-sm bg-white p-4 space-y-4">
         <div class="border-b border-zinc-100 px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
                Datos del producto
            </h2>
        </div>
        <div class="grid grid-cols-[1fr_auto] gap-2">
            <div x-data="{ open: false }" class="relative">
                <div
                    class="transition-opacity duration-150"
                    wire:loading.class="opacity-60"
                    wire:target="searchProduct,selectProduct"
                >
                    <x-form.input
                        label="Descripción"
                        type="text"
                        size="sm"
                        wire:model.live.debounce.300ms="saleItem.description"
                        x-on:input.debounce.300ms="
                            open = true;
                            $wire.searchProduct($event.target.value);
                        "
                        x-on:focus="open = true"
                        placeholder="Buscar o escribir descripción..."
                        :error="$errors->first('saleItem.description')"
                    />
                </div>
    
                @if(count($products))
                    <div
                        x-show="open"
                        x-cloak
                        x-transition.opacity.scale.origin.top.duration.150ms
                        x-on:click.outside="open = false"
                        class="absolute z-[100000] mt-1 w-full max-h-64 overflow-y-auto rounded-sm border border-zinc-200 bg-white shadow-xl"
                    >
                        @foreach($products as $option)
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-emerald-50 hover:text-emerald-700"
                                x-on:click="
                                    open = false;
                                    $wire.selectProduct(@js($option['value']), @js($option['label']));
                                "
                            >
                                {{ $option['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <flux:modal.trigger name="product-create">
                <x-form.button
                    variant="success"
                    size="icon"
                    type="button"
                    leftIcon="plus"
                    class="mt-6"
                />
            </flux:modal.trigger>
        </div>

        {{-- INPUTS PRINCIPALES --}}
        <div class="grid grid-cols-3 gap-3">

            <div
                class="transition-opacity duration-150"
                wire:loading.class="opacity-60"
                wire:target="calculateFromPrice"
            >
                <x-form.input
                    label="Cantidad"
                    type="number"
                    step="0.01"
                    min="0"
                    size="sm"
                    wire:model.live.blur="saleItem.quantity"
                    placeholder="0.00"
                    :error="$errors->first('saleItem.quantity')"
                />
            </div>

            <div
                class="transition-opacity duration-150"
                wire:loading.class="opacity-60"
                wire:target="calculateFromPrice"
            >
                <x-form.input
                    label="Precio unitario"
                    type="number"
                    step="0.01"
                    min="0"
                    size="sm"
                    wire:model.live.blur="saleItem.unitPrice"
                    placeholder="0.00"
                    :error="$errors->first('saleItem.unitPrice')"
                />
            </div>

            <div
                class="transition-opacity duration-150"
                wire:loading.class="opacity-60"
                wire:target="calculateFromTotal"
            >
                <x-form.input
                    label="Total"
                    type="number"
                    step="0.01"
                    min="0"
                    size="sm"
                    wire:model.live.blur="saleItem.total"
                    placeholder="0.00"
                    :error="$errors->first('saleItem.total')"
                />
            </div>

        </div>

        {{-- DESCUENTOS --}}
        <div class="rounded-sm bg-emerald-50/50 p-3 space-y-3">

            <div>
                <p class="text-xs font-semibold text-zinc-800">
                  Descuento sobre la base imponible S/ {{ number_format((float) data_get($saleItem->discounts, '0.baseAmount', 0), 2) }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">

                <div
                    class="transition-opacity duration-150"
                    wire:loading.class="opacity-60"
                    wire:target="calculateFromDiscountAmount"
                >
                    <x-form.input
                        prefix="S/"
                        type="number"
                        step="0.01"
                        min="0"
                        size="sm"
                        wire:model.blur="saleItem.discounts.0.discountAmount"
                        wire:blur="calculateFromDiscountAmount"
                        placeholder="0.00"
                        :error="$errors->first('saleItem.discounts.0.discountAmount')"
                    />
                </div>

                <div
                    class="transition-opacity duration-150"
                    wire:loading.class="opacity-60"
                    wire:target="calculateFromDiscountPercent"
                >
                    <x-form.input
                        prefix="%"
                        type="number"
                        step="0.01"
                        min="0"
                        size="sm"
                        wire:model.blur="saleItem.discounts.0.uiPercent"
                        wire:blur="calculateFromDiscountPercent"
                        placeholder="0.00"
                        :error="$errors->first('saleItem.discounts.0.uiPercent')"
                    />
                </div>

            </div>
        </div>

        {{-- TOTALES --}}
        <div class="grid grid-cols-2 gap-3 rounded-sm bg-zinc-50 p-3">
            <div>
                <p class="text-xs text-zinc-500">IGV</p>
                <p class="text-sm font-semibold tabular-nums">
                    S/ {{ number_format((float) ($saleItem->igv ?? 0), 2) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-zinc-500">Total</p>
                <p class="text-sm font-semibold tabular-nums">
                    S/ {{ number_format((float) ($saleItem->total ?? 0), 2) }}
                </p>
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <x-form.button
                type="button"
                variant="ghost"
                wire:click="resetItem"
                wire:loading.attr="disabled"
            >
                Limpiar
            </x-form.button>
            <x-form.button
                type="button"
                variant="success"
                wire:click="saveItem"
                wire:loading.attr="disabled"
                wire:target="saveItem"
            >
                <span wire:loading.remove wire:target="saveItem">
                    Agregar ítem
                </span>
                <span
                    wire:loading.flex
                    wire:target="saveItem"
                    class="items-center gap-2"
                >
                    <flux:icon.loading class="size-4 animate-spin" />
                    Guardando...
                </span>
            </x-form.button>
        </div>
    </div>
    <flux:modal
        name="product-create"
        class="max-w-lg bg-gray-100"
        scroll="body"
        :dismissible="false"
    >
        <livewire:product.create />
    </flux:modal>
</div>