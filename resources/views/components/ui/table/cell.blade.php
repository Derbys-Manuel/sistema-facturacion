@props([
    'dense' => false,
    'muted' => false,
])

@php
    $cellClasses = ($dense ? 'px-3 py-2' : 'px-4 py-3').' align-middle';
    $textClasses = $muted ? 'text-zinc-500' : 'text-zinc-700';
@endphp

<td {{ $attributes->class([$cellClasses, $textClasses]) }}>
    {{ $slot }}
</td>

