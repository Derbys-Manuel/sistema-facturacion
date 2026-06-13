<?php

it('closes the pdf modal immediately with one livewire request', function (): void {
    $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/sale/pdf-preview-modal.blade.php');
    $createPage = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Pages/Sale/CreateSaleDocumentPage.php');

    expect($modal)
        ->toContain('visible: true')
        ->toContain('this.visible = false')
        ->toContain('$wire.{{ $closeAction }}();')
        ->not->toContain('$dispatch')
        ->not->toContain('closedEvent');

    expect($createPage)
        ->not->toContain("#[On('pdf-modal-closed')]")
        ->not->toContain('resetFromModal')
        ->toContain("public function closePdfPreview(): void\n    {")
        ->toContain('$this->resetForm();');
});
