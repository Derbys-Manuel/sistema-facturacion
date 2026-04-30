<?php

use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Livewire\Forms\SaleForm;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component
{
    use WithPagination;
    public SaleForm $sale;
    public ?string $from = null;
    public ?string $to = null;
    public ?string $q = null;
    public ?string $docSunatType = null;
    public ?string $operationType = null;
    public ?string $companyId = null;
    public bool $companyReady = false;

    public function setCompany(?string $companyId = null): void
    {
        $this->companyId = filled($companyId) ? $companyId : null;
        $this->companyReady = true;
        $this->resetPage();
    }

    private function emptyPaginator(): array
    {
        return [
            'current_page' => 1,
            'data' => [],
            'last_page' => 1,
            'per_page' => 15,
            'total' => 0,
        ];
    }

    public function resetFilters(): void
    {
        $this->from = null;
        $this->to = null;
        $this->q = null;
        $this->docSunatType = null;
        $this->operationType = null;

        $this->resetPage();
    }

    public function mount(): void
    {
        $now = Carbon::now('America/Lima');
        $this->from = $now->copy()->startOfMonth()->toDateString();
        $this->to = $now->toDateString();
    }

    public function updatedFrom(): void { $this->resetPage(); }
    public function updatedTo(): void { $this->resetPage(); }
    public function updatedQ(): void { $this->resetPage(); }
    public function updatedDocSunatType(): void { $this->resetPage(); }
    public function updatedOperationType(): void { $this->resetPage(); }

    public function getSummaryProperty(): array
    {
        if (! $this->companyReady || blank($this->companyId)) {
            return [
                'boletas' => 0.0,
                'facturas' => 0.0,
                'total' => 0.0,
            ];
        }

        return $this->sale->summary(
            from: $this->from,
            to: $this->to,
            q: $this->q,
            docSunatType: $this->docSunatType,
            operationType: $this->operationType,
            companyId: $this->companyId,
        );
    }

    public function getDocumentsProperty(): array
    {
        if (! $this->companyReady) {
            return $this->emptyPaginator();
        }

        if (blank($this->companyId)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Debe de seleccionar una empresa',
                variant: 'warning',
                duration: 2000
            );

            return $this->emptyPaginator();
        }

        return $this->sale->list(
            from: $this->from,
            to: $this->to,
            q: $this->q,
            docSunatType: $this->docSunatType,
            operationType: $this->operationType,
            companyId: $this->companyId,
        );
    }

    public function getDocSunatTypeOptionsProperty(): array
    {
        return [
            ['value' => null, 'label' => 'Todos'],
            ['value' => DocSunatType::BOLETA->value, 'label' => 'Boleta'],
            ['value' => DocSunatType::FACTURA->value, 'label' => 'Factura'],
        ];
    }

    public function getOperationTypeOptionsProperty(): array
    {
        return [
            ['value' => null, 'label' => 'Todos'],
            ['value' => OperationType::INTERNAL_SALE->value, 'label' => 'Venta interna'],
        ];
    }

    public function docSunatTypeLabel(?string $value): string
    {
        return match ($value) {
            DocSunatType::BOLETA->value => 'Boleta',
            DocSunatType::FACTURA->value => 'Factura',
            default => $value ?: '-',
        };
    }

    public function statusBadgeColor(?string $status): string
    {
        return match ($status) {
            'aprobada' => 'emerald',
            'rechazada' => 'red',
            'observada' => 'amber',
            default => 'zinc',
        };
    }
};
?>
<div>
    @php($documents = $this->documents)
    @php($summary = $this->summary)
    <div class="mb-4 mx-auto w-full max-w-2xl grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-card-total
            wire:key="summary-boletas-{{ number_format((float) ($summary['boletas'] ?? 0), 2, '.', '') }}"
            :value="(float) ($summary['boletas'] ?? 0)"
            subtitle="Boletas"
            prefix="S/ "
            :decimals="2"
        />
        <x-card-total
            wire:key="summary-facturas-{{ number_format((float) ($summary['facturas'] ?? 0), 2, '.', '') }}"
            :value="(float) ($summary['facturas'] ?? 0)"
            subtitle="Facturas"
            prefix="S/ "
            :decimals="2"
        />
        <x-card-total
            wire:key="summary-total-{{ number_format((float) ($summary['total'] ?? 0), 2, '.', '') }}"
            :value="(float) ($summary['total'] ?? 0)"
            subtitle="Total"
            prefix="S/ "
            :decimals="2"
        />
    </div>

    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <x-sale.filters
            :doc-sunat-type-options="$this->docSunatTypeOptions"
            :operation-type-options="$this->operationTypeOptions"
            reset-action="resetFilters"
        />
    </div>
    <x-ui.table :columns="['Fecha', 'Documento', 'Cliente', 'Tipo', 'Total', 'Estado', 'Acciones']" striped>
        @forelse ($documents['data'] as $row)
            <tr class="transition-colors hover:bg-zinc-50 font-mono text-xs" wire:key="sale-document-{{ $row['id'] }}">
                <x-ui.table.cell>
                    {{ $row['dateIssue'] ? Carbon::parse($row['dateIssue'])->format('d-m-Y H:i') : '-' }}
                </x-ui.table.cell>
                <x-ui.table.cell>
                    {{ ($row['serie'] ?? '-') . '-' . ($row['correlative'] ?? '-') }}
                </x-ui.table.cell>
                <x-ui.table.cell class="max-w-[28ch] truncate">
                    @php($clientName = data_get($row, 'client.tradeName') ?: data_get($row, 'client.name'))
                    @php($clientDoc = data_get($row, 'client.documentNumber'))
                    <div class="truncate font-medium text-zinc-800">
                        {{ $clientName ?: '' }}
                    </div>
                    <div class="mt-0.5 truncate text-xs text-zinc-500 font-mono">
                        {{ $clientDoc ?: '' }}
                    </div>
                </x-ui.table.cell>
                <x-ui.table.cell>
                    {{ $this->docSunatTypeLabel($row['docSunatType'] ?? null) }}
                </x-ui.table.cell>
                <x-ui.table.cell class="tabular-nums font-semibold">
                    S/ {{ number_format((float) ($row['total'] ?? 0), 2) }}
                </x-ui.table.cell>
                <x-ui.table.cell>
                    <flux:tooltip toggleable>
                        <button type="button" class="inline-flex cursor-pointer">
                            <flux:badge :color="$this->statusBadgeColor($row['status'] ?? null)">
                                {{ $row['status'] ?? '-' }}
                            </flux:badge>
                        </button>
                        <flux:tooltip.content class="max-w-[22rem] space-y-2 text-xs">
                            @php($cdr = data_get($row, 'cdr'))
                            @if (is_array($cdr) && data_get($cdr, 'success') === true)
                                <p class="font-semibold text-white">SUNAT: Aceptado</p>
                                <p class="text-white">
                                    Código: {{ data_get($cdr, 'cdrResponse.code', '-') }}
                                </p>
                                <p class="text-white">
                                    {{ data_get($cdr, 'cdrResponse.description', '-') }}
                                </p>
                            @elseif (is_array($cdr) && data_get($cdr, 'success') === false)
                                <p class="font-semibold text-white">SUNAT: Rechazado</p>
                                <p class="text-white">
                                    Código: {{ data_get($cdr, 'error.code', '-') }}
                                </p>
                                <p class="text-white">
                                    {{ data_get($cdr, 'error.message', '-') }}
                                </p>
                            @else
                                <p class="text-white">Sin respuesta de SUNAT.</p>
                            @endif
                        </flux:tooltip.content>
                    </flux:tooltip>
                </x-ui.table.cell>

                <x-ui.table.cell>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-zinc-200 bg-white px-2 py-0 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50"
                        >
                            Ver
                        </button>

                        <button
                            type="button"
                            class="rounded-md border border-zinc-200 bg-white px-2 py-0 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50"
                        >
                            Acción
                        </button>
                    </div>
                </x-ui.table.cell>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500">
                    Sin resultados.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    <div class="flex flex-col gap-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-xs text-zinc-500">
            Página {{ $documents['current_page'] ?? 1 }} de {{ $documents['last_page'] ?? 1 }} ·
            {{ $documents['total'] ?? 0 }} registros
        </div>
        <div class="flex gap-2">
            <x-form.button
                variant="ghost"
                size="sm"
                type="button"
                wire:click="previousPage"
                :disabled="($documents['current_page'] ?? 1) <= 1"
            >
                Anterior
            </x-form.button>
            <x-form.button
                variant="ghost"
                size="sm"
                type="button"
                wire:click="nextPage"
                :disabled="($documents['current_page'] ?? 1) >= ($documents['last_page'] ?? 1)"
            >
                Siguiente
            </x-form.button>
        </div>
    </div>
</div>
@script
<script>
    const root = $wire.$el
    if (root && ! root.dataset.companySelectorBound) {
        root.dataset.companySelectorBound = '1'

        const syncCompany = (id) => {
            const companyId = id ?? localStorage.getItem('company-selector')
            $wire.$call('setCompany', companyId)
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
    }
</script>
@endscript
