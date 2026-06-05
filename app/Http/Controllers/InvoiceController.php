<?php

namespace App\Http\Controllers;

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Jobs\GenerateSaleDocumentPdf as GenerateSaleDocumentPdfJob;
use App\Models\SaleDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function pdf(string $saleId, GenerateSaleDocumentPdf $generatePdf): Response
    {
        $sale = SaleDocument::query()
            ->with(['items', 'client', 'company', 'items.discounts'])
            ->findOrFail($saleId);

        $path = GenerateSaleDocumentPdf::pathFor($sale);

        if (! Storage::disk('local')->exists($path)) {
            GenerateSaleDocumentPdfJob::dispatch($sale->id);

            return response()->view('sale.pdf-pending', [
                'saleId' => $sale->id,
                'reloadUrl' => route('sale.pdf', $sale->id),
            ], 202);
        }

        $pdf = $generatePdf->handle($sale);
        $filename = sprintf('%s-%s.pdf', $sale->serie ?? 'N-A', $sale->correlative ?? 'N-A');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
