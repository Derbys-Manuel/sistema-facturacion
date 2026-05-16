<?php

use App\Enums\Sunat\DocSunatType;
use App\Enums\DocumentStatus;
use App\Livewire\Forms\SaleForm;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\SaleDocument;

new class extends Component
{
    use WithPagination;
    public SaleForm $sale;
    public ?bool $deletedBool = null;
    public ?string $from = null;
    public ?string $to = null;
    public ?string $q = null;
    public ?string $docSunatType = null;
    public ?string $companyId = null;
    public bool $companyReady = false;

    public bool $pdfPreviewOpen = false;
    public ?string $pdfPreviewUrl = null;
    public ?string $sendSaleId = null;

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

        $this->resetPage();
    }

    public function mount(): void
    {
        $now = Carbon::now('America/Lima');
        $this->from = $now->copy()->startOfMonth()->toDateString();
        $this->to = $now->toDateString();
        $this->deletedBool = false;
    }

    public function updatedFrom(): void { $this->resetPage(); }
    public function updatedTo(): void { $this->resetPage(); }
    public function updatedQ(): void { $this->resetPage(); }
    public function updatedDocSunatType(): void { $this->resetPage(); }

    public function getSummaryProperty(): array
    {
        if (! $this->companyReady || blank($this->companyId)) {
            return [
                'boletas' => 0.0,
                'facturas' => 0.0,
                'total' => 0.0,
                'totalIgv' => 0.0
            ];
        }

        return $this->sale->summary(
            deletedBool: $this->deletedBool,
            from: $this->from,
            to: $this->to,
            q: $this->q,
            docSunatType: $this->docSunatType,
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
            deletedBool: $this->deletedBool,
            from: $this->from,
            to: $this->to,
            q: $this->q,
            docSunatType: $this->docSunatType,
            companyId: $this->companyId,
        );
    }

    public function getDocSunatTypeOptionsProperty(): array
    {
        return [
            ['value' => null, 'label' => 'Todos'],
            ['value' => DocSunatType::BOLETA->value, 'label' => 'Boleta'],
            ['value' => DocSunatType::FACTURA->value, 'label' => 'Factura'],
            ['value' => DocSunatType::NOTA_CREDITO->value, 'label' => 'Nota de crédito'],
        ];
    }

    public function docSunatTypeLabel(?string $value): string
    {
        return match ($value) {
            DocSunatType::BOLETA->value => 'Boleta',
            DocSunatType::FACTURA->value => 'Factura',
            DocSunatType::NOTA_CREDITO->value => 'Nota de crédito',
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

    public function previewPdf(?string $saleId = null): void
    {
        if (blank($saleId)) {
            $this->openPdfPreview(null);

            return;
        }
        $this->openPdfPreview(route('sale.pdf', $saleId));
    }

    public function confirmSend(?string $saleId = null): void
    {
        if (blank($saleId)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'No se encontró el comprobante para enviar',
                variant: 'warning',
                duration: 2500
            );

            return;
        }
        $this->sendSaleId = $saleId;
        Flux::modal('confirm')->show();
    }

    public function duplicateSale(string $saleId, ?string $docSunatType = null): void
    {
        $route = match ($docSunatType) {
            DocSunatType::FACTURA->value => 'create-factura',
            DocSunatType::BOLETA->value => 'create-boleta',
            default => 'create-boleta',
        };

        $this->redirect(route($route, ['duplicate' => $saleId]), navigate: true);
    }

    public function editSale(string $saleId, ?string $docSunatType = null): void
    {
        $route = match ($docSunatType) {
            DocSunatType::FACTURA->value => 'create-factura',
            DocSunatType::BOLETA->value => 'create-boleta',
            default => 'create-boleta',
        };

        $this->redirect(route($route, ['edit' => $saleId]), navigate: true);
    }

    public function createCreditNote(?string $saleId = null): void
    {
        if (blank($saleId)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'No se encontró el comprobante',
                variant: 'warning',
                duration: 2500
            );

            return;
        }

        $sale = SaleDocument::query()
            ->select(['id', 'status', 'doc_sunat_type'])
            ->findOrFail($saleId);

        $docSunatType = $sale->doc_sunat_type?->value ?? (string) $sale->doc_sunat_type;

        if ($sale->status !== DocumentStatus::APPROVED) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Solo puede generar nota de crédito desde comprobantes aprobados',
                variant: 'warning',
                duration: 2500
            );

            return;
        }

        if (! in_array($docSunatType, [DocSunatType::BOLETA->value, DocSunatType::FACTURA->value], true)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'Tipo de comprobante no válido para nota de crédito',
                variant: 'warning',
                duration: 2500
            );

            return;
        }

        $this->redirect(route('create-nota-credito', ['affected' => $saleId]), navigate: true);
    }

    public function delete(string $id){
        SaleDocument::where('id', $id)->update([
            'sunat_state'=> false
        ]);
        $this->mount();
    }
    public function restore(string $id){
        SaleDocument::where('id', $id)->update([
            'sunat_state'=> true
        ]);
        $this->mount();
    }
    public function startNewInvoice(): void
    {
        $this->closePdfPreview();
        $this->redirectRoute('create-factura', navigate: true);
    }

    public function goToVouchers(): void
    {
        $this->closePdfPreview();
        $this->redirectRoute('vouchers', navigate: true);
    }
    #[On('closed-modal-send')]
    public function closeModalSend(){
        $this->mount();    
    }
};
?>
<div class="relative">
    <div
        wire:loading.flex
        wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
        class="fixed inset-0 z-[99999] hidden items-center justify-center bg-white/60 backdrop-blur-[1px]"
    >
        <div class="flex items-center gap-2 rounded-md bg-white px-4 py-3 shadow">
            <flux:icon.loading class="size-4 animate-spin text-emerald-600" />
            <span class="text-sm font-medium text-zinc-600">
                Cargando...
            </span>
        </div>
    </div>
    <div>
        @php($documents = $this->documents)
        @php($summary = $this->summary)
        <div class="mb-4 mx-auto w-full max-w-2xl grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-card-total
                wire:key="summary-sale-value-{{ number_format((float) ($summary['saleValue'] ?? 0), 2, '.', '') }}"
                :value="(float) ($summary['saleValue'] ?? 0)"
                subtitle="VALOR"
                prefix="S/ "
                :decimals="2"
            />
            <x-card-total
                wire:key="summary-total-igv-{{ number_format((float) ($summary['totalIgv'] ?? 0), 2, '.', '') }}"
                :value="(float) ($summary['totalIgv'] ?? 0)"
                subtitle="IGV"
                prefix="S/ "
                :decimals="2"
            />
            <x-card-total
                wire:key="summary-total-{{ number_format((float) ($summary['total'] ?? 0), 2, '.', '') }}"
                :value="(float) ($summary['total'] ?? 0)"
                subtitle="TOTAL"
                prefix="S/ "
                :decimals="2"
            />
        </div>
        <div class="grid grid-cols-[0.6fr_auto] items-start gap-3 mb-2">
            <x-sale.filters
                :doc-sunat-type-options="$this->docSunatTypeOptions"
                reset-action="resetFilters"
            />
            <div class="relative mt-2">
                <div
                    wire:loading.flex
                    wire:target="deletedBool"
                    class="absolute inset-0 z-10 hidden cursor-not-allowed items-center rounded-sm bg-white/60"
                ></div>
                <flux:field variant="inline">
                    <flux:checkbox
                        wire:model.live="deletedBool"
                        wire:loading.attr="disabled"
                        wire:target="deletedBool"
                        style="--color-accent: #059669;"
                    />
                    <flux:label class="text-xs">
                        Listar documentos eliminados
                    </flux:label>
                    <flux:error name="deletedBool" />
                </flux:field>
            </div>
        </div>
        <x-ui.table :columns="['Fecha', 'Documento', 'Cliente', 'Tipo', 'Total', 'Estado', 'Acciones']" striped>
            @forelse ($documents['data'] as $row)
                <tr class="transition-colors hover:bg-zinc-50 font-mono text-xs" wire:key="sale-document-{{ $row['id'] }}">
                    <x-ui.table.cell>
                        {{ $row['dateIssue'] ? Carbon::parse($row['dateIssue'])->format('d-m-Y H:i') : '-' }}
                    </x-ui.table.cell>
                    <x-ui.table.cell>
                        {{ ($row['serie'] ?? '-') . '-' . ($row['correlative'] ?? '-') }}
                        {{ $row['affectedSaleDocumentId'] ? 
                        ( '('.$row['affectedSerie'] .'-'. $row['affectedCorrelative'].')') : ''  }}
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
                                    @php($notes = data_get($cdr, 'cdrResponse.notes'))
                                    @if (is_array($notes) && count($notes))
                                        <p class="text-white font-semibold">Notas:</p>
                                        <ul class="list-disc pl-4 text-white">
                                            @foreach ($notes as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-white">Notas: -</p>
                                    @endif
                                    <p class="text-white">
                                        {{ data_get($cdr, 'cdrResponse.description', '-') }}
                                    </p>
                                @elseif (is_array($cdr) && data_get($cdr, 'success') === false)
                                    <p class="font-semibold text-white">SUNAT: Rechazado (Editar unicamente cuando el codigo del error esta en este rango 0100-1999)</p>
                                    <p class="text-white">
                                        Código: {{ data_get($cdr, 'error.code', '-') }}
                                    </p>
                                    @php($notes = data_get($cdr, 'cdrResponse.notes'))
                                    @if (is_array($notes) && count($notes))
                                        <p class="text-white font-semibold">Notas:</p>
                                        <ul class="list-disc pl-4 text-white">
                                            @foreach ($notes as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-white">Notas: -</p>
                                    @endif
                                    <p class="text-white">
                                        {{ data_get($cdr, 'error.message', '-') }}
                                    </p>
                                @else
                                    <p class="text-white">Sin respuesta de SUNAT.</p>
                                @endif
                            </flux:tooltip.content>
                        </flux:tooltip>
                    </x-ui.table.cell>
                    <x-ui.table.cell class="flex justify-center" >
                        <flux:dropdown>
                            <flux:button
                                icon:trailing="ellipsis-horizontal"
                                size="sm"
                                wire:loading.attr="disabled"
                                wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
                            />                            
                            <flux:menu>
                                <flux:menu.item
                                    icon="document-duplicate"
                                    wire:click="duplicateSale('{{ $row['id'] }}', '{{ $row['docSunatType'] ?? '' }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"    
                                >
                                    Duplicar
                                </flux:menu.item>
                                    @if (
                                        ($row['status'] ?? null) === DocumentStatus::APPROVED->value
                                        && in_array($row['docSunatType'] ?? null, [DocSunatType::BOLETA->value, DocSunatType::FACTURA->value], true)
                                    )
                                        <flux:menu.item
                                            icon="document-text"
                                            wire:click="createCreditNote('{{ $row['id'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
                                        >
                                            Nota de crédito
                                        </flux:menu.item>
                                    @endif
                                    @if (in_array($row['status'] ?? null, [DocumentStatus::DRAFT->value, DocumentStatus::REJECTED->value], true))
                                    <flux:menu.item
                                        icon="pencil"
                                        wire:click="editSale('{{ $row['id'] }}', '{{ $row['docSunatType'] ?? '' }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
                                    >
                                        Editar
                                    </flux:menu.item>
                                @endif
                                 @if ($row['status'] === DocumentStatus::DRAFT->value)
                                     <flux:menu.item icon="paper-airplane"
                                     wire:click="confirmSend('{{ $row['id'] }}')"
                                     wire:loading.attr="disabled"
                                     wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
                                     >
                                         Enviar a sunat
                                     </flux:menu.item>
                                 @endif
                                 <flux:menu.item icon="document-magnifying-glass"
                                 wire:click="previewPdf('{{ $row['id'] }}')"
                                 wire:loading.attr="disabled"
                                 wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
                                 >
                                     Abrir pdf
                                 </flux:menu.item>
                                @if(
                                    (
                                        $row['sunatState'] === null ||
                                        $row['sunatState'] === true
                                    )
                                    && data_get($cdr, 'success') === false ||
                                    $row['status'] === DocumentStatus::DRAFT->value
                                )
                                     <flux:menu.item icon="trash" 
                                     wire:click="delete('{{ $row['id'] }}')"
                                     wire:loading.attr="disabled"
                                     wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote"
                                     >
                                         Eliminar
                                     </flux:menu.item>
                                 @endif
                                 @if($row['sunatState'] === false)
                                     <flux:menu.item icon="arrow-path" 
                                     wire:click="restore('{{ $row['id'] }}')"
                                     wire:loading.attr="disabled"
                                     wire:target="duplicateSale,editSale,confirmSend,previewPdf,delete,restore,createCreditNote">
                                         Restaurar
                                     </flux:menu.item>
                                 @endif
                            </flux:menu>
                        </flux:dropdown>
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
        <x-sale.pdf-preview-modal
            :open="$pdfPreviewOpen"
            :url="$pdfPreviewUrl"
            :show-footer-actions="false"
        />
    
        <livewire:send-modal :sale-id="$sendSaleId" :key="'send-modal-'.($sendSaleId ?? 'none')" />
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
