<?php

use Illuminate\Support\Facades\Route;

test('email verification is disabled', function () {
    expect(Route::has('verification.notice'))->toBeFalse();
    expect(Route::has('verification.send'))->toBeFalse();
    expect(Route::has('verification.verify'))->toBeFalse();

    $this->get('/email/verify')->assertNotFound();
});
