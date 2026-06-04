<?php

it('keeps voucher listing queries lean and index friendly', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Livewire/Forms/SaleForm.php');

    expect($source)
        ->not->toContain("->with(['items', 'client', 'company'])")
        ->not->toContain("->whereDate('date_issue'")
        ->not->toContain('(clone $query)')
        ->toContain("->with('client:id,name,trade_name,document_number')")
        ->toContain("->select(['id', 'serie', 'correlative'])");
});
