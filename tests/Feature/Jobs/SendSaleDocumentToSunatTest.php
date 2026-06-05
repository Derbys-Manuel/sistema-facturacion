<?php

use App\Enums\DocumentType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use App\Jobs\SendSaleDocumentToSunat;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function createQueuedSunatSaleDocument(string $status = 'borrador'): SaleDocument
{
    $company = Company::query()->create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
    ]);

    $client = Client::query()->create([
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

    return SaleDocument::query()->create([
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
        'status' => $status,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ]);
}

it('queues a unique sunat job for the selected sale document', function (): void {
    Queue::fake();

    SendSaleDocumentToSunat::dispatch('sale-id');

    Queue::assertPushed(
        SendSaleDocumentToSunat::class,
        fn (SendSaleDocumentToSunat $job): bool => $job->uniqueId() === 'sale-id'
            && $job->tries === 3
            && $job->timeout === 120,
    );
});

it('marks the sale document as waiting when queued from the send modal', function (): void {
    Queue::fake();
    $sale = createQueuedSunatSaleDocument();

    Livewire::test('send-modal', ['saleId' => (string) $sale->id])
        ->call('sendSunat');

    expect($sale->refresh()->status->value)->toBe('esperando');

    Queue::assertPushed(
        SendSaleDocumentToSunat::class,
        fn (SendSaleDocumentToSunat $job): bool => $job->uniqueId() === (string) $sale->id,
    );
});

it('does not show the sunat send option while the document is waiting in queue', function (): void {
    $sale = createQueuedSunatSaleDocument('esperando');

    Livewire::test('pages::sale.vouchers')
        ->call('setCompany', (string) $sale->company_id)
        ->assertSee('esperando')
        ->assertDontSee('Enviar a sunat');
});
