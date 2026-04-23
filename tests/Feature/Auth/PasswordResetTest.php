<?php

use Illuminate\Support\Facades\Route;

test('password reset is disabled', function () {
    expect(Route::has('password.request'))->toBeFalse();
    expect(Route::has('password.email'))->toBeFalse();
    expect(Route::has('password.reset'))->toBeFalse();
    expect(Route::has('password.update'))->toBeFalse();

    $this->get('/forgot-password')->assertNotFound();
    $this->get('/reset-password/test-token')->assertNotFound();
});
