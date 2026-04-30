<?php

namespace App\Http\Controllers;

use App\Models\SaleDocument;
use App\Services\SunatService;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function pdf(string $saleId, SunatService $sunatService): Response
    {
        $sale = SaleDocument::query()
            ->with(['items', 'client', 'company'])
            ->findOrFail($saleId);

        $data = $sale->toArray();
        $sunatService->setLegends($data);
        $invoice = $sunatService->getInvoice($data, $sale);
        $pdf = $sunatService->generatePdfReport($invoice, company: $sale->company, hash: $sale->hash);
        $filename = sprintf('%s-%s.pdf', $sale->serie ?? 'N-A', $sale->correlative ?? 'N-A');
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
