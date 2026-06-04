<?php

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Services\SunatService;
use Greenter\Model\Sale\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('preserves the Lima issue time when generating the pdf', function (): void {
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
        ->withArgs(fn (array $data, SaleDocument $document): bool => $document === $sale
            && $data['dateIssue'] === '2026-06-04T16:04:00-05:00')
        ->andReturn(new Invoice);
    $sunat->shouldReceive('generatePdfReport')->once()->andReturn('pdf');

    expect((new GenerateSaleDocumentPdf($sunat))->handle($sale))->toBe('pdf');
});

it('includes the render version in the pdf cache fingerprint', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Actions/Sales/GenerateSaleDocumentPdf.php');

    expect($source)
        ->toContain("private const RENDER_VERSION = '2';")
        ->toContain('self::RENDER_VERSION,');
});
