<?php

use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a sale document from the create modal', function () {
    $department = Department::create([
        'code' => '99',
        'description' => 'Test Department',
    ]);

    $province = Province::create([
        'description' => 'Test Province',
        'code' => '9901',
        'department_id' => $department->id,
    ]);

    $district = District::create([
        'description' => 'Test District',
        'code' => '990101',
        'province_id' => $province->id,
    ]);

    $company = Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department_id' => $department->id,
        'province_id' => $province->id,
        'district_id' => $district->id,
    ]);

    $client = Client::create([
        'name' => 'Juan',
        'last_name' => 'Pérez',
        'trade_name' => null,
        'address' => null,
        'email' => null,
        'telephone' => null,
        'doc_identity_type' => DocIdentityType::DNI->value,
        'document_number' => '12345678',
        'department_id' => $department->id,
        'province_id' => $province->id,
        'district_id' => $district->id,
    ]);

    Livewire::test('sale.create')
        ->set('sale.company_id', $company->id)
        ->set('sale.client_id', $client->id)
        ->set('sale.doc_sunat_type', DocSunatType::BOLETA->value)
        ->set('sale.operation_type', OperationType::INTERNAL_SALE->value)
        ->set('sale.payment_form', PaymentForm::CASH->value)
        ->set('sale.currency', 'PEN')
        ->set('sale.serie', 'V001')
        ->set('sale.correlative', '00000001')
        ->set('sale.date_issue', now()->toDateString())
        ->set('sale.date_expiration', now()->toDateString())
        ->call('save')
        ->assertDispatched('modal-close', name: 'sale-create');

    $this->assertDatabaseHas('sale_documents', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'serie' => 'V001',
        'correlative' => '00000001',
        'currency' => 'PEN',
    ]);
});
