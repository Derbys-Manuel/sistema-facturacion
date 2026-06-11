<?php

namespace App\Http\Controllers;

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Models\SaleDocument;
use App\Services\CompanyCache;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function pdf(
        string $saleId,
        GenerateSaleDocumentPdf $generatePdf,
        CompanyCache $companyCache,
    ): Response {
        $sale = SaleDocument::query()
            ->with(['items', 'client', 'items.discounts'])
            ->findOrFail($saleId);

        $sale->setRelation('company', $companyCache->findOrFail((string) $sale->company_id));

        $pdf = $generatePdf->handle($sale);
        $filename = sprintf('%s-%s.pdf', $sale->serie ?? 'N-A', $sale->correlative ?? 'N-A');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
