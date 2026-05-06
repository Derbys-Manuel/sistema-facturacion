<?php

use App\Enums\Sunat\DocIdentityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('fills client fields when consulting a RUC', function () {
    config()->set('services.diurvan.url', 'https://diurvanconsultores.com/apidiurvan/api');
    config()->set('services.diurvan.key', 'test-key');

    Http::fake([
        'diurvanconsultores.com/*' => Http::response([
            'success' => true,
            'message' => [
                'tipo_documento' => 'RUC',
                'nro_documento' => '20615778207',
                'nombre_completo' => 'ACME SAC',
                'nombres' => '',
                'direccion' => 'Av. Siempre Viva 123',
                'ubigeo' => '200101-Piura/Piura/Piura',
            ],
        ], 200),
    ]);

    Livewire::test('client.create')
        ->set('client.docIdentityType', DocIdentityType::RUC->value)
        ->set('client.documentNumber', '20615778207')
        ->call('consultDocument')
        ->assertSet('client.name', null)
        ->assertSet('client.tradeName', 'ACME SAC')
        ->assertSet('client.address', 'Av. Siempre Viva 123')
        ->assertSet('client.department', 'Piura')
        ->assertSet('client.province', 'Piura')
        ->assertSet('client.district', 'Piura');
});
