<?php

test('vouchers page renders', function () {
    $this->get(route('vouchers'))
        ->assertOk()
        ->assertSee('TOTAL')
        ->assertSee('IGV');
});
