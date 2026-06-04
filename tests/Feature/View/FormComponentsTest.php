<?php

use Illuminate\Support\Facades\Blade;

it('does not recursively render the form input component', function (): void {
    $source = file_get_contents(resource_path('views/components/form/input.blade.php'));

    expect($source)->not->toContain('<x-form.input');
});

it('renders the form input and select components', function (): void {
    $input = Blade::render('<x-form.input label="Nombre" name="name" />');
    $select = Blade::render('<x-form.select label="Tipo" :options="$options" />', [
        'options' => [
            ['value' => '01', 'label' => 'Factura'],
        ],
    ]);

    expect($input)
        ->toContain('Nombre')
        ->toContain('name="name"')
        ->and($select)
        ->toContain('Tipo')
        ->toContain('Factura');
});
