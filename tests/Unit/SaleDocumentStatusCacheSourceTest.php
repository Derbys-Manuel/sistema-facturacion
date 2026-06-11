<?php

it('stores sale document statuses in local cache from every job outcome', function (): void {
    $cacheSource = file_get_contents(dirname(__DIR__, 2).'/app/Services/SaleDocumentStatusCache.php');
    $modalSource = collect(glob(dirname(__DIR__, 2).'/resources/views/components/*send-modal*.blade.php'))
        ->map(fn (string $path): string => file_get_contents($path))
        ->implode("\n");
    $actionSource = file_get_contents(dirname(__DIR__, 2).'/app/Actions/Sales/SendSaleDocumentToSunatAction.php');
    $jobSource = file_get_contents(dirname(__DIR__, 2).'/app/Jobs/SendSaleDocumentToSunat.php');

    expect($cacheSource)
        ->toContain('Cache::put(')
        ->toContain('Cache::get(')
        ->toContain("private const KEY_VERSION = 'v3';")
        ->toContain("'updatedAt' => now()->toIso8601String()");

    expect($modalSource)
        ->toContain('SaleDocumentStatusCache')
        ->toContain('DocumentStatus::WAITING');

    expect($actionSource)
        ->toContain('SaleDocumentStatusCache')
        ->toContain('DocumentStatus::APPROVED,')
        ->toContain('$response[\'sunatResponse\'] ?? null');

    expect($jobSource)
        ->toContain('SaleDocumentStatusCache')
        ->toContain('DocumentStatus::REJECTED');
});

it('reads queued statuses through a cache-only endpoint', function (): void {
    $controllerSource = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/SaleDocumentStatusController.php');
    $routeSource = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
    $vouchersSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php',
    );

    expect($controllerSource)
        ->toContain('SaleDocumentStatusCache')
        ->not->toContain('SaleDocument::')
        ->toContain('array_slice')
        ->toContain('response()->json');

    expect($routeSource)
        ->toContain('SaleDocumentStatusController')
        ->toContain("->name('sale.statuses')");

    expect($vouchersSource)
        ->not->toContain('wire:poll')
        ->not->toContain('refreshQueuedStatuses')
        ->toContain("route('sale.statuses')")
        ->toContain('sale-document-status-updated');
});
