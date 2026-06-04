<?php

it('only rejects an affected invoice when its client is missing', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2).'/app/Livewire/Pages/Sale/CreateSaleDocumentPage.php',
    );

    expect($source)->toContain(
        '$this->sale->affectedDocSunatType === DocSunatType::FACTURA->value && ! $this->sale->clientId',
    );
});
