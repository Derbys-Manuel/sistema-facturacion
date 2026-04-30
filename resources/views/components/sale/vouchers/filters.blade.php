@props([
    'qModel' => 'q',
    'fromModel' => 'from',
    'toModel' => 'to',
    'docSunatTypeModel' => 'docSunatType',
    'operationTypeModel' => 'operationType',
    'docSunatTypeOptions' => [],
    'operationTypeOptions' => [],
    'resetAction' => 'resetFilters',
])

<div class="grid w-full gap-2 sm:w-auto sm:grid-cols-6 sm:items-end">
    <x-form.input
        wire:model.live.debounce.300ms="{{ $qModel }}"
        placeholder="Buscar por cliente, serie o correlativo..."
        icon-left="magnifying-glass"
        wrapper-class="sm:col-span-3"
    />
    <div
        class="relative"
        x-data="{ open: false }"
        x-on:keydown.escape.window="open = false"
    >
        <button
            type="button"
            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-sm border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 sm:w-auto"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
            aria-haspopup="dialog"
        >
            <flux:icon name="adjustments-horizontal" variant="micro" class="size-4 text-zinc-400" />
            <span>Filtros</span>
            <flux:icon name="chevron-down" variant="micro" class="size-4 text-zinc-400" x-bind:class="open ? 'rotate-180' : ''" />
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.opacity.scale.origin.top.duration.150ms
            x-on:click.outside="open = false"
            class="absolute right-0 z-50 mt-2 w-[22rem] rounded-md border border-zinc-200 bg-white shadow-xl"
        >
            <div class="flex flex-col gap-4 p-4">
                <x-form.date-range
                    from-model="{{ $fromModel }}"
                    to-model="{{ $toModel }}"
                />
                <x-form.select
                    type="simple"
                    placeholder="Comprobante"
                    icon-left="document-text"
                    wire:model.live="{{ $docSunatTypeModel }}"
                    :options="$docSunatTypeOptions"
                />

                <x-form.select
                    type="simple"
                    placeholder="Operación"
                    icon-left="arrows-right-left"
                    wire:model.live="{{ $operationTypeModel }}"
                    :options="$operationTypeOptions"
                />

                <div class="h-px w-full bg-zinc-100"></div>

                <x-form.button
                    variant="ghost"
                    size="md"
                    type="button"
                    class="justify-center -m-2 px-2!"
                    wire:click="{{ $resetAction }}"
                    x-on:click="open = false"
                >
                    Resetear filtros
                </x-form.button>
            </div>
        </div>
    </div>
</div>
