<?php

it('uses compact defaults for shared form controls', function (): void {
    $componentsPath = dirname(__DIR__, 2).'/resources/views/components/form';

    $input = file_get_contents($componentsPath.'/input.blade.php');
    $select = file_get_contents($componentsPath.'/select.blade.php');
    $button = file_get_contents($componentsPath.'/button.blade.php');
    $date = file_get_contents($componentsPath.'/date.blade.php');
    $dateRange = file_get_contents($componentsPath.'/date-range.blade.php');

    expect($input)
        ->toContain("'size' => 'sm'")
        ->toContain("{{ \$size === 'sm' ? 'h-8' : 'h-10' }}")
        ->and($select)
        ->toContain("'size' => 'sm'")
        ->toContain('rounded-sm bg-white text-left transition')
        ->not->toContain('rounded-sm bg-zinc-50 text-left transition')
        ->and($button)
        ->toContain("'size' => 'sm'")
        ->toContain("\$size === 'icon' ? 'h-8! w-8! p-0!' : ''")
        ->and($date)
        ->toContain('class="group relative flex h-8 w-full')
        ->and($dateRange)
        ->toContain('class="group relative flex h-8 w-full');
});
