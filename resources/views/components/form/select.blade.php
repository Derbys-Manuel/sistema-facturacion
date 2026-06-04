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
    'size' => 'sm'
])

@php
    $wireModel = $attributes->wire('model');
    $shouldTriggerRequestOnChange = $wireModel->hasModifier('live');

    $sizes = [
        'xs' => [
            'button' => 'h-7 px-2 text-[11px]',
            'icon' => 'size-3',
            'label' => 'mb-1 text-[11px]',
            'option' => 'px-2 py-1 text-[11px]',
            'search' => 'p-1',
            'empty' => 'p-2 text-[11px]',
        ],
        'sm' => [
            'button' => 'h-8 px-2.5 text-xs',
            'icon' => 'size-3.5',
            'label' => 'mb-1.5 text-xs',
            'option' => 'px-2.5 py-1.5 text-xs',
            'search' => 'p-1.5',
            'empty' => 'p-3 text-xs',
        ],
        'md' => [
            'button' => 'h-9 px-3 text-sm',
            'icon' => 'size-4',
            'label' => 'mb-2 text-sm',
            'option' => 'px-3 py-2 text-sm',
            'search' => 'p-2',
            'empty' => 'p-4 text-sm',
        ],
        'lg' => [
            'button' => 'h-11 px-3.5 text-sm',
            'icon' => 'size-4',
            'label' => 'mb-2 text-sm',
            'option' => 'px-3.5 py-2.5 text-sm',
            'search' => 'p-2.5',
            'empty' => 'p-4 text-sm',
        ],
    ];

    $ui = $sizes[$size] ?? $sizes['md'];
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
    @if($label)
        <flux:label class="{{ $ui['label'] }} font-medium text-zinc-700">
            {{ $label }}
        </flux:label>
    @endif

    <button
        type="button"
        x-on:click="toggle()"
        @disabled($disabled)
        class="group flex w-full items-center gap-2 rounded-sm bg-white text-left transition
            {{ $ui['button'] }}
            {{ $disabled ? 'cursor-not-allowed opacity-60' : 'hover:bg-white' }}
            {{ $error ? 'ring-1 ring-red-300 bg-red-50' : 'ring-1 ring-zinc-200 focus:ring-2 focus:ring-emerald-100 focus:bg-white' }}"
    >
        @if($iconLeft)
            <flux:icon :name="$iconLeft" class="{{ $ui['icon'] }} shrink-0 text-zinc-400" />
        @endif

        <span
            class="min-w-0 flex-1 truncate"
            x-bind:class="selectedLabel ? 'text-zinc-800' : 'text-zinc-400'"
            x-text="selectedLabel || @js($placeholder)"
        ></span>

        @if($clearable)
            <span
                x-show="selectedLabel"
                x-cloak
                x-on:click.stop="clear()"
                class="rounded-sm p-0.5 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700"
            >
                <flux:icon.x-mark class="{{ $ui['icon'] }}" />
            </span>
        @endif

        <flux:icon.chevron-down
            class="{{ $ui['icon'] }} shrink-0 text-zinc-400 transition"
            x-bind:class="open ? 'rotate-180' : ''"
        />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        x-on:click.outside="if (!keepOpenAfterSelect) close()"
        class="absolute z-50 mt-1.5 w-full overflow-hidden rounded-sm bg-white shadow-lg ring-1 ring-zinc-200"
    >
        <div class="{{ $ui['search'] }} border-b border-zinc-100 bg-white">
            <x-form.input
                x-ref="search"
                x-model="query"
                x-on:input.debounce.300ms="search()"
                placeholder="{{ $searchPlaceholder }}"
                size="sm"
            />
        </div>

        <div class="max-h-60 overflow-y-auto py-1">
            @forelse($options as $option)
                <button
                    type="button"
                    x-on:click="choose(@js($option['value']), @js($option['label']))"
                    class="w-full truncate text-left text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-950 {{ $ui['option'] }}"
                >
                    {{ $option['label'] }}
                </button>
            @empty
                <div class="{{ $ui['empty'] }} text-center text-zinc-400">
                    Sin resultados
                </div>
            @endforelse
        </div>
    </div>

    @if($hint && !$error)
        <p class="mt-1 text-xs text-zinc-400">
            {{ $hint }}
        </p>
    @endif

    @if($error)
        <flux:error class="mt-1">
            {{ $error }}
        </flux:error>
    @endif
</div>
