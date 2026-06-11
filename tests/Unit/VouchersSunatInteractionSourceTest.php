<?php

it('opens the sunat modal on the client without rerendering vouchers', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php',
    );

    expect($source)
        ->toContain("\$wire.\$dispatchTo('send-modal', 'open-send-modal'")
        ->not->toContain('x-on:click="$dispatchTo(')
        ->not->toContain('wire:click="confirmSend(');
});

it('queues sunat with one conditional sale update and one parent event', function (): void {
    $source = collect(glob(dirname(__DIR__, 2).'/resources/views/components/*send-modal*.blade.php'))
        ->map(fn (string $path): string => file_get_contents($path))
        ->implode("\n");

    expect($source)
        ->toContain("#[On('open-send-modal')]")
        ->toContain("->whereIn('status', [")
        ->toContain('->update([')
        ->not->toContain("->select(['id', 'status'])")
        ->not->toContain("dispatch('closed-modal-send'");
});

it('does not use livewire polling for queued document statuses', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php',
    );

    expect($source)
        ->not->toContain('wire:poll')
        ->not->toContain('refreshQueuedStatuses')
        ->toContain("route('sale.statuses')")
        ->toContain('sale-document-status-updated');
});
