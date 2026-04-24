<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\CreditQuota;
use App\Models\Department;
use App\Models\District;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Province;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\Serie;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('models define expected relationships', function () {
    expect((new Client())->department())->toBeInstanceOf(BelongsTo::class);
    expect((new Client())->province())->toBeInstanceOf(BelongsTo::class);
    expect((new Client())->district())->toBeInstanceOf(BelongsTo::class);
    expect((new Client())->saleDocuments())->toBeInstanceOf(HasMany::class);

    expect((new Company())->department())->toBeInstanceOf(BelongsTo::class);
    expect((new Company())->province())->toBeInstanceOf(BelongsTo::class);
    expect((new Company())->district())->toBeInstanceOf(BelongsTo::class);
    expect((new Company())->paymentMethods())->toBeInstanceOf(HasMany::class);
    expect((new Company())->saleDocuments())->toBeInstanceOf(HasMany::class);

    expect((new Department())->provinces())->toBeInstanceOf(HasMany::class);
    expect((new Department())->clients())->toBeInstanceOf(HasMany::class);
    expect((new Department())->companies())->toBeInstanceOf(HasMany::class);

    expect((new Province())->department())->toBeInstanceOf(BelongsTo::class);
    expect((new Province())->districts())->toBeInstanceOf(HasMany::class);
    expect((new Province())->clients())->toBeInstanceOf(HasMany::class);
    expect((new Province())->companies())->toBeInstanceOf(HasMany::class);

    expect((new District())->province())->toBeInstanceOf(BelongsTo::class);
    expect((new District())->clients())->toBeInstanceOf(HasMany::class);
    expect((new District())->companies())->toBeInstanceOf(HasMany::class);

    expect((new SaleDocument())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new SaleDocument())->client())->toBeInstanceOf(BelongsTo::class);
    expect((new SaleDocument())->items())->toBeInstanceOf(HasMany::class);
    expect((new SaleDocument())->creditQuotas())->toBeInstanceOf(HasMany::class);
    expect((new SaleDocument())->payments())->toBeInstanceOf(HasMany::class);

    expect((new SaleDocumentItem())->saleDocument())->toBeInstanceOf(BelongsTo::class);

    expect((new CreditQuota())->saleDocument())->toBeInstanceOf(BelongsTo::class);
    expect((new CreditQuota())->payments())->toBeInstanceOf(HasMany::class);

    expect((new PaymentMethod())->company())->toBeInstanceOf(BelongsTo::class);
    expect((new PaymentMethod())->payments())->toBeInstanceOf(HasMany::class);

    expect((new Payment())->saleDocument())->toBeInstanceOf(BelongsTo::class);
    expect((new Payment())->paymentMethod())->toBeInstanceOf(BelongsTo::class);
    expect((new Payment())->creditQuota())->toBeInstanceOf(BelongsTo::class);

    expect((new Pack())->items())->toBeInstanceOf(HasMany::class);
    expect((new Pack())->products())->toBeInstanceOf(BelongsToMany::class);

    expect((new PackItem())->pack())->toBeInstanceOf(BelongsTo::class);
    expect((new PackItem())->product())->toBeInstanceOf(BelongsTo::class);

    expect((new Product())->packItems())->toBeInstanceOf(HasMany::class);
    expect((new Product())->packs())->toBeInstanceOf(BelongsToMany::class);
});

test('uuid models are non-incrementing and use string keys', function () {
    $uuidModels = [
        new Client(),
        new Company(),
        new CreditQuota(),
        new Department(),
        new District(),
        new Pack(),
        new Payment(),
        new PaymentMethod(),
        new Product(),
        new Province(),
        new SaleDocument(),
        new SaleDocumentItem(),
        new Serie(),
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
            'last_name',
            'trade_name',
            'address',
            'email',
            'telephone',
            'doc_identity_type',
            'document_number',
            'is_active',
            'department_id',
            'province_id',
            'district_id',
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
            'department_id',
            'province_id',
            'district_id',
        ],
        CreditQuota::class => [
            'document_type',
            'number',
            'date_expiration',
            'date_paid',
            'total_to_pay',
            'total_paid',
            'is_active',
            'sale_document_id',
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
        Pack::class => [
            'name',
            'price',
            'is_active',
        ],
        PackItem::class => [
            'quantity',
            'price',
            'is_active',
            'pack_id',
            'product_id',
        ],
        Payment::class => [
            'document_type',
            'date',
            'amount',
            'note',
            'sale_document_id',
            'payment_method_id',
            'credit_quota_id',
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
            'is_active',
            'company_id',
            'client_id',
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
            'document_type',
            'description',
            'code',
            'correlative',
            'is_active',
        ],
    ];

    foreach ($expectedFillable as $modelClass => $fillable) {
        $model = new $modelClass();

        expect(collect($model->getFillable())->sort()->values()->all())
            ->toBe(collect($fillable)->sort()->values()->all(), "Fillable mismatch for [{$modelClass}].");
    }
});
