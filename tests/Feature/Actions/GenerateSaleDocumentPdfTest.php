<?php

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Services\SunatService;
use Greenter\Model\Sale\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it('reuses the cached pdf while the sale document is unchanged', function (): void {
    Storage::fake('local');

    $sale = new SaleDocument([
        'id' => 'sale-id',
        'hash' => 'document-hash',
        'date_issue' => '2026-06-04 16:04:00',
    ]);
    $sale->syncOriginal();
    $sale->updated_at = Carbon::parse('2026-06-04 20:00:00');
    $sale->setRelation('company', new Company);
    $sale->setRelation('items', collect());

    $sunat = Mockery::mock(SunatService::class);
    $sunat->shouldReceive('setLegends')->once();
    $sunat->shouldReceive('getDocument')
        ->once()
        ->withArgs(function (array $data, SaleDocument $document) use ($sale): bool {
            return $document === $sale
                && $data['dateIssue'] === '2026-06-04T16:04:00-05:00';
        })
        ->andReturn(new Invoice);
    $sunat->shouldReceive('generatePdfReport')->once()->andReturn('cached-pdf');

    $action = new GenerateSaleDocumentPdf($sunat);

    expect($action->handle($sale))
        ->toBe('cached-pdf')
        ->and($action->handle($sale))
        ->toBe('cached-pdf');
});
