<?php

use App\Livewire\Forms\ProductForm;
use Flux\Flux;
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
        try {
            $product = $this->product->store();
            $this->product->reset();

            $this->dispatch('modal-close', name: 'product-create');
            $this->dispatch('created-product', product: $product);
            Flux::toast(
                heading: 'Aviso',
                text: 'Producto guardado con éxito',
                variant: 'success',
                duration: 1000
            );
        } catch (\Throwable $th) {
            report($th);

            Flux::toast(
                heading: 'Aviso',
                text: 'Error al guardar producto',
                variant: 'error',
                duration: 1000
            );
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
                <span class="text-sm font-medium text-zinc-600">
                    Limpiando...
                </span>
            </div>
        </div>
        <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                        <flux:icon.cube class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-900">
                            Crear producto
                        </h2>
                        <p class="mt-1 text-xs text-emerald-700/80">
                            Registra la información del producto.
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
                    <span wire:loading.remove wire:target="closeModal">
                        ✕
                    </span>
                    <flux:icon.loading
                        wire:loading
                        wire:target="closeModal"
                        class="size-4 animate-spin"
                    />
                </button>
            </div>
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
                    :error="$errors->first('product.sku')"
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
                    wire:loading.attr="disabled"
                    wire:target="closeModal"
                >
                    <span wire:loading.remove wire:target="closeModal">
                        Cancelar
                    </span>
                    <span
                        wire:loading.flex
                        wire:target="closeModal"
                        class="hidden items-center gap-2"
                    >
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
                        Guardar producto
                    </span>
                    <span
                        wire:loading.flex
                        wire:target="save"
                        class="hidden items-center gap-2"
                    >
                        <flux:icon.loading class="size-4 animate-spin" />
                        Guardando...
                    </span>
                </x-form.button>
            </div>
        </form>
    </div>
</div>