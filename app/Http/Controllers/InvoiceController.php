<?php

namespace App\Http\Controllers;

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Models\SaleDocument;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function pdf(string $saleId, GenerateSaleDocumentPdf $generatePdf): Response
    {
        $sale = SaleDocument::query()
            ->with(['items', 'client', 'company', 'items.discounts'])
            ->findOrFail($saleId);

        $pdf = $generatePdf->handle($sale);
        $filename = sprintf('%s-%s.pdf', $sale->serie ?? 'N-A', $sale->correlative ?? 'N-A');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
