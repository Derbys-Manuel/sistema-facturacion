<?php

it('waits for queued pdf generation before loading the iframe', function (): void {
    $component = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/sale/pdf-preview-modal.blade.php');

    expect($component)
        ->toContain("'statusUrl' => null")
        ->toContain('pollPdfStatus')
        ->toContain("status === 'ready'")
        ->toContain("status === 'failed'")
        ->toContain('Generando PDF...')
        ->toContain('x-bind:src="pdfUrl"');
});

it('dispatches pdf generation from creation and vouchers', function (): void {
    $createPage = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Pages/Sale/CreateSaleDocumentPage.php');
    $vouchers = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php');

    expect($createPage)
        ->toContain("GenerateSaleDocumentPdfJob::dispatch(\n                \$this->savedSaleId,\n                \$result['pdfSnapshotPath']")
        ->toContain("route('sale.pdf-status'");

    expect($vouchers)
        ->toContain('$snapshotPath = $pdfSnapshot->storeFromDatabase($saleId)')
        ->toContain('GenerateSaleDocumentPdfJob::dispatch($saleId, $snapshotPath)')
        ->toContain("route('sale.pdf-status'");
});
