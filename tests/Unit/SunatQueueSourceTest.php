<?php

it('queues sunat sending and centralizes its implementation', function (): void {
    $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/⚡send-modal.blade.php');
    $job = file_get_contents(dirname(__DIR__, 2).'/app/Jobs/SendSaleDocumentToSunat.php');
    $form = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/SaleForm.php');

    expect($modal)
        ->toContain('SendSaleDocumentToSunat::dispatch($this->saleId)')
        ->not->toContain('$sunatService->send(')
        ->and($job)
        ->toContain('ShouldBeUnique')
        ->toContain('SendSaleDocumentToSunatAction')
        ->and($form)
        ->toContain('SendSaleDocumentToSunatAction $sendSaleDocumentToSunat')
        ->not->toContain('$sunatService->send($data, $sale)');
});
