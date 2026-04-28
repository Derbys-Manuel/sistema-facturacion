<?php

use Livewire\Component;
use App\Livewire\Forms\BoletaForm;
use App\Livewire\Forms\BoletaItemForm;
use App\Services\SaleCreateService;
use App\Models\Client;

new class extends Component
{
    public BoletaForm $sale;
    public BoletaItemForm $saleItem;
    public bool $bolClient = 0;
    public array $clientList = [];
    public array $items = [];
    public string $q;
    public ?string $selectedClientLabel = null;


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

    public function loadClient($q)
    {
        $this->clientList = Client::query()
            ->when(
                filled($q),
                fn ($query) =>
                $query->where(
                    fn ($subQuery) =>
                    $subQuery->where('name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('trade_name', 'like', "%{$q}%")
                        ->orWhere('document_number', 'like', "%{$q}%")
                )
            )
            ->limit(20)
            ->get()
            ->map(fn ($client) => [
            'value' => $client->id,
            'label' => trim($client->name . ' ' . $client->last_name) . ' - ' . $client->document_number,
            ])
            ->toArray();
    }
    public function save()
    {
        $this->validate();
    }
    public function selectClient(string $id): void
    {
        $client = Client::find($id);

        if (!$client) {
            return;
        }

        $this->sale->clientId = $client->id;

        $this->selectedClientLabel = trim($client->name . ' ' . $client->last_name)
            . ' - ' . $client->document_number;
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
                        Datos de boleta
                    </h2>
                </div>
            </div>
            <div class="flex-1 overflow-auto p-4 space-y-4">
                <flux:radio.group wire:model="shipping" label="Shipping" variant="cards" class="max-sm:flex-col">
                    <flux:radio value="standard" label="Con cliente" checked />
                    <flux:radio value="fast" label="Sin cliente" />
                </flux:radio.group>
                <div class="grid grid-cols-2 gap-3">
                  <x-form.select
                        label="Cliente"
                        mode="backend"
                        placeholder="Seleccionar cliente..."
                        search-placeholder="Buscar..."
                        search-model="q"
                        select-action="selectClient"
                        :options="$clientList"
                        :selected-label="$selectedClientLabel"
                    />
                    <flux:modal.trigger name="client-create">
                        <flux:button variant="ghost" type="button" class="flex-1" >
                            +
                        </flux:button>
                    </flux:modal.trigger>
                </div>
                <x-input label="Name" wire:model="name" placeholder="Your name" icon="o-user" hint="Your full name" />

                <flux:input
                    label="Nota"
                    wire:model="sale.additionalInfo"
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