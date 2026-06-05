<?php

it('uses bulk inserts for sale items and discounts to keep document saving fast', function (): void {
    $items = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/SaleItemForm.php');
    $discounts = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/DiscountForm.php');

    expect($items)
        ->toContain('SaleDocumentItem::query()->insert($itemRows);')
        ->toContain('Discount::query()->insert($discountRows);')
        ->not->toContain('SaleDocumentItem::create(')
        ->and($discounts)
        ->toContain('Discount::query()->insert($rows);')
        ->not->toContain('Discount::create(');
});
