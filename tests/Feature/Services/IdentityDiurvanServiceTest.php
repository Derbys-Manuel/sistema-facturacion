<?php

use App\Services\IdentityDiurvanService;
use Illuminate\Support\Facades\Http;

it('caches successful identity responses by document', function (): void {
    config()->set('services.diurvan.url', 'https://identity.example.test');
    config()->set('services.diurvan.key', 'testing-key');

    Http::fake([
        'identity.example.test/*' => Http::response([
            'success' => true,
            'nombre' => 'Cliente',
        ]),
    ]);

    $service = app(IdentityDiurvanService::class);

    expect($service->searchDni('12345678'))->toBe($service->searchDni('12345678'));

    Http::assertSentCount(1);
});
