<?php

use App\Services\SaleService;

it('recalculates the unit price from the gross visual total', function (): void {
    $item = (new SaleService)->calculateItemFromTotal([
        'quantity' => 2,
        'unitPrice' => 10,
        'total' => 30,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ]);

    expect($item['unitPrice'])->toBe('15.00000')
        ->and($item['totalWithoutDiscount'])->toBe('30.00000');
});

it('calculates a discount that reaches the desired item total', function (): void {
    $item = (new SaleService)->calculateItemFromDesiredTotal([
        'quantity' => 2,
        'unitPrice' => 10,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ], 15);

    expect($item['unitPrice'])->toBe('10.00000')
        ->and($item['totalWithoutDiscount'])->toBe('20.00000')
        ->and($item['total'])->toBe('15.00000')
        ->and(data_get($item, 'discounts.0.applyTo'))->toBe('base')
        ->and(data_get($item, 'discounts.0.baseAmount'))->toBe('16.95000')
        ->and(data_get($item, 'discounts.0.discountAmount'))->toBe('4.24')
        ->and($item['itemValue'])->toBe('12.71000')
        ->and($item['igvAmount'])->toBe('2.29000');

    $productionExample = (new SaleService)->calculateItemFromDesiredTotal([
        'quantity' => 2,
        'unitPrice' => 109.90,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ], 164.90);

    expect($productionExample['total'])->toBe('164.90000')
        ->and($productionExample['itemValue'])->toBe('139.75000')
        ->and($productionExample['igvAmount'])->toBe('25.15000')
        ->and($productionExample['unitPriceWithDiscount'])->toBe('82.45000')
        ->and(data_get($productionExample, 'discounts.0.baseAmount'))->toBe('186.27000')
        ->and(data_get($productionExample, 'discounts.0.discountAmount'))->toBe('46.52');
});

it('limits the desired item total to the gross total', function (): void {
    $item = (new SaleService)->calculateItemFromDesiredTotal([
        'quantity' => 1,
        'unitPrice' => 10,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ], 12);

    expect($item['totalWithoutDiscount'])->toBe('10.00000')
        ->and($item['total'])->toBe('10.00000')
        ->and(data_get($item, 'discounts.0.discountAmount'))->toBe('0.00');
});

it('triggers the desired total calculation when the field loses focus', function (): void {
    $modalSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/components/sale/⚡modal-item.blade.php',
    );

    expect($modalSource)
        ->toContain('wire:model.blur="saleItem.desiredTotal"')
        ->toContain('wire:blur="calculateFromDesiredTotal"');
});
