<?php

it('uses one livewire request per product search and selects only required columns', function (): void {
    $modalSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/components/sale/⚡modal-item.blade.php',
    );
    $formSource = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/ProductForm.php');

    expect($modalSource)
        ->toContain('wire:model.defer="saleItem.description"')
        ->not->toContain('wire:model.live.debounce.300ms="saleItem.description"');

    expect($formSource)
        ->toContain("->select(['id', 'name', 'unit', 'sku'])");
});
