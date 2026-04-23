<?php

use Illuminate\Support\Facades\Route;

test('registration is disabled', function () {
    expect(Route::has('register'))->toBeFalse();
    expect(Route::has('register.store'))->toBeFalse();

    $this->get('/register')->assertNotFound();
});
