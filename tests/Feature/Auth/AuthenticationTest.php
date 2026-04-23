<?php

use Illuminate\Support\Facades\Route;

test('authentication routes are disabled', function () {
    expect(Route::has('login'))->toBeFalse();
    expect(Route::has('login.store'))->toBeFalse();
    expect(Route::has('logout'))->toBeFalse();

    expect(Route::has('register'))->toBeFalse();
    expect(Route::has('register.store'))->toBeFalse();

    expect(Route::has('password.request'))->toBeFalse();
    expect(Route::has('password.email'))->toBeFalse();
    expect(Route::has('password.reset'))->toBeFalse();
    expect(Route::has('password.update'))->toBeFalse();
    expect(Route::has('password.confirm'))->toBeFalse();
    expect(Route::has('password.confirm.store'))->toBeFalse();

    expect(Route::has('verification.notice'))->toBeFalse();
    expect(Route::has('verification.send'))->toBeFalse();
    expect(Route::has('verification.verify'))->toBeFalse();

    expect(Route::has('two-factor.login'))->toBeFalse();
});

test('authentication pages return 404', function () {
    $this->get('/login')->assertNotFound();
    $this->get('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/reset-password/test-token')->assertNotFound();
});
