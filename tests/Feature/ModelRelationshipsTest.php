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

