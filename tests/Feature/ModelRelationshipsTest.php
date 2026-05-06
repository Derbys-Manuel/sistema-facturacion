<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Discount;
use App\Models\District;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Province;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\Serie;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('models define expected relationships', function () {
    expect((new Client)->saleDocuments())->toBeInstanceOf(HasMany::class);

    expect((new Company)->saleDocuments())->toBeInstanceOf(HasMany::class);
    expect((new Company)->paymentMethods())->toBeInstanceOf(HasMany::class);
    expect((new Company)->series())->toBeInstanceOf(HasMany::class);

    expect((new Department)->provinces())->toBeInstanceOf(HasMany::class);

    expect((new Province)->department())->toBeInstanceOf(BelongsTo::class);
    expect((new Province)->districts())->toBeInstanceOf(HasMany::class);

    expect((new District)->province())->toBeInstanceOf(BelongsTo::class);

    expect((new PaymentMethod)->company())->toBeInstanceOf(BelongsTo::class);

    expect((new SaleDocument)->company())->toBeInstanceOf(BelongsTo::class);
    expect((new SaleDocument)->client())->toBeInstanceOf(BelongsTo::class);
    expect((new SaleDocument)->items())->toBeInstanceOf(HasMany::class);
    expect((new SaleDocument)->discounts())->toBeInstanceOf(HasMany::class);

    expect((new SaleDocumentItem)->saleDocument())->toBeInstanceOf(BelongsTo::class);
    expect((new SaleDocumentItem)->discounts())->toBeInstanceOf(HasMany::class);

    expect((new Discount)->saleDocument())->toBeInstanceOf(BelongsTo::class);
    expect((new Discount)->saleDocumentItem())->toBeInstanceOf(BelongsTo::class);

    expect((new Serie)->company())->toBeInstanceOf(BelongsTo::class);
});

test('uuid models are non-incrementing and use string keys', function () {
    $uuidModels = [
        new Client,
        new Company,
        new Department,
        new Discount,
        new District,
        new PaymentMethod,
        new Product,
        new Province,
        new SaleDocument,
        new SaleDocumentItem,
        new Serie,
    ];

    foreach ($uuidModels as $model) {
        expect($model->getIncrementing())->toBeFalse();
        expect($model->getKeyType())->toBe('string');
    }
});

test('models define expected fillable attributes', function () {
    $expectedFillable = [
        Client::class => [
            'name',
            'trade_name',
            'doc_identity_type',
            'document_number',
            'address',
            'department',
            'province',
            'district',
            'telephone',
            'is_active',
        ],
        Company::class => [
            'company_name',
            'ruc',
            'urbanization',
            'address',
            'cod_local',
            'sol_user',
            'sol_pass',
            'cert_path',
            'logo_path',
            'production',
            'ubigueo',
            'department',
            'province',
            'district',
        ],
        Department::class => [
            'code',
            'description',
            'is_active',
        ],
        District::class => [
            'description',
            'code',
            'is_active',
            'province_id',
        ],
        Discount::class => [
            'type',
            'base_amount',
            'factor_porcentage',
            'discount_amount',
            'sale_document_id',
            'sale_document_item_id',
        ],
        PaymentMethod::class => [
            'document_type',
            'name',
            'description',
            'is_active',
            'company_id',
        ],
        Product::class => [
            'name',
            'unit',
            'sku',
            'price',
            'is_active',
        ],
        Province::class => [
            'description',
            'code',
            'is_active',
            'department_id',
        ],
        SaleDocument::class => [
            'document_type',
            'ubl_version',
            'doc_sunat_type',
            'operation_type',
            'payment_form',
            'currency',
            'serie',
            'correlative',
            'credit_days',
            'num_quota',
            'total_taxed',
            'total_exempted',
            'total_unaffected',
            'total_export',
            'total_free',
            'total_igv',
            'total_igv_free',
            'icbper',
            'total_taxes',
            'sale_value',
            'sub_total',
            'total_sale',
            'rounding',
            'total',
            'sunat_state',
            'hash',
            'xml',
            'cdr',
            'legends',
            'date_issue',
            'date_expiration',
            'additional_info',
            'status',
            'client_id',
            'company_id',
        ],
        SaleDocumentItem::class => [
            'code',
            'description',
            'unit',
            'quantity',
            'unit_value',
            'unit_price',
            'item_value',
            'igv_affectation_type',
            'igv_base_amount',
            'igv_percent',
            'igv_amount',
            'icbper_factor',
            'icbper_amount',
            'taxes_total',
            'is_active',
            'sale_document_id',
        ],
        Serie::class => [
            'doc_sunat_type',
            'description',
            'code',
            'correlative',
            'is_active',
            'company_id',
        ],
    ];

    foreach ($expectedFillable as $modelClass => $fillable) {
        $model = new $modelClass;

        expect(collect($model->getFillable())->sort()->values()->all())
            ->toBe(collect($fillable)->sort()->values()->all(), "Fillable mismatch for [{$modelClass}].");
    }
});
