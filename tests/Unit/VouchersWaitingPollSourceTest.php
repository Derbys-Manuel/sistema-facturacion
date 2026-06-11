<?php

it('polls local cache instead of livewire while a document waits for sunat response', function (): void {
    $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php');
    $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/⚡send-modal.blade.php');

    expect($view)
        ->toContain('DocumentStatus::WAITING->value')
        ->toContain('$this->displayStatus($row)')
        ->toContain("route('sale.statuses')")
        ->toContain('setInterval(() => this.refreshStatuses(), 60000)')
        ->not->toContain('wire:poll')
        ->and($modal)
        ->toContain("'sale-document-queued'")
        ->not->toContain("'closed-modal-send'");
});
