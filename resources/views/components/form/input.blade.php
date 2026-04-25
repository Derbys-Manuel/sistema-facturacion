@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'disabled' => false,
])

<div class="w-full">
    @if($label)
        <label class="mb-1 block text-xs font-medium text-zinc-600">
            {{ $label }}
        </label>
    @endif

    <input
        {{ $attributes->merge([
            'class' =>
                'h-10 w-full rounded-lg border bg-white px-3 text-sm text-zinc-900 outline-none transition
                focus:ring-2 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400 ' .
                ($error
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-100'
                    : 'border-zinc-300 focus:border-zinc-500 focus:ring-zinc-200')
        ]) }}
        @disabled($disabled)
    />

    @if($hint && !$error)
        <p class="mt-1 text-xs text-zinc-500">
            {{ $hint }}
        </p>
    @endif

    @if($error)
        <p class="mt-1 text-xs text-red-600">
            {{ $error }}
        </p>
    @endif
</div>