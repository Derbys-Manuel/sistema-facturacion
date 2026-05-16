<?php

test('vouchers page renders', function () {
    $this->get(route('vouchers'))
        ->assertOk()
        ->assertSee('TOTAL')
        ->assertSee('IGV')
        ->assertDontSee('Operación');
});

test('nota credito page renders', function () {
    $this->get(route('create-nota-credito'))
        ->assertOk()
        ->assertSee('Documento afectado');
});

test('vouchers can start a credit note only from approved vouchers', function () {
    $company = \App\Models\Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);

    $client = \App\Models\Client::create([
        'name' => 'Juan Perez',
        'trade_name' => null,
        'doc_identity_type' => \App\Enums\Sunat\DocIdentityType::DNI->value,
        'document_number' => '12345678',
        'address' => null,
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
        'telephone' => null,
        'is_active' => true,
    ]);

    $approvedSale = \App\Models\SaleDocument::create([
        'document_type' => \App\Enums\DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => \App\Enums\Sunat\DocSunatType::BOLETA->value,
        'operation_type' => \App\Enums\Sunat\OperationType::INTERNAL_SALE->value,
        'payment_form' => \App\Enums\Sunat\PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000010',
        'total_taxed' => 0,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 0,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 0,
        'sale_value' => 0,
        'sub_total' => 0,
        'total_sale' => 0,
        'rounding' => 0,
        'total' => 0,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'additional_info' => null,
        'status' => \App\Enums\DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);

    \Livewire\Livewire::test('pages::sale.vouchers')
        ->call('createCreditNote', (string) $approvedSale->id)
        ->assertRedirect(route('create-nota-credito', ['affected' => (string) $approvedSale->id]));

    $draftSale = \App\Models\SaleDocument::create([
        'document_type' => \App\Enums\DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => \App\Enums\Sunat\DocSunatType::BOLETA->value,
        'operation_type' => \App\Enums\Sunat\OperationType::INTERNAL_SALE->value,
        'payment_form' => \App\Enums\Sunat\PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000011',
        'total_taxed' => 0,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 0,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 0,
        'sale_value' => 0,
        'sub_total' => 0,
        'total_sale' => 0,
        'rounding' => 0,
        'total' => 0,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'additional_info' => null,
        'status' => \App\Enums\DocumentStatus::DRAFT->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);

    \Livewire\Livewire::test('pages::sale.vouchers')
        ->call('createCreditNote', (string) $draftSale->id)
        ->assertNoRedirect();
});

test('vouchers summary subtracts credit notes', function () {
    $company = \App\Models\Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);
    $client = \App\Models\Client::create([
        'name' => 'Juan Perez',
        'trade_name' => null,
        'doc_identity_type' => \App\Enums\Sunat\DocIdentityType::DNI->value,
        'document_number' => '12345678',
        'address' => null,
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
        'telephone' => null,
        'is_active' => true,
    ]);
    $today = now('America/Lima');
    \App\Models\SaleDocument::create([
        'document_type' => \App\Enums\DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => \App\Enums\Sunat\DocSunatType::BOLETA->value,
        'operation_type' => \App\Enums\Sunat\OperationType::INTERNAL_SALE->value,
        'payment_form' => \App\Enums\Sunat\PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000010',
        'total_taxed' => 82,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 18,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 18,
        'sale_value' => 82,
        'sub_total' => 100,
        'total_sale' => 100,
        'rounding' => 0,
        'total' => 100,
        'date_issue' => $today,
        'date_expiration' => $today,
        'additional_info' => null,
        'status' => \App\Enums\DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);
    \App\Models\SaleDocument::create([
        'document_type' => \App\Enums\DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => \App\Enums\Sunat\DocSunatType::FACTURA->value,
        'operation_type' => \App\Enums\Sunat\OperationType::INTERNAL_SALE->value,
        'payment_form' => \App\Enums\Sunat\PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'F001',
        'correlative' => '00000020',
        'total_taxed' => 164,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 36,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 36,
        'sale_value' => 164,
        'sub_total' => 200,
        'total_sale' => 200,
        'rounding' => 0,
        'total' => 200,
        'date_issue' => $today,
        'date_expiration' => $today,
        'additional_info' => null,
        'status' => \App\Enums\DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);
    \App\Models\SaleDocument::create([
        'document_type' => \App\Enums\DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => \App\Enums\Sunat\DocSunatType::NOTA_CREDITO->value,
        'operation_type' => \App\Enums\Sunat\OperationType::INTERNAL_SALE->value,
        'payment_form' => \App\Enums\Sunat\PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => '00000001',
        'total_taxed' => 41,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 9,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 9,
        'sale_value' => 41,
        'sub_total' => 50,
        'total_sale' => 50,
        'rounding' => 0,
        'total' => 50,
        'date_issue' => $today,
        'date_expiration' => $today,
        'additional_info' => null,
        'status' => \App\Enums\DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);
    $component = \Livewire\Livewire::test('pages::sale.vouchers')->call('setCompany', (string) $company->id);
    $summary = $component->get('summary');
    expect((float) ($summary['total'] ?? 0))->toBe(250.0);
    expect((float) ($summary['totalIgv'] ?? 0))->toBe(45.0);
    expect((float) ($summary['saleValue'] ?? 0))->toBe(205.0);
    $docSunatTypeValues = collect($component->get('docSunatTypeOptions'))->pluck('value')->all();
    expect($docSunatTypeValues)->toContain(\App\Enums\Sunat\DocSunatType::NOTA_CREDITO->value);
});
