@props([
    'options' => [],
    'cols' => 2,
    'disabled' => false,
    'defaultValue' => null,
])

@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value();

    $gridColsClass = match ((int) $cols) {
        1 => 'grid-cols-1',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2',
    };
@endphp

<div
    wire:cloak
    {{ $attributes->whereDoesntStartWith('wire:model')->class(['grid gap-1', $gridColsClass]) }}
    x-data="{
        defaultValue: @js($defaultValue),
        modelName: @js($modelName),
        init() {
            if (! this.modelName) return;

            if (! $wire || typeof $wire.get !== 'function') {
                return;
            }

            const current = $wire.get(this.modelName);
            if (current !== null && current !== '' && typeof current !== 'undefined') {
                return;
            }

            if (this.defaultValue !== null) {
                $wire.set(this.modelName, String(this.defaultValue), false);
            }
        },
        choose(value) {
            if (@js($disabled)) return;

            if (this.modelName) {
                $wire.set(this.modelName, String(value), false);
            }
        }
    }"
>
    @foreach ($options as $option)
        @php
            $value = $option['value'] ?? null;
            $label = $option['label'] ?? "(string) $value";
            $description = $option['description'] ?? null;
            $icon = $option['icon'] ?? null;
        @endphp

        <button
            type="button"
            @disabled($disabled)
            x-on:click="choose(@js($value))"
            class="group relative flex items-start gap-2 rounded-sm px-3 py-3 pr-10 text-left transition
                {{ $disabled ? 'cursor-not-allowed opacity-60' : 'hover:border-zinc-300 hover:bg-zinc-50 active:scale-[0.99]' }}"
            x-bind:class="String($wire.get(modelName) ?? '') === @js($value)
                ? 'bg-emerald-50 text-zinc-900'
                : 'border-zinc-200 bg-white text-zinc-700'"
        >
            <span
                class="absolute right-3 top-3 inline-flex size-5 items-center justify-center rounded-full border transition"
                x-bind:class="String($wire.get(modelName) ?? '') === @js($value)
                    ? 'border-emerald-600 bg-emerald-600 text-white'
                    : 'border-zinc-300 text-transparent group-hover:border-zinc-400'"
            >
                <flux:icon name="check" class="size-3" />
            </span>

            @if($icon)
                <div
                    class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md border transition"
                    x-bind:class="String($wire.get(modelName) ?? '') === @js($value)
                        ? 'border-emerald-200 bg-white text-emerald-700'
                        : 'border-zinc-200 bg-zinc-50 text-zinc-500 group-hover:border-zinc-300'"
                >
                    <flux:icon :name="$icon" class="size-4" />
                </div>
            @endif

            <div class="min-w-0">
                <p class="text-sm font-semibold leading-5 text-zinc-900">
                    {{ $label }}
                </p>
                @if(filled($description))
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ $description }}
                    </p>
                @endif
            </div>
        </button>
    @endforeach
</div>
