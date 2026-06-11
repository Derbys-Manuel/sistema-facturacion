<?php

it('caches the sunat response and checks it every sixty seconds', function (): void {
    $cacheSource = file_get_contents(dirname(__DIR__, 2).'/app/Services/SaleDocumentStatusCache.php');
    $actionSource = file_get_contents(dirname(__DIR__, 2).'/app/Actions/Sales/SendSaleDocumentToSunatAction.php');
    $vouchersSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php',
    );

    expect($cacheSource)
        ->toContain('?array $sunatResponse = null')
        ->toContain("'sunatResponse' => \$sunatResponse");

    expect($actionSource)
        ->toContain("\$response['sunatResponse'] ?? null");

    expect($vouchersSource)
        ->toContain('setInterval(() => this.refreshStatuses(), 60000)')
        ->toContain('sunatResponse = $event.detail.sunatResponse')
        ->toContain('SUNAT: Aceptado')
        ->toContain('cdrResponse.description');
});

it('keeps discounts hidden without changing them and does not overwrite desired total', function (): void {
    $modalSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/components/sale/⚡modal-item.blade.php',
    );

    expect($modalSource)
        ->toContain('x-data="{ showDiscounts: false }"')
        ->toContain('x-model="showDiscounts"')
        ->toContain('x-show="showDiscounts"')
        ->toContain('Mostrar descuentos')
        ->not->toContain("\$this->saleItem->desiredTotal = \$item['total'] ?? 0;");
});
