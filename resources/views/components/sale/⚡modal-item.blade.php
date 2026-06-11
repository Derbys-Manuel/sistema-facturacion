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
    public array $productList = [];
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
        $this->saleItem->sumTotal = $item['totalWithoutDiscount']
            ?? ((float) $this->saleItem->quantity * (float) $this->saleItem->unitPrice);
        $this->saleItem->desiredTotal = data_get($item, 'discounts.0.discountAmount', 0) > 0
            ? ($item['total'] ?? 0)
            : null;
        $this->saleItem->igv = $item['igv'] ?? 0;
        $this->saleItem->igvPercent = $item['igvPercent'] ?? 18;
        $this->saleItem->igvAffectationType = $item['igvAffectationType'] ?? '10';
        $this->saleItem->discounts = $item['discounts'] ?? [];
    }

    public function searchProduct(string $q = ''): void
    {
        $this->productList = $this->product->search($q);
        $this->products = array_map(fn ($p) => [
            'value' => (string) $p['id'],
            'label' => $p['name'].' '.$p['unit'].' '.$p['sku'],
            'item' => $p,
        ], $this->productList);
    }

    public function selectProduct(?array $item): void
    {
        $this->saleItem->id = $item['id'] ?? null;
        $this->saleItem->description = $item['name'].' '.$item['unit'].' '.$item['sku'];
        $this->saleItem->code = $item['sku'] ?? '00000';
        $this->saleItem->unit = $item['unit'] ?? 'NIU';
        $this->saleItem->quantity = 1;
        $this->saleItem->unitPrice = $item['price'] ?? 0;
        $this->saleItem->igvPercent = 18;
        $this->saleItem->igvAffectationType = '10';

        $this->calculateFromPrice();
    }

    public function updatedSaleItemQuantity(): void
    {
        $this->calculateFromPrice();
    }

    public function updatedSaleItemUnitPrice(): void
    {
        $this->calculateFromPrice();
    }

    public function updatedSaleItemSumTotal(): void
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
            $this->syncCalculatedItem($item, syncDesiredTotal: false);
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
            $item = $saleService->calculateItemFromTotal(array_merge(
                $this->itemPayload(),
                ['total' => $this->saleItem->sumTotal ?? 0],
            ));
            $this->syncCalculatedItem($item, syncDesiredTotal: false);
        } finally {
            $this->calculating = false;
        }
    }

    public function calculateFromDesiredTotal(): void
    {
        if ($this->calculating) {
            return;
        }

        $this->calculating = true;
        try {
            $saleService = app(SaleService::class);
            $item = $saleService->calculateItemFromDesiredTotal(
                $this->itemPayload(),
                $this->saleItem->desiredTotal,
            );
            $this->syncCalculatedItem($item, syncDesiredTotal: true);
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
        $this->syncCalculatedItem($item, syncDesiredTotal: false);
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
        $this->syncCalculatedItem($item, syncDesiredTotal: false);
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

    private function syncCalculatedItem(array $item, bool $syncDesiredTotal = false): void
    {
        $this->saleItem->quantity = $item['quantity'] ?? 1;
        $this->saleItem->unitPrice = $item['unitPrice'] ?? 0;
        $this->saleItem->unitValue = $item['unitValue'] ?? 0;
        $this->saleItem->total = $item['total'] ?? 0;
        $this->saleItem->sumTotal = $item['totalWithoutDiscount'] ?? 0;
        if ($syncDesiredTotal) {
            $this->saleItem->desiredTotal = $item['total'] ?? null;
        }
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
        $this->saleItem->resetValidation();

        $this->saleItem->id = null;
        $this->saleItem->description = null;
        $this->saleItem->code = null;
        $this->saleItem->quantity = 1;
        $this->saleItem->unit = 'NIU';
        $this->saleItem->unitPrice = 0;
        $this->saleItem->unitValue = 0;
        $this->saleItem->total = 0;
        $this->saleItem->sumTotal = 0;
        $this->saleItem->desiredTotal = null;
        $this->saleItem->igv = 0;
        $this->saleItem->igvPercent = 18;
        $this->saleItem->igvAffectationType = '10';

        $this->saleItem->discounts = [
            $saleService->newDiscount(DiscountType::ITEM->value),
        ];

        $this->products = [];
    }
    public function closeModal(SaleService $saleService): void
    {
        $this->resetItem($saleService);
        $this->dispatch('modal-close', name: 'sale-item');
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
<div x-data="{ showDiscounts: false }" class="mx-auto w-full max-w-xl overflow-hidden rounded-sm bg-white">
    <div class="relative bg-white">
        <div
            wire:loading.flex
            wire:target="closeModal"
            class="absolute inset-0 z-50 hidden items-center justify-center bg-white/75 backdrop-blur-[1px]"
        >
            <div class="flex items-center gap-2 rounded-md bg-white px-4 py-3 shadow">
                <flux:icon.loading class="size-4 animate-spin text-emerald-600" />
                <span class="text-sm font-medium text-zinc-600">Limpiando...</span>
            </div>
        </div>
        <div class="border-b px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                        <flux:icon.shopping-bag class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-900">
                            Producto
                        </h2>
                        <p class="mt-1 text-xs text-emerald-700/80">
                            Agrega o edita los datos del producto.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="closeModal"
                    wire:loading.attr="disabled"
                    wire:target="closeModal"
                    class="rounded-md p-2 text-emerald-700 transition hover:bg-white hover:text-emerald-900"
                >
                    <span wire:loading.remove wire:target="closeModal">✕</span>
                    <flux:icon.loading
                        wire:loading
                        wire:target="closeModal"
                        class="size-6 animate-spin"
                    />
                </button>
            </div>
        </div>
        <div class="space-y-4 px-5 py-5">
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
                            wire:model.defer="saleItem.description"
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
                            class="absolute z-[100000] mt-1 max-h-64 w-full overflow-y-auto rounded-sm border border-zinc-200 bg-white shadow-xl"
                        >
                            @foreach($products as $option)
                                <button
                                    type="button"
                                    class="w-full px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-emerald-50 hover:text-emerald-700"
                                    x-on:click="
                                        open = false;
                                        $wire.selectProduct(@js($option['item']));                                    "
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
            <div class="grid grid-cols-3 gap-3">
                <div
                    class="transition-opacity duration-150"
                    wire:loading.class="opacity-60"
                    wire:target="calculateFromPrice"
                >
                    <x-form.input
                        label="Cantidad"
                        type="number"
                        step="0.00001"
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
                        step="0.00001"
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
                        step="0.00001"
                        min="0"
                        size="sm"
                        wire:model.live.blur="saleItem.sumTotal"
                        placeholder="0.00"
                        :error="$errors->first('saleItem.sumTotal')"
                    />
                </div>
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-zinc-600">
                <input
                    type="checkbox"
                    x-model="showDiscounts"
                    class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                />
                <flux:icon.receipt-percent class="size-4 text-emerald-600" />
                <span>Mostrar descuentos</span>
            </label>
            <div
                x-show="showDiscounts"
                x-cloak
                x-transition.opacity.duration.150ms
                class="space-y-3 rounded-sm bg-red-50/80 p-3"
            >
                <div
                    class="transition-opacity duration-150"
                    wire:loading.class="opacity-60"
                    wire:target="calculateFromDesiredTotal"
                >
                    <x-form.input
                        label="Total deseado"
                        prefix="S/"
                        type="number"
                        step="0.01"
                        min="0"
                        size="sm"
                        wire:model.blur="saleItem.desiredTotal"
                        wire:blur="calculateFromDesiredTotal"
                        placeholder="0.00"
                        :error="$errors->first('saleItem.desiredTotal')"
                    />
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
            <div class="grid grid-cols-2 gap-3 rounded-sm bg-zinc-50 p-3">
                <div>
                    <p class="text-xs text-zinc-500">IGV</p>
                    <p class="text-sm font-semibold tabular-nums text-zinc-800">
                        S/ {{ number_format((float) ($saleItem->igv ?? 0), 2) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500">Total</p>
                    <p class="text-sm font-semibold tabular-nums text-zinc-800">
                        S/ {{ number_format((float) ($saleItem->total ?? 0), 2) }}
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4">
                <x-form.button
                    type="button"
                    variant="ghost"
                    wire:click="closeModal"
                    wire:loading.attr="disabled"
                    wire:target="closeModal"
                >
                    <span wire:loading.remove wire:target="closeModal">
                        Cancelar
                    </span>
                    <span wire:loading.flex wire:target="closeModal" class="hidden items-center gap-2">
                        <flux:icon.loading class="size-4 animate-spin" />
                        Limpiando...
                    </span>
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
                    <span wire:loading.flex wire:target="saveItem" class="items-center gap-2">
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
            :closable="false"
        >
            <livewire:product.create />
        </flux:modal>

    </div>
</div>
