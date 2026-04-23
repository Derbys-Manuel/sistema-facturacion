<?php

test('home redirects to the dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});

test('guests can visit the dashboard', function () {
    $response = $this->get('/dashboard');
    $response->assertOk();
});
