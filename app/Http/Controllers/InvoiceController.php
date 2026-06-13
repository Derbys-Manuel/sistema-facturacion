<?php

namespace App\Http\Controllers;

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Jobs\GenerateSaleDocumentPdfJob;
use App\Models\SaleDocument;
use App\Services\CompanyCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

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

        abort_unless($generatePdf->exists($sale), 404, 'El PDF todavía no está disponible.');

        $pdf = $generatePdf->get($sale);
        $filename = sprintf('%s-%s.pdf', $sale->serie ?? 'N-A', $sale->correlative ?? 'N-A');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function pdfStatus(
        string $saleId,
        GenerateSaleDocumentPdf $generatePdf,
    ): JsonResponse {
        $sale = SaleDocument::query()->findOrFail($saleId);

        if ($generatePdf->exists($sale)) {
            return response()->json([
                'status' => 'ready',
                'url' => route('sale.pdf', $sale->id),
            ]);
        }

        $error = Cache::get(GenerateSaleDocumentPdfJob::failureCacheKey((string) $sale->id));

        if (filled($error)) {
            return response()->json([
                'status' => 'failed',
                'message' => $error,
            ]);
        }

        return response()->json(['status' => 'pending']);
    }
}
