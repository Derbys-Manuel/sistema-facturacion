<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\Sunat\AffecType;
use App\Enums\Sunat\CreditNoteReasonType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Models\Serie;
use App\Services\SerieService;
use Livewire\Livewire;

it('creates a credit note from the nota credito page', function () {
    $company = Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);

    $client = Client::create([
        'name' => 'Juan Perez',
        'trade_name' => null,
        'doc_identity_type' => DocIdentityType::DNI->value,
        'document_number' => '12345678',
        'address' => null,
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
        'telephone' => null,
        'is_active' => true,
    ]);

    $affected = SaleDocument::create([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
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
        'status' => DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);

    Serie::create([
        'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
        'affected_doc_sunat_type' => DocSunatType::BOLETA->value,
        'description' => 'Nota de crÃ©dito - Boleta',
        'code' => 'BC01',
        'correlative' => '00000000',
        'is_active' => true,
        'company_id' => $company->id,
    ]);

    $serie = app(SerieService::class)->getSerieForUpdate(
        DocSunatType::NOTA_CREDITO->value,
        (string) $company->id,
        DocSunatType::BOLETA->value,
    );
    expect($serie->code)->toBe('BC01');

    $items = [
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P001',
            'description' => 'Producto',
            'unit' => 'NIU',
            'quantity' => 1,
            'unitValue' => 100,
            'itemValue' => 100,
            'unitPrice' => 118,
            'igvBaseAmount' => 100,
            'igvPercent' => 18,
            'igvAmount' => 18,
            'taxesTotal' => 18,
            'discounts' => [],
            'total' => 118,
        ],
    ];

    Livewire::test('pages::sale.create-nota-credito')
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.affectedDocSunatType', DocSunatType::BOLETA->value)
        ->set('sale.clientId', (string) $client->id)
        ->set('sale.affectedSaleDocumentId', (string) $affected->id)
        ->set('sale.affectedSerie', 'B001')
        ->set('sale.affectedCorrelative', '00000010')
        ->set('sale.noteReasonCode', CreditNoteReasonType::CANCEL_OPERATION->value)
        ->set('sale.noteReasonDescription', 'AnulaciÃ³n de la operaciÃ³n')
        ->set('items', $items)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sale_documents', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
        'serie' => 'BC01',
        'correlative' => '00000001',
        'affected_sale_document_id' => $affected->id,
        'affected_doc_sunat_type' => DocSunatType::BOLETA->value,
        'affected_serie' => 'B001',
        'affected_correlative' => '00000010',
        'note_reason_code' => CreditNoteReasonType::CANCEL_OPERATION->value,
        'note_reason_description' => 'AnulaciÃ³n de la operaciÃ³n',
        'status' => DocumentStatus::DRAFT->value,
    ]);
});
