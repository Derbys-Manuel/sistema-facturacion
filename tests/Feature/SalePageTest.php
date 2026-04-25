<?php

test('sale page renders', function () {
    $response = $this->get('/sale');

    $response
        ->assertOk()
        ->assertSee('Ventas')
        ->assertSee('Pendiente')
        ->assertSee('Pagado')
        ->assertSee('Total');
});
