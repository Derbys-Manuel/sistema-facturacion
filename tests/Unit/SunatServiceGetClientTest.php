<?php

use App\Services\SunatService;

test('sunat service returns a generic client when client is null', function () {
    $service = new SunatService();

    $client = $service->getClient(null);

    expect($client->getNumDoc())->toBe('00000000');
    expect($client->getRznSocial())->toBe('CLIENTE-VARIOS');
});

