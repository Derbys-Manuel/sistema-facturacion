<?php

it('delegates pdf generation to the cached pdf action', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/InvoiceController.php');

    expect($source)
        ->toContain('GenerateSaleDocumentPdf')
        ->toContain('$generatePdf->handle($sale)')
        ->not->toContain('GenerateSaleDocumentPdfJob')
        ->not->toContain('sale.pdf-pending')
        ->not->toContain('generatePdfReport(');
});
