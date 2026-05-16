<?php

test('vouchers page renders', function () {
    $this->get(route('vouchers'))
        ->assertOk()
        ->assertSee('TOTAL')
        ->assertSee('IGV');
});

test('nota credito page renders', function () {
    $this->get(route('create-nota-credito'))
        ->assertOk()
        ->assertSee('Documento afectado');
});
