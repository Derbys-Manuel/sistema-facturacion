<?php

it('accepts decimal product prices', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/ProductForm.php');

    expect($source)
        ->toContain('public int|float|null $price = null;')
        ->toContain("#[Validate('nullable|numeric|min:0')]");
});
