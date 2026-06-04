<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\Sunat\AffecType;
use App\Enums\Sunat\CreditNoteReasonType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use App\Livewire\Pages\Sale\CreateSaleDocumentPage;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\Serie;
use App\Services\SerieService;
use App\Services\SunatService;
use Livewire\Livewire;

it('creates a credit note from the nota credito page', function () {
    $captured = null;

    app()->instance(SunatService::class, new class($captured) extends SunatService
    {
        public mixed $captured;

        public function __construct(mixed &$captured)
        {
            $this->captured = &$captured;
        }

        public function send(array $data, SaleDocument $sale): array
        {
            $this->captured = $data;

            return [
                'success' => true,
                'xml' => '<xml/>',
                'hash' => 'hash',
                'pdfUrl' => route('sale.pdf', $sale->id),
                'sunatResponse' => [
                    'success' => true,
                    'error' => null,
                ],
                'error' => null,
            ];
        }
    });

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
            'quantity' => 2,
            'unitValue' => 100,
            'itemValue' => 180,
            'unitPrice' => 118,
            'igvBaseAmount' => 180,
            'igvPercent' => 18,
            'igvAmount' => 32.40,
            'taxesTotal' => 32.40,
            'discounts' => [],
            'total' => 212.40,
        ],
    ];

    Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::NOTA_CREDITO->value])
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

    $saleDocumentId = (string) SaleDocument::query()
        ->where('doc_sunat_type', DocSunatType::NOTA_CREDITO->value)
        ->value('id');

    Livewire::test('send-modal', ['saleId' => $saleDocumentId])
        ->call('sendSunat')
        ->assertHasNoErrors();

    expect(is_array($captured))->toBeTrue();
    expect((string) ($captured['docSunatType'] ?? ''))->toBe(DocSunatType::NOTA_CREDITO->value);
    expect((float) data_get($captured, 'items.0.discounts.0.discountAmount', 0))->toBeGreaterThan(0);
});

it('prefills the credit note page from an approved voucher', function () {
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
        'total_taxed' => 100,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 18,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 18,
        'sale_value' => 100,
        'sub_total' => 118,
        'total_sale' => 118,
        'rounding' => 0,
        'total' => 118,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'additional_info' => null,
        'status' => DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);

    SaleDocumentItem::create([
        'code' => 'P001',
        'description' => 'Producto',
        'unit' => 'NIU',
        'quantity' => 1,
        'unit_value' => 100,
        'unit_price' => 118,
        'item_value' => 100,
        'igv_affectation_type' => AffecType::GRAVADO->value,
        'igv_base_amount' => 100,
        'igv_percent' => 18,
        'igv_amount' => 18,
        'icbper_factor' => null,
        'icbper_amount' => 0,
        'total_taxes' => 18,
        'is_active' => true,
        'sale_document_id' => $affected->id,
    ]);

    Livewire::withQueryParams(['affected' => (string) $affected->id])
        ->test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::NOTA_CREDITO->value])
        ->assertSet('sale.affectedSaleDocumentId', (string) $affected->id)
        ->assertSet('sale.affectedDocSunatType', DocSunatType::BOLETA->value)
        ->assertSet('sale.affectedSerie', 'B001')
        ->assertSet('sale.affectedCorrelative', '00000010')
        ->assertSet('sale.companyId', (string) $company->id)
        ->assertSet('sale.clientId', (string) $client->id)
        ->assertSet('items.0.code', 'P001')
        ->assertSet('items.0.description', 'Producto');
});

it('searches clients based on affected doc type', function () {
    $dniClient = Client::create([
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

    $rucClient = Client::create([
        'name' => null,
        'trade_name' => 'Juan Perez SAC',
        'doc_identity_type' => DocIdentityType::RUC->value,
        'document_number' => '20123456789',
        'address' => null,
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
        'telephone' => null,
        'is_active' => true,
    ]);

    $component = Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::NOTA_CREDITO->value])
        ->set('sale.affectedDocSunatType', DocSunatType::BOLETA->value)
        ->call('searchClient', 'Juan');

    $boletaResults = $component->get('clients');

    expect(collect($boletaResults)->pluck('value')->all())->toContain((string) $dniClient->id);
    expect(collect($boletaResults)->pluck('value')->all())->toContain((string) $rucClient->id);

    $component
        ->set('sale.affectedDocSunatType', DocSunatType::FACTURA->value)
        ->call('searchClient', 'Juan');

    $facturaResults = $component->get('clients');

    expect(collect($facturaResults)->pluck('value')->all())->not->toContain((string) $dniClient->id);
    expect(collect($facturaResults)->pluck('value')->all())->toContain((string) $rucClient->id);
});
