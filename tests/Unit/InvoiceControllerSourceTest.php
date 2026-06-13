<?php

it('only serves an already generated cached pdf', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/InvoiceController.php');

    expect($source)
        ->toContain('GenerateSaleDocumentPdf')
        ->toContain('$generatePdf->exists($sale)')
        ->toContain('$generatePdf->get($sale)')
        ->not->toContain('$generatePdf->handle($sale)')
        ->not->toContain('generatePdfReport(');
});

it('exposes a lightweight pdf generation status endpoint', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/InvoiceController.php');

    expect($source)
        ->toContain('public function pdfStatus(')
        ->toContain("'status' => 'ready'")
        ->toContain("'status' => 'failed'")
        ->toContain("'status' => 'pending'");
});
