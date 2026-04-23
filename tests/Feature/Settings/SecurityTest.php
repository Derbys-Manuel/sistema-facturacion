<?php

use Illuminate\Support\Facades\Route;

test('security settings are disabled', function () {
    expect(Route::has('security.edit'))->toBeFalse();

    $this->get('/settings/security')->assertNotFound();
});
