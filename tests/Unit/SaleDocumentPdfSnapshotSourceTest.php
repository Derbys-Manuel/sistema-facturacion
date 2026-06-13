<?php

it('stores pdf snapshots on the local filesystem', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Services/SaleDocumentPdfSnapshot.php');

    expect($source)
        ->toContain("Storage::disk('local')")
        ->toContain('json_encode(')
        ->toContain('json_decode(')
        ->toContain('sale-document-snapshots/');
});

it('builds the snapshot while sale creation data is still in memory', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/SaleForm.php');

    expect($source)
        ->toContain('SaleDocumentPdfSnapshot')
        ->toContain('app(SaleDocumentPdfSnapshot::class)->store(')
        ->toContain("'pdfSnapshotPath'");
});

it('prepares historical voucher snapshots with one eager-loaded database read', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Services/SaleDocumentPdfSnapshot.php');

    expect($source)
        ->toContain('public function storeFromDatabase(string $saleId): string')
        ->toContain("->with(['items.discounts', 'discounts', 'client'])")
        ->toContain('return $this->store($sale, $data);');
});
