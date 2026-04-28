<?php

use Livewire\Component;
use App\Livewire\Forms\BoletaForm;
use App\Livewire\Forms\BoletaItemForm;
use App\Livewire\Forms\ClientForm;
use App\Livewire\Forms\ProductForm;
use App\Services\SaleCreateService;

new class extends Component
{
    public BoletaForm $sale;
    public BoletaItemForm $saleItem;
    public ClientForm $client;
    public ProductForm $product;

    public string $bolClient = 'hide';

    public array $items = [];
    public array $products = [];
    public array $clients = [];

    public ?string $selectedClientLabel = null;

    public function mount(): void
    {
        $this->sale->dateIssue = now()->format('d-m-Y H:i:s');
        $this->sale->dateExpiration = now()->format('d-m-Y H:i:s');
    }

    public function searchClient(string $q = ''): void
    {
        $this->clients = $this->client->search($q);
    }

    public function searchProduct(string $q = ''): void
    {
        $this->products = $this->product->search($q);
    }
    public function deletedItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        app(SaleCreateService::class)->applyTotals($this->sale, $this->items);
    }

    public function selectClient(?string $id = null, ?string $label = null): void
    {
        if (blank($id)) {
            return;
        }
        $this->sale->clientId = $id;
        $this->selectedClientLabel = $label;
    }

    public function selectProduct(?string $id = null, ?string $label = null): void
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
        $this->saleItem->unit = $record->unit;
        $this->saleItem->quantity = 1;
        $this->saleItem->unitValue = $record->price ?? 0;
        $this->saleItem->unitPrice = $record->price ?? 0;

        $saleService = app(SaleCreateService::class);
        $this->items = $saleService->addItem($this->items, $this->saleItem);
        $saleService->applyTotals($this->sale, $this->items);
        $this->saleItem->reset();
        $this->products = [];
    }

    public function updatedBolClient($value): void
    {
        if ($value === 'hide') {
            $this->clearClient();
        }
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

        $this->bolClient = 'hide';

        $this->sale->dateIssue = now()->format('Y-m-d H:i:s');
        $this->sale->dateExpiration = now()->format('Y-m-d H:i:s');
    }

    public function clientCreated(string $id, string $label): void
    {
        $this->sale->clientId = $id;
        $this->selectedClientLabel = $label;
        $this->bolClient = 'show';

        $this->dispatch('modal-close', name: 'client-create');
    }

    public function save(): void
    {
        $this->sale->items = $this->items;
        $this->sale->store($this->saleItem);
        $this->resetForm();
    }
};
?>

<div class="grid gap-4 grid-cols-[4fr_2.5fr] mt-3 h-[82vh]">
    <section class="flex flex-col overflow-hidden rounded-sm border border-zinc-200 bg-white">
        <div class="space-y-3 border-b border-zinc-200 p-4">
            <div class="flex items-center gap-2">
                <flux:icon.archive-box class="h-4 w-4 text-zinc-700" />
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
                    Productos
                </h2>
            </div>
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
                        variant="primary"
                        size="icon"
                        type="button"
                    >
                        +
                    </x-form.button>
                </flux:modal.trigger>
            </div>
        </div>
        <div class="flex-1 overflow-auto p-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Descripción</flux:table.column>
                    <flux:table.column>Unidad</flux:table.column>
                    <flux:table.column>Cantidad</flux:table.column>
                    <flux:table.column>Precio unit.</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                @forelse ($items as $index => $item)
                    <flux:table.row wire:key="item-{{ $index }}">
                        <flux:table.cell>{{ $item['description'] }}</flux:table.cell>
                        <flux:table.cell>{{ $item['unit'] }}</flux:table.cell>
                        <flux:table.cell>{{ $item['quantity'] }}</flux:table.cell>
                        <flux:table.cell>{{ $item['unitPrice'] }}</flux:table.cell>
                        <flux:table.cell>{{ $item['total'] }}</flux:table.cell>

                        <flux:table.cell>
                            <x-form.button
                                variant="danger"
                                size="icon"
                                type="button"
                                wire:click="deletedItem({{ $index }})"
                            >
                                -
                            </x-form.button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
                            No ha agregado ningún producto
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table>
        </div>
        <div class="border-t border-zinc-200 px-4 py-3">
            <div class="flex items-center justify-between">
                <span class="text-xs text-zinc-500">
                    Total costo items
                </span>
                <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                    S/ {{ number_format($sale->total ?? 0, 2) }}
                </div>
            </div>
        </div>
    </section>
    <form wire:submit.prevent="save" class="contents">
        <aside class="flex flex-col overflow-hidden rounded-sm border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-4 py-4">
                <div class="flex items-center gap-2">
                    <flux:icon.document-text class="h-4 w-4 text-zinc-700" />
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
                        Datos de boleta
                    </h2>
                </div>
            </div>
            <div class="flex-1 space-y-4 overflow-auto p-4">
                <flux:radio.group
                    wire:model.live="bolClient"
                    wire:loading.attr="disabled"
                    wire:target="bolClient"
                    wire:loading.class="opacity-60 pointer-events-none cursor-wait"
                    variant="cards"
                    class="max-sm:flex-col"
                >
                    <flux:radio value="show" label="Con cliente" />
                    <flux:radio value="hide" label="Sin cliente" />
                </flux:radio.group>
                <div
                    x-show="$wire.bolClient === 'show'"
                    x-cloak
                    x-transition.opacity.scale.origin.top.duration.150ms
                    class="grid grid-cols-[1fr_auto] gap-3 items-end"
                >
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
                            variant="primary"
                            size="icon"
                            type="button"
                        >
                            +
                        </x-form.button>
                    </flux:modal.trigger>
                </div>
                <x-form.input
                    label="Información adicional"
                    wire:model="sale.additionalInfo"
                    placeholder="Ingresa información"
                    icon-left="hashtag"
                    :error="$errors->first('sale.additionalInfo')"
                />
                <div class="rounded-sm border border-zinc-200 bg-zinc-50 p-4">
                    <p class="text-xs font-semibold text-zinc-800">
                        Resumen
                    </p>
                    <div class="mt-3 space-y-2 text-xs text-zinc-600">
                        <div class="flex justify-between">
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
                    <x-form.button
                        variant="ghost"
                        type="button"
                        class="flex-1"
                    >
                        Limpiar
                    </x-form.button>
                    <x-form.button
                        variant="primary"
                        type="submit"
                        class="flex-1"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">
                            Guardar
                        </span>
                        <span wire:loading wire:target="save">
                            <flux:icon.loading class="size-4 animate-spin" />
                            Guardando...
                        </span>
                    </x-form.button>
                </div>
            </div>
        </aside>
    </form>
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
</div>