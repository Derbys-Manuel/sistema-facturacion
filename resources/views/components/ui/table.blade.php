@props([
    'columns' => [],
    'striped' => false,
    'selectable' => false,
    'selected' => null,
    'dense' => false,
    'scrollClass' => 'max-h-[60vh]',
])

@php
    $containerClasses = 'overflow-hidden rounded-sm shadow-inner border border-zinc-200/80 g-white';
    $tableClasses = 'min-w-full text-sm';
    $headCellClasses = ($dense ? 'px-3 py-2' : 'px-4 py-3').' text-left text-xs font-semibold uppercase tracking-wide text-zinc-500';
@endphp

<div {{ $attributes->class($containerClasses) }}>
    <div class="ui-table-scroll {{ $scrollClass }} overflow-y-auto overflow-x-auto">        
        <table class="{{ $tableClasses }}">
            <thead class="sticky top-0 z-10 bg-zinc-50">
                @isset($head)
                    {{ $head }}
                @else
                    <tr>
                        @foreach ($columns as $column)
                            <th scope="col" class="{{ $headCellClasses }}">
                                {{ $column }}
                            </th>
                        @endforeach
                    </tr>
                @endisset
            </thead>

            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>

@once
<style>
.ui-table-scroll {
    scrollbar-gutter: stable;
    scrollbar-width: thin;
}

.ui-table-scroll::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.ui-table-scroll::-webkit-scrollbar-thumb {
    background: #d4d4d8;
    border-radius: 999px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.ui-table-scroll::-webkit-scrollbar-track {
    background: transparent;
}
</style>
@endonce