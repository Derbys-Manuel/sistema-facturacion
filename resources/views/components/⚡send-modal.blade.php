<?php

use App\Enums\DocumentStatus;
use App\Jobs\SendSaleDocumentToSunat;
use App\Models\SaleDocument;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public ?string $saleId = null;

    public function mount(?string $saleId = null): void
    {
        $this->saleId = filled($saleId) ? $saleId : null;
    }

    public function close(): void
    {
        Flux::modal('confirm')->close();
    }

    public function sendSunat(): void
    {
        if (blank($this->saleId)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'No se encontro el comprobante para enviar',
                variant: 'warning',
                duration: 2500
            );

            return;
        }

        $sale = SaleDocument::query()
            ->select(['id', 'status'])
            ->findOrFail($this->saleId);

        if ($sale->status === DocumentStatus::WAITING) {
            Flux::toast(
                heading: 'SUNAT',
                text: 'El comprobante ya esta esperando respuesta de SUNAT.',
                variant: 'warning',
                duration: 2500
            );
            $this->close();

            return;
        }

        if (! in_array($sale->status, [DocumentStatus::DRAFT, DocumentStatus::REJECTED], true)) {
            Flux::toast(
                heading: 'SUNAT',
                text: 'Este comprobante no esta disponible para envio.',
                variant: 'warning',
                duration: 2500
            );
            $this->close();

            return;
        }

        $sale->update([
            'status' => DocumentStatus::WAITING->value,
        ]);

        SendSaleDocumentToSunat::dispatch($this->saleId);

        Flux::toast(
            heading: 'SUNAT',
            text: 'El comprobante fue agregado a la cola de envio y queda esperando respuesta.',
            variant: 'success',
            duration: 3000
        );
        $this->dispatch('sale-document-queued', saleId: (string) $sale->id);
        $this->dispatch('closed-modal-send', saleId: (string) $sale->id);
        $this->close();
    }
};
?>

<div>
    <flux:modal name="confirm" class="max-w-md" :dismissible="false">
        <div class="p-2">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <flux:icon.paper-airplane class="size-6" />
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-zinc-800 mt-2">
                        Desea enviar comprobante a la SUNAT?
                    </h2>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-form.button
                    variant="ghost"
                    type="button"
                    wire:click="close"
                >
                    Cancelar
                </x-form.button>
                <x-form.button
                    variant="success"
                    type="button"
                    class="min-w-32"
                    wire:loading.attr="disabled"
                    wire:target="sendSunat"
                    wire:click="sendSunat"
                >
                    <span
                        wire:loading.remove
                        wire:target="sendSunat"
                        class="inline-flex items-center gap-2"
                    >
                        <flux:icon.paper-airplane class="size-4" />
                        Enviar a SUNAT
                    </span>

                    <span
                        wire:loading.flex
                        wire:target="sendSunat"
                        class="hidden items-center justify-center gap-2"
                    >
                        <flux:icon.loading class="size-4 animate-spin" />
                        Enviando...
                    </span>
                </x-form.button>
            </div>
        </div>
    </flux:modal>
</div>
