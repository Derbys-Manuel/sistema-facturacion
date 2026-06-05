<div>
    @php
        use App\Enums\Sunat\DocSunatType;

        $docType = (string) ($sale->docSunatType ?? '');
        $isBoleta = $docType === DocSunatType::BOLETA->value;
        $isFactura = $docType === DocSunatType::FACTURA->value;
        $isCreditNote = $docType === DocSunatType::NOTA_CREDITO->value;

        $sidebarTitle = match (true) {
            $isFactura => 'Datos de factura',
            $isCreditNote => 'Datos de nota de crédito',
            default => 'Datos de boleta',
        };

        $sidebarSubtitle = match (true) {
            $isCreditNote => 'Documento afectado, cliente y motivo',
            default => 'Cliente y observaciones',
        };

        $totalLabel = $isCreditNote ? 'Total nota' : 'Total venta';

        $newLabel = match (true) {
            $isFactura => 'Ingresar nueva factura',
            $isCreditNote => 'Ingresar nueva nota de crédito',
            default => 'Ingresar nueva boleta',
        };
    @endphp

    <div class="grid gap-4 grid-cols-[4fr_2.0fr] h-[88vh] overflow-auto scrollbar-thin-stable">
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
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Descuento</th>
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
                            <x-ui.table.cell dense class="text-center">
                                <div class="text-xs text-zinc-500">
                                    Base: S/ {{ number_format((float) data_get($item, 'discounts.0.baseAmount', 0), 2) }}
                                </div>
                                <div class="text-sm font-medium tabular-nums text-zinc-800">
                                    S/ {{ number_format((float) data_get($item, 'discounts.0.discountAmount', 0), 2) }}
                                    <span class="text-xs text-zinc-500">
                                        ({{ number_format((float) data_get($item, 'discounts.0.uiPercent', 0), 2) }}%)
                                    </span>
                                </div>
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
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500">
                                No ha agregado ningún producto
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
                @php
                    $discountTotal = collect($items)
                        ->sum(fn ($item) => (float) data_get($item, 'discounts.0.discountAmount', 0));

                    $baseBeforeDiscount = (float) ($sale->saleValue ?? 0) + $discountTotal;
                @endphp
                <div class="px-4 py-0 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">Base original</span>
                        <div class="rounded-sm border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold tabular-nums">
                            S/ {{ number_format($baseBeforeDiscount, 2) }}
                        </div>
                    </div>
                    @if ($discountTotal > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-red-500">Descuento total</span>
                            <div class="rounded-sm border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold tabular-nums text-red-600">
                                - S/ {{ number_format($discountTotal, 2) }}
                            </div>
                        </div>
                    @endif
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
                        <span class="text-sm font-semibold text-zinc-800">{{ $totalLabel }}</span>
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
                                {{ $sidebarTitle }}
                            </h2>
                            <p class="text-xs text-zinc-500">
                                {{ $sidebarSubtitle }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex-1 space-y-2 overflow-auto pt-4 pl-4 pb-4 pr-2 scrollbar-thin-stable">
                    @if ($isCreditNote)
                        <div class="grid grid-cols-[1fr_0.6fr] gap-3">
                            <x-form.select
                                label="Tipo doc. afect."
                                type="simple"
                                icon-left="document-text"
                                wire:model.live="sale.affectedDocSunatType"
                                :options="$affectedDocTypeOptions"
                                :error="$errors->first('sale.affectedDocSunatType')"
                                size="sm"
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
                                size="sm"
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
                            size="sm"
                        />
                        <x-form.input
                            label="Descripción del motivo"
                            wire:model.defer="sale.noteReasonDescription"
                            placeholder="Ej: Anulación por devolución..."
                            icon-left="document-text"
                            :error="$errors->first('sale.noteReasonDescription')"
                            size="sm"
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
                                size="sm"
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
                    @else
                        @if ($isBoleta)
                            <x-ui.choice-cards
                                wire:model="bolClient"
                                :options="[
                                    ['value' => 'show', 'label' => 'Con cliente', 'icon' => 'user'],
                                    ['value' => 'hide', 'label' => 'Sin cliente', 'icon' => 'user-x'],
                                ]"
                            />
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
                                        variant="success"
                                        size="icon"
                                        type="button"
                                        leftIcon="plus"
                                    />
                                </flux:modal.trigger>
                            </div>
                        @else
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
                                    size="sm"
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
                        @endif

                        <x-form.input
                            label="Información adicional"
                            wire:model="sale.additionalInfo"
                            placeholder="Ingresa información"
                            icon-left="document-text"
                            :error="$errors->first('sale.additionalInfo')"
                            size="sm"
                        />
                        <x-form.date
                            label="Fecha de emisión"
                            wire:model="sale.dateIssue"
                            :error="$errors->first('sale.dateIssue')"
                        />
                    @endif
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
        :new-label="$newLabel"
        new-action="startNewDocument"
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
