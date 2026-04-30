@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'fullWidth' => false,
    'disabled' => false,
    'type' => 'button',
    'leftIcon' => null,
    'rightIcon' => null,
])

@php
    $fluxVariant = match ($variant) {
        'primary' => 'primary',
        'secondary' => 'filled',
        'success' => 'primary',
        'warning' => 'primary',
        'danger' => 'danger',
        'outline' => 'outline',
        'ghost' => 'ghost',
        'link' => 'ghost',
        default => 'primary',
    };

    $classes = [
        'rounded-sm!',
        'transition-all',
        'duration-200',
        'active:scale-[0.98]',
        'cursor-pointer',
        'disabled:cursor-not-allowed',
        $fullWidth ? 'w-full' : '',

        $size === 'sm' ? 'h-9! px-3! text-xs!' : '',
        $size === 'md' ? 'h-10! px-4! text-sm!' : '',
        $size === 'lg' ? 'h-11! px-5! text-sm!' : '',
        $size === 'icon' ? 'h-10! w-10! p-0!' : '',

        $variant === 'primary' ? 'bg-primary! text-white! hover:brightness-110!' : '',
        $variant === 'secondary' ? 'bg-zinc-900! text-white! hover:bg-zinc-800!' : '',
        $variant === 'success' ? 'bg-emerald-600! text-white! hover:bg-emerald-600!' : '',
        $variant === 'warning' ? 'bg-amber-500! text-white! hover:bg-amber-600!' : '',
        $variant === 'danger' ? 'bg-red-600! text-white! hover:bg-red-700!' : '',
        $variant === 'link' ? 'bg-transparent! text-primary! underline-offset-4! hover:underline! shadow-none!' : '',
    ];

    $isDisabled = $disabled || $loading;
@endphp

<flux:button
    :type="$type"
    :variant="$fluxVariant"
    :disabled="$isDisabled"
    {{ $attributes->class($classes) }}
>
    @if($loading)
        <flux:icon.loading variant="micro" class="size-4 animate-spin" />
    @elseif($leftIcon)
        <flux:icon :name="$leftIcon" variant="micro" class="size-4" />
    @endif

    {{ $slot }}

    @if(!$loading && $rightIcon)
        <flux:icon :name="$rightIcon" variant="micro" class="size-4" />
    @endif
</flux:button>
