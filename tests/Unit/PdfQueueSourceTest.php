<?php

it('queues pdf generation after saving and serves a pending response when the cache is missing', function (): void {
    $form = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/SaleForm.php');
    $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/InvoiceController.php');
    $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/sale/pdf-pending.blade.php');

    expect($form)
        ->toContain('GenerateSaleDocumentPdf::dispatch((string) $sale->id);')
        ->and($controller)
        ->toContain('GenerateSaleDocumentPdfJob::dispatch($sale->id);')
        ->toContain('GenerateSaleDocumentPdf::pathFor($sale);')
        ->toContain('response()->view(\'sale.pdf-pending\'')
        ->and($view)
        ->toContain('meta http-equiv="refresh" content="1;url={{ $reloadUrl }}"')
        ->toContain('Preparando PDF...');
});
