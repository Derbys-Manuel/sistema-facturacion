<?php

it('centralizes company reads in cache and invalidates them from model events', function (): void {
    $cacheSource = file_get_contents(dirname(__DIR__, 2).'/app/Services/CompanyCache.php');
    $modelSource = file_get_contents(dirname(__DIR__, 2).'/app/Models/Company.php');
    $selectorSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/components/⚡company-select.blade.php',
    );

    expect($cacheSource)
        ->toContain('Cache::rememberForever(self::LIST_KEY')
        ->toContain('$this->companyKey($id)')
        ->toContain("private const COMPANY_KEY_VERSION = 'v2';")
        ->toContain('->getAttributes()')
        ->toContain('(new Company)->newFromBuilder($attributes)')
        ->toContain('Cache::forget(self::LIST_KEY)')
        ->toContain('Cache::forget($this->companyKey($id))');

    expect($modelSource)
        ->toContain('CompanyCache::class')
        ->toContain('static::saved')
        ->toContain('static::deleted');

    expect($selectorSource)
        ->toContain('CompanyCache')
        ->not->toContain('Company::query()');
});

it('avoids redundant company reads during sale creation and pdf loading', function (): void {
    $saleFormSource = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/SaleForm.php');
    $controllerSource = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/InvoiceController.php');

    expect($saleFormSource)
        ->not->toContain("])->load('company', 'client');");

    expect($controllerSource)
        ->toContain('CompanyCache')
        ->toContain("->with(['items', 'client', 'items.discounts'])")
        ->not->toContain("->with(['items', 'client', 'company', 'items.discounts'])")
        ->toContain("\$sale->setRelation('company', \$companyCache->findOrFail");
});
