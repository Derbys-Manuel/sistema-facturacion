@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'prefix' => null,
    'suffix' => null,
    'iconLeft' => null,
    'iconRight' => null,
    'textLeft' => null,
    'disabled' => false,
    'size' => 'md',
    'wrapperClass' => null,

    'actionLeftIcon' => null,
    'actionLeftText' => null,
    'actionLeftClick' => null,
    'actionLeftTarget' => null,

    'actionRightIcon' => null,
    'actionRightText' => null,
    'actionRightClick' => null,
    'actionRightTarget' => null,
])

@php
    $type = $attributes->get('type', 'text');
@endphp

<div @class(['w-full', $wrapperClass])>
    @if ($label)
        <flux:label class="mb-3 text-gray-700 text-sm">
            {{ $label }}
        </flux:label>
    @endif

    <div
        class="group relative flex {{ $size === 'sm' ? 'h-9' : 'h-10' }} w-full items-center overflow-hidden rounded-sm border bg-white shadow-sm transition
        {{ $error
            ? 'border-red-400 ring-2 ring-red-100'
            : 'border-zinc-200 hover:border-zinc-300 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100' }}
        {{ $disabled ? 'bg-zinc-100 opacity-80' : '' }}"
    >
        @if ($prefix)
            <span class="flex h-full items-center border-r border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-500">
                {{ $prefix }}
            </span>
        @endif

        @if ($actionLeftIcon || $actionLeftText)
            <button
                type="button"
                @if($actionLeftClick) wire:click="{{ $actionLeftClick }}" @endif
                @if($actionLeftTarget) wire:loading.attr="disabled" wire:target="{{ $actionLeftTarget }}" @endif
                class="flex h-full shrink-0 items-center gap-1 border-r border-zinc-200 bg-zinc-50 px-3 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                @if($actionLeftTarget)
                    <span wire:loading.flex wire:target="{{ $actionLeftTarget }}" class="items-center">
                        <flux:icon.loading class="size-4 animate-spin" />
                    </span>
                @endif

                <span
                    @if($actionLeftTarget) wire:loading.remove wire:target="{{ $actionLeftTarget }}" @endif
                    class="inline-flex items-center gap-1"
                >
                    @if($actionLeftIcon)
                        <flux:icon :name="$actionLeftIcon" class="size-4" />
                    @endif

                    @if($actionLeftText)
                        <span>{{ $actionLeftText }}</span>
                    @endif
                </span>
            </button>
        @endif

        @if ($textLeft)
            <span class="flex h-full shrink-0 items-center pl-1 pr-1 text-[8px] leading-tight text-zinc-400 whitespace-nowrap">
                {!! $textLeft !!}
            </span>
        @elseif($iconLeft)
            <span class="flex h-full items-center pl-3 text-zinc-400">
                <flux:icon :name="$iconLeft" class="size-4" />
            </span>
        @endif

        <input
            {{ $attributes->merge([
                'type' => $type,
                'class' =>
                    'h-full w-full min-w-0 border-0 bg-transparent px-3 text-sm text-zinc-900 placeholder:text-zinc-400 outline-none ring-0 focus:ring-0 focus:outline-none disabled:cursor-not-allowed ' .
                    ($textLeft ? 'pl-1 ' : '') .
                    ($type === 'number'
                        ? 'appearance-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none'
                        : ''),
            ]) }}
            @disabled($disabled)
        />

        @if ($iconRight)
            <span class="flex h-full items-center pr-3 text-zinc-400">
                <flux:icon :name="$iconRight" class="size-4" />
            </span>
        @endif

        @if ($actionRightIcon || $actionRightText)
            <button
                type="button"
                @if($actionRightClick) wire:click="{{ $actionRightClick }}" @endif
                @if($actionRightTarget) wire:loading.attr="disabled" wire:target="{{ $actionRightTarget }}" @endif
                class="flex h-full shrink-0 items-center gap-1 border-l border-zinc-200 bg-zinc-50 px-3 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                @if($actionRightTarget)
                    <span wire:loading.flex wire:target="{{ $actionRightTarget }}" class="items-center">
                        <flux:icon.loading class="size-4 animate-spin" />
                    </span>
                @endif

                <span
                    @if($actionRightTarget) wire:loading.remove wire:target="{{ $actionRightTarget }}" @endif
                    class="inline-flex items-center gap-1"
                >
                    @if($actionRightIcon)
                        <flux:icon :name="$actionRightIcon" class="size-4" />
                    @endif

                    @if($actionRightText)
                        <span>{{ $actionRightText }}</span>
                    @endif
                </span>
            </button>
        @endif

        @if ($suffix)
            <span class="flex h-full items-center border-l border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-500">
                {{ $suffix }}
            </span>
        @endif
    </div>

    @if ($hint && !$error)
        <flux:description class="mt-1.5">
            {{ $hint }}
        </flux:description>
    @endif

    @if ($error)
        <flux:error class="mt-1.5">
            {{ $error }}
        </flux:error>
    @endif
</div>