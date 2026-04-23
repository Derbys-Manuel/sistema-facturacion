<?php

use Illuminate\Support\Facades\Route;

test('settings pages are disabled', function () {
    expect(Route::has('profile.edit'))->toBeFalse();
    expect(Route::has('security.edit'))->toBeFalse();
    expect(Route::has('appearance.edit'))->toBeFalse();

    $this->get('/settings')->assertNotFound();
    $this->get('/settings/profile')->assertNotFound();
    $this->get('/settings/security')->assertNotFound();
    $this->get('/settings/appearance')->assertNotFound();
});
