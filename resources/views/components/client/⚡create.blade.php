<?php

use App\Enums\Sunat\DocIdentityType;
use App\Livewire\Forms\ClientForm;
use Livewire\Component;
use App\Services\IdentityDiurvanService;
use Flux\Flux;
use Livewire\Attributes\On;


new class extends Component
{
    public ClientForm $client;

    public array $docIdentityTypeOptions = [];

    public function mount(): void
    {
        $docIdentityType = $this->client->docIdentityType;

        if ($docIdentityType instanceof DocIdentityType) {
            $docIdentityType = $docIdentityType->value;
        }

        if (blank($docIdentityType)) {
            $docIdentityType = DocIdentityType::DNI->value;
        }

        $this->client->docIdentityType = $docIdentityType;

        $this->docIdentityTypeOptions = [
            [
                'value' => DocIdentityType::DNI->value,
                'label' => 'DNI',
                'icon' => 'identification',
            ],
            [
                'value' => DocIdentityType::RUC->value,
                'label' => 'RUC',
                'icon' => 'building-office',
            ],
            [
                'value' => DocIdentityType::FOREIGN_CARD->value,
                'label' => 'CE',
                'icon' => 'identification',
            ],
        ];
    }
    #[On('client-create-closed')]
    public function closeModal(): void
    {
        $this->client->reset();
        $this->resetValidation();
        $this->client->docIdentityType = DocIdentityType::DNI->value;
        $this->dispatch('modal-close', name: 'client-create');
    }

    public function consultDocument(IdentityDiurvanService $identityService): ?array
    {
        return $this->client->consultDocument($identityService);
    }

    public function save(): void
    {
        try {
            $client = $this->client->store();
            $this->client->reset();
            $this->dispatch('created-client', client: $client);
            $this->dispatch('modal-close', name: 'client-create');
            Flux::toast(
                heading: 'Aviso',
                text: 'Cliente guardado con éxito',
                variant: 'success',
                duration: 1000);                   
        } catch (\Throwable $th) {
            report($th);
            Flux::toast(
                heading: 'Aviso',
                text: 'Error al guardar cliente',
                variant: 'error',
                duration: 1000);
        }
    }
};
?>
<div x-data class="mx-auto w-full max-w-xl overflow-hidden rounded-sm bg-white">
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
        <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                        <flux:icon.user-plus class="size-6" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-900">
                            Crear cliente
                        </h2>
                        <p class="mt-1 text-xs text-emerald-700/80">
                            Registra la información del cliente.
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
                        class="size-4 animate-spin"
                    />
                </button>
            </div>
        </div>
        <form wire:submit.prevent="save" class="space-y-4 px-5 py-5">
            <div class="grid grid-cols-[0.5fr_1.5fr] gap-3">
                <x-form.select
                    label="Tipo doc."
                    type="simple"
                    placeholder="Seleccionar documento..."
                    wire:model="client.docIdentityType"
                    :options="$docIdentityTypeOptions"
                    :selected-label="collect($docIdentityTypeOptions)->firstWhere('value', $client->docIdentityType)['label'] ?? null"
                    icon-left="identification"
                    :error="$errors->first('client.docIdentityType')"
                />
                <div
                    x-show="[
                        '{{ DocIdentityType::DNI->value }}',
                        '{{ DocIdentityType::FOREIGN_CARD->value }}'
                    ].includes(String($wire.client.docIdentityType))"
                    x-cloak
                    x-transition.opacity.scale.origin.top.duration.150ms
                >
                    <x-form.input
                        label="Número documento"
                        wire:model.defer="client.documentNumber"
                        placeholder="Ingresa el número"
                        icon-left="hashtag"
                        :error="$errors->first('client.documentNumber')"
                    />
                </div>
                <div
                    x-show="String($wire.client.docIdentityType) === '{{ DocIdentityType::RUC->value }}'"
                    x-cloak
                    x-transition.opacity.scale.origin.top.duration.150ms
                >
                    <x-form.input
                        label="Número documento"
                        wire:model.defer="client.documentNumber"
                        placeholder="Ingresa el número"
                        icon-left="hashtag"
                        action-right-icon="magnifying-glass"
                        action-right-click="consultDocument"
                        action-right-target="consultDocument"
                        :error="$errors->first('client.documentNumber')"
                    />
                </div>
            </div>
            <div
                x-show="[
                    '{{ DocIdentityType::DNI->value }}',
                    '{{ DocIdentityType::FOREIGN_CARD->value }}'
                ].includes(String($wire.client.docIdentityType))"
                x-cloak
                x-transition.opacity.scale.origin.top.duration.150ms
            >
                <x-form.input
                    label="Nombre completo"
                    wire:model.defer="client.name"
                    placeholder="Ingresa el nombre completo"
                    icon-left="user"
                    :error="$errors->first('client.name')"
                />
            </div>
            <div
                x-show="String($wire.client.docIdentityType) === '{{ DocIdentityType::RUC->value }}'"
                x-cloak
                x-transition.opacity.scale.origin.top.duration.150ms
            >
                <x-form.input
                    label="Razón social"
                    wire:model.defer="client.tradeName"
                    placeholder="Ingresa la razón social"
                    icon-left="building-office"
                    :error="$errors->first('client.tradeName')"
                />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input
                    label="Departamento"
                    wire:model.defer="client.department"
                    placeholder="Ingresa el departamento"
                    icon-left="map-pin"
                    :error="$errors->first('client.department')"
                />
                <x-form.input
                    label="Provincia"
                    wire:model.defer="client.province"
                    placeholder="Ingresa la provincia"
                    icon-left="map-pin"
                    :error="$errors->first('client.province')"
                />
                <x-form.input
                    label="Distrito"
                    wire:model.defer="client.district"
                    placeholder="Ingresa el distrito"
                    icon-left="map-pin"
                    :error="$errors->first('client.district')"
                />
                <x-form.input
                    label="Teléfono"
                    wire:model.defer="client.telephone"
                    placeholder="Ingresa el teléfono"
                    icon-left="phone"
                    :error="$errors->first('client.telephone')"
                />
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
                    type="submit"
                    variant="success"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">
                        Guardar cliente
                    </span>
                    <span wire:loading.flex wire:target="save" class="hidden items-center gap-2">
                        <flux:icon.loading class="size-4 animate-spin" />
                        Guardando...
                    </span>
                </x-form.button>
            </div>
        </form>
    </div>
</div>