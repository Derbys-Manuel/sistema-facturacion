<?php

it('polls vouchers only while a document is waiting for sunat response', function (): void {
    $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php');
    $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/⚡send-modal.blade.php');

    expect($view)
        ->toContain('DocumentStatus::WAITING->value')
        ->toContain('public array $queuedSaleStatuses = []')
        ->toContain("#[On('sale-document-queued')]")
        ->toContain('public function saleDocumentQueued(string $saleId): void')
        ->toContain('$this->displayStatus($row)')
        ->toContain('$hasWaitingDocuments')
        ->toContain('wire:poll.3s')
        ->and($modal)
        ->toContain("'sale-document-queued'")
        ->toContain('\'closed-modal-send\', saleId: (string) $sale->id');
});
