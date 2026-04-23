<?php

use Illuminate\Support\Facades\Route;

test('two factor authentication is disabled', function () {
    expect(Route::has('two-factor.login'))->toBeFalse();

    $this->get('/two-factor-challenge')->assertNotFound();
});
