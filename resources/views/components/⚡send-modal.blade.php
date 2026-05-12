<?php

use App\Enums\DocumentStatus;
use App\Models\SaleDocument;
use App\Services\SaleService;
use App\Services\SunatService;
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

    public function sendSunat(SunatService $sunatService, SaleService $saleService): void
    {
        if (blank($this->saleId)) {
            Flux::toast(
                heading: 'Alerta',
                text: 'No se encontró el comprobante para enviar',
                variant: 'warning',
                duration: 2500
            );
            return;
        }
        try {
            $sale = SaleDocument::query()
                ->with(['items', 'client', 'company', 'discounts', 'items.discounts'])
                ->findOrFail($this->saleId);
            $data = $sale->toArray();
            $data['discounts'] = collect($data['discounts'] ?? [])
                ->filter(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)
                ->values()
                ->all();
            $data['items'] = $saleService->hydrateItemsForSunatFromDatabase($data['items'] ?? []);
            $data['items'] = collect($data['items'])
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return $item;
                    }

                    $item['discounts'] = collect($item['discounts'] ?? [])
                        ->filter(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)
                        ->values()
                        ->all();

                    return $item;
                })
                ->values()
                ->all();
            $response = $sunatService->send($data, $sale);
            $sunatSuccess = $response['sunatResponse']['success'] ?? false;
            $sale->update([
                'xml' => $response['xml'] ?? null,
                'hash' => $response['hash'] ?? null,
                'cdr' => $response['sunatResponse'] ?? null,
                'status' => $sunatSuccess
                    ? DocumentStatus::APPROVED->value
                    : DocumentStatus::REJECTED->value,
            ]);
            Flux::toast(
                heading: $sunatSuccess ? 'SUNAT' : 'Comprobante rechazado',
                text: $sunatSuccess
                    ? 'Comprobante aceptado por SUNAT'
                    : ($response['sunatResponse']['error']['message'] ?? 'SUNAT rechazó el comprobante'),
                variant: $sunatSuccess ? 'success' : 'warning',
                duration: 4000
            );
            $this->dispatch('closed-modal-send');
            $this->close();
        } catch (\Throwable $th) {
            report($th);
            Flux::toast(
                heading: 'Error',
                text: $th->getMessage() ?: 'No se pudo enviar el comprobante',
                variant: 'warning',
                duration: 4000
            );
        }
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
                        ¿Desea enviar comprobante a la SUNAT?                    
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
