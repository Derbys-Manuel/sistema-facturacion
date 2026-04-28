@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'prefix' => null,
    'suffix' => null,
    'iconLeft' => null,
    'iconRight' => null,
    'disabled' => false,
])

<div class="w-full">

    @if($label)
        <flux:label class="mb-1.5">
            {{ $label }}
        </flux:label>
    @endif

    <div
        class="group relative flex h-10 w-full items-center overflow-hidden rounded-sm border bg-white shadow-sm transition
        {{ $error
            ? 'border-red-400 ring-2 ring-red-100'
            : 'border-zinc-200 hover:border-zinc-300 focus-within:border-zinc-400 focus-within:ring-2 focus-within:ring-zinc-100'
        }}
        {{ $disabled ? 'bg-zinc-100 opacity-80' : '' }}"
    >

        @if($prefix)
            <span class="flex h-full items-center border-r border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-500">
                {{ $prefix }}
            </span>
        @endif

        @if($iconLeft)
            <span class="flex h-full items-center pl-3 text-zinc-400">
                <flux:icon :name="$iconLeft" class="size-4" />
            </span>
        @endif

        <input
            {{ $attributes->merge([
                'type' => 'text',
                'class' => 'h-full w-full border-0 bg-transparent px-3 text-sm text-zinc-900 placeholder:text-zinc-400 outline-none ring-0 focus:ring-0 focus:outline-none disabled:cursor-not-allowed'
            ]) }}
            @disabled($disabled)
        />

        @if($iconRight)
            <span class="flex h-full items-center pr-3 text-zinc-400">
                <flux:icon :name="$iconRight" class="size-4" />
            </span>
        @endif

        @if($suffix)
            <span class="flex h-full items-center border-l border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-500">
                {{ $suffix }}
            </span>
        @endif

    </div>

    @if($hint && !$error)
        <flux:description class="mt-1.5">
            {{ $hint }}
        </flux:description>
    @endif

    @if($error)
        <flux:error class="mt-1.5">
            {{ $error }}
        </flux:error>
    @endif

</div>