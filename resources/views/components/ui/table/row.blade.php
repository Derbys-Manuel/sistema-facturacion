@props([
    'id' => null,
    'striped' => false,
    'selectable' => false,
])

@php
    $baseClasses = 'transition-colors';
    $stripeClasses = $striped ? 'odd:bg-zinc-50/60' : '';
    $selectableClasses = $selectable ? 'cursor-pointer hover:bg-zinc-50' : '';
@endphp

<tr
    {{ $attributes->class([
        $baseClasses,
        $stripeClasses,
        $selectableClasses
    ]) }}
    @if($selectable)
        x-on:click="select(@js($id))"
        x-bind:class="selected === @js($id) ? 'bg-emerald-50' : ''"
    @endif
>
    {{ $slot }}
</tr>