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
    'keepOpenAfterSelect' => false,
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
        keepOpenAfterSelect: @js($keepOpenAfterSelect),

        hasWireModel: @js($wireModel->value() !== null),
        wireModel: @js($wireModel->value()),

        init() {
            if (!this.wireModel || ! $wire || typeof $wire.$watch !== 'function') return;

            const normalize = (v) => (v === null || v === undefined || v === '' ? '' : String(v));

            const syncFromWire = (value) => {
                const normalized = normalize(value);

                if (normalized === '') {
                    this.selectedLabel = null;
                    return;
                }

                const match = @js($options).find(o => normalize(o.value) === normalized);
                this.selectedLabel = match?.label ?? this.selectedLabel;
            };

            syncFromWire($wire.get(this.wireModel));

            $wire.$watch(this.wireModel, (value) => {
                syncFromWire(value);
            });
        },

        toggle() {
            if (@js($disabled) || this.loading) return;

            this.open = !this.open;

            if (this.open) {
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },

        close() {
            this.open = false;
        },

        search() {
            @if($searchAction)
                this.loading = true;

                $wire.$call('{{ $searchAction }}', this.query)
                    .finally(() => this.loading = false);
            @endif
        },

        choose(value, label) {
            this.selectedLabel = label;

            if (this.hasWireModel) {
                $wire.set(this.wireModel, value, @js($shouldTriggerRequestOnChange));
            }

            @if($selectAction)
                this.loading = true;

                $wire.$call('{{ $selectAction }}', value, label)
                    .finally(() => {
                        this.loading = false;
                        this.afterSelect();
                    });
            @else
                this.afterSelect();
            @endif
        },

        afterSelect() {
            if (this.clearAfterSelect) {
                this.selectedLabel = null;

                if (this.hasWireModel) {
                    $wire.set(this.wireModel, null, @js($shouldTriggerRequestOnChange));
                }
            }

            if (this.keepOpenAfterSelect) {
                this.query = '';

                setTimeout(() => {
                    this.open = true;
                    this.$refs.search?.focus();
                }, 80);
            } else {
                this.query = '';
                this.open = false;
            }
        },

        clear() {
            this.selectedLabel = null;
            this.query = '';
            this.open = false;

            if (this.hasWireModel) {
                $wire.set(this.wireModel, null, @js($shouldTriggerRequestOnChange));
            }

            @if($clearAction)
                $wire.$call('{{ $clearAction }}');
            @endif
        }
    }"
>
    {{-- LABEL --}}
    @if($label)
        <flux:label class="mb-3 text-gray-700 text-sm">
            {{ $label }}
        </flux:label>
    @endif

    {{-- BOTÓN --}}
    <button
        type="button"
        x-on:click="toggle()"
        class="flex h-10 w-full items-center gap-2 rounded-sm border bg-white px-3 text-sm shadow-sm transition
        {{ $error ? 'border-red-400 ring-2 ring-red-100' : 'border-zinc-200 hover:border-zinc-300 focus:ring-emerald-100 focus:ring-2' }}"
    >
        @if($iconLeft)
            <flux:icon :name="$iconLeft" class="size-4 text-zinc-400" />
        @endif

        <span
            class="flex-1 truncate text-left"
            x-bind:class="selectedLabel ? 'text-zinc-900' : 'text-zinc-400'"
            x-text="selectedLabel || @js($placeholder)"
        ></span>

        <flux:icon.chevron-down class="size-4 text-zinc-400" />
    </button>

    {{-- DROPDOWN --}}
    <div
        x-show="open"
        x-cloak
        x-transition
        x-on:click.outside="if (!keepOpenAfterSelect) close()"
        class="absolute z-50 mt-1 w-full rounded-sm border bg-white shadow-lg"
    >
        {{-- SEARCH --}}
        <div class="p-2 border-b">
            <x-form.input
                x-ref="search"
                x-model="query"
                x-on:input.debounce.300ms="search()"
                placeholder="{{ $searchPlaceholder }}"
                size="sm"
            />
        </div>

        {{-- LISTA --}}
        <div class="max-h-64 overflow-y-auto">
            @forelse($options as $option)
                <button
                    type="button"
                    x-on:click="choose(@js($option['value']), @js($option['label']))"
                    class="w-full px-3 py-2 text-left hover:bg-zinc-50 text-sm"
                >
                    {{ $option['label'] }}
                </button>
            @empty
                <div class="p-4 text-center text-sm text-zinc-400">
                    Sin resultados
                </div>
            @endforelse
        </div>
    </div>

    {{-- ERROR --}}
    @if($error)
        <flux:error class="mt-1">
            {{ $error }}
        </flux:error>
    @endif
</div>