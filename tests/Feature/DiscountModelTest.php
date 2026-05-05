<?php

use App\Models\Discount;
use App\Enums\Sunat\DiscountType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('discount defines expected relationships', function () {
    expect((new Discount)->saleDocument())->toBeInstanceOf(BelongsTo::class);
});

test('discount uses uuid keys', function () {
    $model = new Discount;

    expect($model->getIncrementing())->toBeFalse();
    expect($model->getKeyType())->toBe('string');
});

test('discount defines expected fillable attributes', function () {
    $model = new Discount;

    $expected = [
        'type',
        'base_amount',
        'factor_porcentage',
        'discount_amount',
        'sale_document_id',
        'sale_document_item_id',
    ];

    expect(collect($model->getFillable())->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());
});

test('discount defines expected casts', function () {
    $casts = (new Discount)->getCasts();

    expect($casts['type'])->toBe(DiscountType::class);
    expect($casts['base_amount'])->toBe('decimal:2');
    expect($casts['factor_porcentage'])->toBe('decimal:5');
    expect($casts['discount_amount'])->toBe('decimal:2');
});
