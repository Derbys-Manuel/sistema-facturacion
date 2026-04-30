@props([
    'label' => null,
    'type' => 'backend',
    'placeholder' => 'Seleccionar...',
    'searchPlaceholder' => 'Buscar...',
    'options' => [],
    'selectedLabel' => null,
    'iconLeft' => null,
    'hint' => null,
    'error' => null,
    'disabled' => false,
    'clearable' => false,

    'searchAction' => null,
    'selectAction' => null,
    'clearAction' => null,

    'clearAfterSelect' => false,
])

@php
    $wireModel = $attributes->wire('model');
    $shouldTriggerRequestOnChange = $wireModel->hasModifier('live');
@endphp

<div
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative w-full']) }}
    x-data="{
        open: false,
        query: '',
        selectedLabel: @js($selectedLabel),
        loading: false,
        clearAfterSelect: @js($clearAfterSelect),
        hasWireModel: @js($wireModel->value() !== null),

        toggle() {
            if (@js($disabled) || this.loading) return;

            this.open = !this.open;

            if (this.open && @js($type !== 'simple')) {
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },

        close() {
            this.open = false;
            this.query = '';
        },

        search() {
            if (@js($type === 'simple')) return;

            @if($searchAction)
                this.loading = true;

                $wire.$call('{{ $searchAction }}', this.query)
                    .finally(() => {
                        this.loading = false;
                    });
            @endif
        },

        choose(value, label) {
            this.selectedLabel = label;
            this.query = '';
            this.open = false;

            if (this.hasWireModel) {
                @if($wireModel->value())
                    $wire.set('{{ $wireModel->value() }}', value, @js($shouldTriggerRequestOnChange));
                @endif
            }

            @if($selectAction)
                this.loading = true;

                $wire.$call('{{ $selectAction }}', value, label)
                    .finally(() => {
                        this.loading = false;

                        if (this.clearAfterSelect) {
                            this.selectedLabel = null;
                            this.query = '';

                        if (this.hasWireModel) {
                            @if($wireModel->value())
                                $wire.set('{{ $wireModel->value() }}', null, @js($shouldTriggerRequestOnChange));
                            @endif
                        }
                        }
                    });
            @else
                if (this.clearAfterSelect) {
                    this.selectedLabel = null;
                    this.query = '';

                    if (this.hasWireModel) {
                        @if($wireModel->value())
                            $wire.set('{{ $wireModel->value() }}', null, @js($shouldTriggerRequestOnChange));
                        @endif
                    }
                }
            @endif
        },

        clear() {
            this.selectedLabel = null;
            this.query = '';
            this.open = false;

            if (this.hasWireModel) {
                @if($wireModel->value())
                    $wire.set('{{ $wireModel->value() }}', null, @js($shouldTriggerRequestOnChange));
                @endif
            }

            @if($clearAction)
                $wire.$call('{{ $clearAction }}');
            @endif
        }
    }"
>
    @if($label)
        <flux:label class="mb-1.5">
            {{ $label }}
        </flux:label>
    @endif

    <button
        type="button"
        x-on:click="toggle()"
        @disabled($disabled)
        class="flex h-10 w-full items-center gap-2 rounded-sm border bg-white px-3 text-sm shadow-sm transition
            {{ $error ? 'border-red-400 ring-2 ring-red-100' : 'border-zinc-200 hover:border-zinc-300 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100' }}
            {{ $disabled ? 'cursor-not-allowed bg-zinc-100 opacity-70' : '' }}"
    >
        @if($iconLeft)
            <flux:icon :name="$iconLeft" class="size-4 shrink-0 text-zinc-400" />
        @endif

        <span
            class="flex-1 truncate text-left"
            x-bind:class="selectedLabel ? 'text-zinc-900' : 'text-zinc-400'"
            x-text="selectedLabel || @js($placeholder)"
        ></span>

        <flux:icon.loading
            x-show="loading"
            x-cloak
            class="size-4 shrink-0 animate-spin text-zinc-400"
        />

        @if($clearable)
            <span
                x-show="selectedLabel && !loading"
                x-cloak
                x-on:click.stop="clear()"
                class="flex size-5 shrink-0 items-center justify-center rounded-full text-zinc-400 hover:bg-red-50 hover:text-red-500"
            >
                <flux:icon.x-mark class="size-4" />
            </span>
        @endif

        <flux:icon.chevron-down
            x-show="!loading"
            class="size-4 shrink-0 text-zinc-400 transition"
            x-bind:class="open ? 'rotate-180' : ''"
        />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.scale.origin.top.duration.150ms
        x-on:click.outside="close()"
        class="absolute z-[100000] mt-1 w-full overflow-hidden rounded-sm border border-zinc-200 bg-white shadow-xl"
    >
        @if($type !== 'simple')
            <div class="border-b border-zinc-100 p-2">
                <x-form.input
                    size="sm"
                    x-ref="search"
                    x-model="query"
                    x-on:input.debounce.300ms="search()"
                    placeholder="{{ $searchPlaceholder }}"
                />
            </div>
        @endif

        <div class="max-h-64 overflow-y-auto p-1">
            @forelse($options as $option)
                <button
                    type="button"
                    x-on:click="choose(@js($option['value']), @js($option['label']))"
                    class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-50"
                >
                    @if(!empty($option['icon']))
                        <flux:icon :name="$option['icon']" class="size-4 shrink-0 text-zinc-400" />
                    @endif

                    <span class="flex-1 truncate">
                        {{ $option['label'] }}
                    </span>

                    @if(!empty($option['description']))
                        <span class="truncate text-xs text-zinc-400">
                            {{ $option['description'] }}
                        </span>
                    @endif
                </button>
            @empty
                <div class="px-3 py-5 text-center text-sm text-zinc-400">
                    Sin resultados
                </div>
            @endforelse
        </div>
    </div>

    @if($hint && !$error)
        <flux:description class="mt-1">
            {{ $hint }}
        </flux:description>
    @endif

    @if($error)
        <flux:error class="mt-1">
            {{ $error }}
        </flux:error>
    @endif
</div>
