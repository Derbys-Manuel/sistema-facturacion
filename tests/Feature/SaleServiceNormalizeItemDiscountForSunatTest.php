<?php

use App\Services\SaleService;

it('derives a line discount when itemValue differs from quantity x unitValue', function () {
    $service = app(SaleService::class);

    $item = [
        'quantity' => '2',
        'unitValue' => '93.14',
        'itemValue' => '139.75',
        'discounts' => [],
    ];

    $normalized = $service->normalizeItemDiscountForSunat($item);

    expect($normalized['discounts'] ?? [])->toHaveCount(1);
    expect($normalized['discounts'][0]['type'])->toBe('00');
    expect($normalized['discounts'][0]['baseAmount'])->toBe('186.28');
    expect($normalized['discounts'][0]['discountAmount'])->toBe('46.53');
    expect($normalized['discounts'][0]['factorPorcentage'])->toBe('0.24979');
});

it('does not add a discount when itemValue matches quantity x unitValue', function () {
    $service = app(SaleService::class);

    $item = [
        'quantity' => '2',
        'unitValue' => '93.14',
        'itemValue' => '186.28',
        'discounts' => [],
    ];

    $normalized = $service->normalizeItemDiscountForSunat($item);

    expect($normalized['discounts'] ?? [])->toHaveCount(0);
});

