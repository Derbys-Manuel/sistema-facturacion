<?php

use App\Enums\Sunat\DocIdentityType;
use App\Livewire\Forms\ClientForm;
use Livewire\Component;
use Flux\Flux;

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

    public function closeModal(): void
    {
        $this->dispatch('modal-close', name: 'client-create');
        $this->client->reset();
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

<div x-data class="mx-auto w-full max-w-xl bg-white">

    <div class="border-b border-zinc-100 px-5 py-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
            Crear cliente
        </h2>

        <p class="mt-1 text-xs text-zinc-500">
            Registra la información del cliente.
        </p>
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

            <x-form.input
                label="Número documento"
                wire:model.defer="client.documentNumber"
                placeholder="Ingresa el número"
                icon-left="hashtag"
                :error="$errors->first('client.documentNumber')"
            />

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

        <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4">

            <x-form.button
                type="button"
                variant="ghost"
                wire:click="closeModal"
            >
                Cancelar
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
