<?php

test('home redirects to vouchers', function () {
    $this->get('/')
        ->assertRedirectToRoute('vouchers');
});

test('guests can visit the dashboard', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('data-flux-sidebar-collapse');
});
