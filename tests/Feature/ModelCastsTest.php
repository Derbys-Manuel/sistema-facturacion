<?php

use App\Enums\DocumentStatus;
use App\Enums\Sunat\AffecType;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;

test('sale document defines expected casts', function () {
    $casts = (new SaleDocument)->getCasts();

    expect($casts['status'])->toBe(DocumentStatus::class);
    expect(array_key_exists('is_active', $casts))->toBeFalse();
});

test('sale document item casts affectation type to enum', function () {
    $casts = (new SaleDocumentItem)->getCasts();

    expect($casts['igv_affectation_type'])->toBe(AffecType::class);
});
