<?php

use App\Actions\Sales\SendSaleDocumentToSunatAction;
use App\Enums\DocumentStatus;
use App\Enums\Sunat\AffecType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Livewire\Pages\Sale\CreateSaleDocumentPage;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Models\Serie;
use App\Services\SunatService;
use Livewire\Livewire;

it('creates a sale document from the boleta page', function () {
    app()->instance(SunatService::class, new class extends SunatService
    {
        public function send(array $data, SaleDocument $sale): array
        {
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

    Serie::create([
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'description' => 'Boleta',
        'code' => 'B001',
        'correlative' => '00000001',
        'is_active' => true,
        'company_id' => $company->id,
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

    Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.clientId', (string) $client->id)
        ->set('items', $items)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sale_documents', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'serie' => 'B001',
        'correlative' => '00000002',
        'status' => DocumentStatus::DRAFT->value,
    ]);

    $saleDocumentId = SaleDocument::query()->value('id');

    $this->assertDatabaseHas('sale_document_items', [
        'sale_document_id' => $saleDocumentId,
        'code' => 'P001',
    ]);
});

it('marks the sale document as approved when SUNAT accepts', function () {
    app()->instance(SunatService::class, new class extends SunatService
    {
        public function send(array $data, SaleDocument $sale): array
        {
            return [
                'success' => true,
                'xml' => '<xml/>',
                'hash' => 'hash',
                'pdfUrl' => route('sale.pdf', $sale->id),
                'sunatResponse' => [
                    'success' => true,
                    'cdrResponse' => [
                        'notes' => ['OBS-1'],
                    ],
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

    Serie::create([
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'description' => 'Boleta',
        'code' => 'B001',
        'correlative' => '00000001',
        'is_active' => true,
        'company_id' => $company->id,
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

    Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.clientId', (string) $client->id)
        ->set('items', $items)
        ->call('save')
        ->assertHasNoErrors();

    $saleDocumentId = (string) SaleDocument::query()->value('id');

    Livewire::test('send-modal', ['saleId' => $saleDocumentId])
        ->call('sendSunat')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sale_documents', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'status' => DocumentStatus::APPROVED->value,
    ]);
});

it('keeps the sale document retryable when SUNAT is unreachable', function () {
    app()->instance(SunatService::class, new class extends SunatService
    {
        public function send(array $data, SaleDocument $sale): array
        {
            return [
                'success' => false,
                'xml' => null,
                'hash' => null,
                'pdfUrl' => route('sale.pdf', $sale->id),
                'sunatResponse' => [
                    'success' => false,
                    'error' => [
                        'code' => 'CONNECTION_ERROR',
                        'message' => 'Network error',
                    ],
                ],
                'error' => 'Network error',
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

    Serie::create([
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'description' => 'Boleta',
        'code' => 'B001',
        'correlative' => '00000001',
        'is_active' => true,
        'company_id' => $company->id,
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

    Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.clientId', (string) $client->id)
        ->set('items', $items)
        ->call('save')
        ->assertHasNoErrors();

    $saleDocumentId = (string) SaleDocument::query()->value('id');

    expect(
        fn () => app(SendSaleDocumentToSunatAction::class)->handle($saleDocumentId),
    )->toThrow(RuntimeException::class, 'Network error');

    $this->assertDatabaseHas('sale_documents', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'status' => DocumentStatus::DRAFT->value,
    ]);
});

it('loads and updates a draft sale document from vouchers edit', function () {
    $company = Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);

    Serie::create([
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'description' => 'Boleta',
        'code' => 'B001',
        'correlative' => '00000001',
        'is_active' => true,
        'company_id' => $company->id,
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

    Livewire::test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.clientId', (string) $client->id)
        ->set('items', $items)
        ->call('save')
        ->assertHasNoErrors();

    $saleId = (string) SaleDocument::query()->value('id');

    $updatedItems = [
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P001',
            'description' => 'Producto',
            'unit' => 'NIU',
            'quantity' => 2,
            'unitValue' => 100,
            'itemValue' => 200,
            'unitPrice' => 118,
            'igvBaseAmount' => 200,
            'igvPercent' => 18,
            'igvAmount' => 36,
            'taxesTotal' => 36,
            'discounts' => [],
            'total' => 236,
        ],
    ];

    Livewire::withQueryParams(['edit' => $saleId])
        ->test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
        ->assertSet('editingSaleId', $saleId)
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.clientId', (string) $client->id)
        ->set('items', $updatedItems)
        ->call('save')
        ->assertHasNoErrors();

    expect(SaleDocument::count())->toBe(1);

    $this->assertDatabaseHas('sale_documents', [
        'id' => $saleId,
        'serie' => 'B001',
        'correlative' => '00000002',
        'status' => DocumentStatus::DRAFT->value,
    ]);

    $this->assertDatabaseHas('sale_document_items', [
        'sale_document_id' => $saleId,
        'code' => 'P001',
        'quantity' => 2,
    ]);
});

it('keeps existing line totals when loading a draft sale for edit', function () {
    $company = Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);

    $sale = SaleDocument::create([
        'document_type' => 'sale',
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'operation_type' => '0101',
        'payment_form' => 'contado',
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000010',
        'total_taxed' => 127.03,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 22.87,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 22.87,
        'sale_value' => 127.03,
        'sub_total' => 149.90,
        'total_sale' => 149.90,
        'rounding' => 0,
        'total' => 149.90,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'status' => DocumentStatus::DRAFT->value,
        'company_id' => $company->id,
        'sunat_state' => true,
    ]);

    $sale->items()->create([
        'code' => 'P149',
        'description' => 'Producto 149.90',
        'unit' => 'NIU',
        'quantity' => 3,
        'unit_value' => 42.34,
        'unit_price' => 49.97,
        'item_value' => 127.03,
        'igv_affectation_type' => AffecType::GRAVADO->value,
        'igv_base_amount' => 127.03,
        'igv_percent' => 18,
        'igv_amount' => 22.87,
        'icbper_amount' => 0,
        'total_taxes' => 22.87,
    ]);

    $component = Livewire::withQueryParams(['edit' => (string) $sale->id])
        ->test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value])
        ->assertSet('items.0.total', '149.90');

    expect((float) $component->get('sale.total'))->toBe(149.90);
});

it('keeps saved document totals when item rounding differs by one cent on edit', function () {
    $company = Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);

    $sale = SaleDocument::create([
        'document_type' => 'sale',
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'operation_type' => '0101',
        'payment_form' => 'contado',
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000011',
        'total_taxed' => 127.03,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 22.87,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 22.87,
        'sale_value' => 127.03,
        'sub_total' => 149.90,
        'total_sale' => 149.90,
        'rounding' => 0,
        'total' => 149.90,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'status' => DocumentStatus::DRAFT->value,
        'company_id' => $company->id,
        'sunat_state' => true,
    ]);

    foreach ([
        ['AMPOLLA', 82.17, 14.79, 96.96],
        ['JABON AZUFRE', 18.70, 3.37, 22.07],
        ['MASCARILLA VERDE', 26.17, 4.71, 30.88],
    ] as [$description, $itemValue, $igvAmount, $unitPrice]) {
        $sale->items()->create([
            'code' => '00000',
            'description' => $description,
            'unit' => 'NIU',
            'quantity' => 1,
            'unit_value' => $itemValue,
            'unit_price' => $unitPrice,
            'item_value' => $itemValue,
            'igv_affectation_type' => AffecType::GRAVADO->value,
            'igv_base_amount' => $itemValue,
            'igv_percent' => 18,
            'igv_amount' => $igvAmount,
            'icbper_amount' => 0,
            'total_taxes' => $igvAmount,
        ]);
    }

    $component = Livewire::withQueryParams(['edit' => (string) $sale->id])
        ->test(CreateSaleDocumentPage::class, ['docSunatType' => DocSunatType::BOLETA->value]);

    expect((float) $component->get('sale.saleValue'))->toBe(127.03)
        ->and((float) $component->get('sale.totalTaxes'))->toBe(22.87)
        ->and((float) $component->get('sale.total'))->toBe(149.90);
});
