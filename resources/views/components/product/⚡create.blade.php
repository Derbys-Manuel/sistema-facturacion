<?php

use App\Livewire\Forms\ProductForm;
use Livewire\Component;

new class extends Component
{
    public ProductForm $product;

    public function mount(): void
    {
        $this->product->reset();
    }

    public function closeModal(): void
    {
        $this->dispatch('modal-close', name: 'product-create');
        $this->product->reset();
    }

    public function save(): void
    {
        $this->product->store();
        $this->product->reset();

        $this->dispatch('modal-close', name: 'product-create');
    }
};
?>

<div x-data class="mx-auto w-full max-w-xl bg-white">

    <div class="border-b border-zinc-100 px-5 py-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
            Crear producto
        </h2>

        <p class="mt-1 text-xs text-zinc-500">
            Registra la información del producto.
        </p>
    </div>

    <form wire:submit.prevent="save" class="space-y-4 px-5 py-5">

        <x-form.input
            label="Nombre"
            wire:model="product.name"
            placeholder="Ingresa el nombre"
            icon-left="document-text"
            :error="$errors->first('product.name')"
        />
        <div class="grid grid-cols-2 gap-3">
            <x-form.input
                label="Sku"
                wire:model="product.sku"
                placeholder="Ingresa el sku"
                icon-left="document-text"
                :error="$errors->first('product.name')"
            />
            <x-form.input
                label="Precio"
                type="number"
                step="0.01"
                min="0"
                wire:model="product.price"
                placeholder="0.00"
                prefix="S/"
                :error="$errors->first('product.price')"
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
                variant="primary"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">
                    Guardar producto
                </span>

                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <flux:icon.loading class="size-4 animate-spin" />
                    Guardando...
                </span>
            </x-form.button>

        </div>

    </form>

</div>