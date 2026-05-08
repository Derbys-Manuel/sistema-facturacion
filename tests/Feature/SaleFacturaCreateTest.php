<?php

use App\Enums\Sunat\AffecType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Models\Serie;
use App\Services\SunatService;
use Livewire\Livewire;

it('creates a sale document from the factura page', function () {
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
        'doc_sunat_type' => DocSunatType::FACTURA->value,
        'description' => 'Factura',
        'code' => 'F001',
        'correlative' => '00000000',
        'is_active' => true,
        'company_id' => $company->id,
    ]);

    $client = Client::create([
        'name' => 'ACME SAC',
        'trade_name' => 'ACME SAC',
        'doc_identity_type' => DocIdentityType::RUC->value,
        'document_number' => '20123456789',
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

    Livewire::test('pages::sale.create-factura')
        ->set('sale.companyId', (string) $company->id)
        ->set('sale.clientId', (string) $client->id)
        ->set('items', $items)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sale_documents', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'doc_sunat_type' => DocSunatType::FACTURA->value,
        'serie' => 'F001',
        'correlative' => '00000001',
    ]);
});

