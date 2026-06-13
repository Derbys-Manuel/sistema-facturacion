<?php

it('generates sale document pdfs in a unique queued job', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Jobs/GenerateSaleDocumentPdfJob.php');

    expect($source)
        ->toContain('ShouldBeUnique')
        ->toContain('ShouldQueue')
        ->toContain('public int $timeout = 180')
        ->toContain('public int $uniqueFor = 300')
        ->toContain('$pdfSnapshot->get($this->snapshotPath)')
        ->toContain('$generatePdf->handleSnapshot($snapshot)')
        ->toContain('if (filled($this->snapshotPath)')
        ->toContain('$generatePdf->handle($sale)')
        ->toContain('public function failed(?Throwable $exception): void');
});
