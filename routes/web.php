<?php

use Illuminate\Support\Facades\Route;
use App\Models\SaleDocument;
use App\Services\SunatService;

Route::redirect('/', '/dashboard')->name('home');

Route::view('dashboard', 'dashboard')->name('dashboard');

Route::livewire('/boleta', 'pages::sale.create-boleta')->name('create-boleta');
Route::livewire('/factura', 'pages::sale.create-factura')->name('create-factura');
Route::livewire('/comprobantes', 'pages::sale.vouchers')->name('vouchers');

Route::get('/comprobantes/{sale}/pdf', function (SaleDocument $sale, SunatService $sunatService) {
    $sale->loadMissing(['company', 'client', 'items']);

    $data = [
        'ublVersion' => $sale->ubl_version ?? '2.1',
        'operationType' => $sale->operation_type?->value,
        'docSunatType' => $sale->doc_sunat_type?->value,
        'serie' => (string) ($sale->serie ?? ''),
        'correlative' => (string) ($sale->correlative ?? ''),
        'dateIssue' => optional($sale->date_issue)->format('Y-m-d H:i:s'),
        'currency' => (string) ($sale->currency ?? 'PEN'),

        'totalTaxed' => (float) ($sale->total_taxed ?? 0),
        'totalExempted' => (float) ($sale->total_exempted ?? 0),
        'totalUnaffected' => (float) ($sale->total_unaffected ?? 0),
        'totalExport' => (float) ($sale->total_export ?? 0),
        'totalFree' => (float) ($sale->total_free ?? 0),
        'totalIgv' => (float) ($sale->total_igv ?? 0),
        'totalIgvFree' => (float) ($sale->total_igv_free ?? 0),
        'icbper' => (float) ($sale->icbper ?? 0),
        'totalTaxes' => (float) ($sale->total_taxes ?? 0),
        'saleValue' => (float) ($sale->sale_value ?? 0),
        'subTotal' => (float) ($sale->sub_total ?? 0),
        'rounding' => (float) ($sale->rounding ?? 0),
        'total' => (float) ($sale->total ?? 0),

        'items' => $sale->items
            ->map(fn ($item) => [
                'code' => (string) ($item->code ?? ''),
                'unit' => (string) ($item->unit ?? 'NIU'),
                'quantity' => (float) ($item->quantity ?? 0),
                'description' => (string) ($item->description ?? ''),
                'unitValue' => (float) ($item->unit_value ?? 0),
                'itemValue' => (float) ($item->item_value ?? 0),
                'unitPrice' => (float) ($item->unit_price ?? 0),
                'igvBaseAmount' => (float) ($item->igv_base_amount ?? 0),
                'igvPercent' => (float) ($item->igv_percent ?? 0),
                'igvAmount' => (float) ($item->igv_amount ?? 0),
                'igvAffectationType' => $item->igv_affectation_type?->value,
                'icbperFactor' => (float) ($item->icbper_factor ?? 0),
                'icbperAmount' => (float) ($item->icbper_amount ?? 0),
                'taxesTotal' => (float) ($item->taxes_total ?? 0),
            ])
            ->values()
            ->all(),
    ];

    $sunatService->setLegends($data);
    $invoice = $sunatService->getInvoice($data, $sale);
    $pdf = $sunatService->generatePdfReport($invoice, company: $sale->company, hash: $sale->hash);

    $filename = sprintf('%s-%s.pdf', $sale->serie ?? 'N-A', $sale->correlative ?? 'N-A');

    return response($pdf, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
})->name('sale.pdf');
