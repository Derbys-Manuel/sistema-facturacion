<?php

use Illuminate\Support\Facades\Route;

test('password confirmation is disabled', function () {
    expect(Route::has('password.confirm'))->toBeFalse();
    expect(Route::has('password.confirm.store'))->toBeFalse();

    $this->get('/user/confirm-password')->assertNotFound();
});
