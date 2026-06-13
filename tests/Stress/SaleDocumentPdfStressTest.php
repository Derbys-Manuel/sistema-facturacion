<?php

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Enums\Sunat\DocSunatType;
use App\Services\SunatService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('generates synthetic pdfs without changing their source data', function (): void {
    $count = max(1, (int) getenv('PDF_STRESS_COUNT') ?: 10);
    $offset = max(0, (int) getenv('PDF_STRESS_OFFSET'));
    $sunat = app(SunatService::class);
    $generator = app(GenerateSaleDocumentPdf::class);
    $startedAt = microtime(true);

    for ($iteration = 1; $iteration <= $count; $iteration++) {
        $index = $offset + $iteration;
        $snapshot = syntheticPdfSnapshot($index);
        $sale = $generator->saleFromSnapshot($snapshot);
        $data = $snapshot['data'];
        $sunat->setLegends($data);
        $document = $sunat->getDocument($data, $sale);

        expect($document->getSerie())->toBe($data['serie'])
            ->and($document->getCorrelativo())->toBe($data['correlative'])
            ->and($document->getMtoImpVenta())->toBe((float) $data['total'])
            ->and($document->getCompany()->getRuc())->toBe($snapshot['company']['ruc'])
            ->and($document->getClient()->getNumDoc())->toBe($snapshot['client']['document_number'])
            ->and($document->getDetails())->toHaveCount(count($data['items']));

        foreach ($document->getDetails() as $itemIndex => $detail) {
            $source = $data['items'][$itemIndex];

            expect($detail->getCodProducto())->toBe($source['code'])
                ->and($detail->getCantidad())->toBe((float) $source['quantity'])
                ->and($detail->getMtoValorVenta())->toBe((float) $source['itemValue'])
                ->and($detail->getIgv())->toBe((float) $source['igvAmount']);
        }

        $pdf = $generator->handleSnapshot($snapshot);

        expect(str_starts_with($pdf, '%PDF-'))->toBeTrue()
            ->and(strlen($pdf))->toBeGreaterThan(1_000);

        Storage::disk('local')->delete(GenerateSaleDocumentPdf::pathFor($sale));
    }

    fwrite(STDERR, sprintf(
        "\nGenerated %d PDFs in %.2f seconds (%.3f seconds/PDF).\n",
        $count,
        microtime(true) - $startedAt,
        (microtime(true) - $startedAt) / $count,
    ));
});

function syntheticPdfSnapshot(int $index): array
{
    $isFactura = $index % 2 === 0;
    $quantity = (float) (($index % 5) + 1);
    $unitValue = (float) (10 + ($index % 40));
    $itemValue = round($quantity * $unitValue, 2);
    $igv = round($itemValue * 0.18, 2);
    $total = round($itemValue + $igv, 2);
    $id = sprintf('00000000-0000-7000-8000-%012d', $index);
    $date = sprintf('2026-06-%02d 10:%02d:00', (($index - 1) % 28) + 1, $index % 60);

    return [
        'version' => 1,
        'sale' => [
            'id' => $id,
            'hash' => "hash-{$index}",
            'company_id' => 'company-id',
            'client_id' => 'client-id',
            'date_issue' => $date,
            'created_at' => $date,
            'updated_at' => $date,
        ],
        'data' => [
            'ublVersion' => '2.1',
            'docSunatType' => $isFactura ? DocSunatType::FACTURA->value : DocSunatType::BOLETA->value,
            'operationType' => '0101',
            'currency' => 'PEN',
            'serie' => ($isFactura ? 'F' : 'B').sprintf('%03d', ($index % 999) + 1),
            'correlative' => sprintf('%08d', $index),
            'dateIssue' => str_replace(' ', 'T', $date).'-05:00',
            'totalTaxed' => $itemValue,
            'totalExempted' => 0,
            'totalUnaffected' => 0,
            'totalExport' => 0,
            'totalFree' => 0,
            'totalIgv' => $igv,
            'totalIgvFree' => 0,
            'icbper' => 0,
            'totalTaxes' => $igv,
            'saleValue' => $itemValue,
            'subTotal' => $total,
            'rounding' => 0,
            'total' => $total,
            'additionalInfo' => "Caso sintetico {$index}",
            'discounts' => [],
            'items' => [[
                'code' => sprintf('SKU-%05d', $index),
                'unit' => 'NIU',
                'quantity' => $quantity,
                'description' => "Producto sintetico {$index}",
                'unitValue' => $unitValue,
                'itemValue' => $itemValue,
                'unitPrice' => round($unitValue * 1.18, 2),
                'igvBaseAmount' => $itemValue,
                'igvPercent' => 18,
                'igvAmount' => $igv,
                'igvAffectationType' => '10',
                'icbperFactor' => 0,
                'icbperAmount' => 0,
                'taxesTotal' => $igv,
                'discounts' => [],
            ]],
        ],
        'company' => [
            'id' => 'company-id',
            'company_name' => 'Empresa Sintetica SAC',
            'ruc' => '20123456789',
            'address' => 'Av. Pruebas 123',
            'department' => 'LIMA',
            'province' => 'LIMA',
            'district' => 'LIMA',
            'cod_local' => '0000',
            'production' => false,
        ],
        'client' => [
            'id' => 'client-id',
            'name' => "Cliente Sintetico {$index}",
            'trade_name' => null,
            'doc_identity_type' => $isFactura ? '6' : '1',
            'document_number' => $isFactura ? '20612345678' : sprintf('%08d', $index % 100_000_000),
            'address' => 'Jr. Cliente 456',
            'telephone' => null,
            'is_active' => true,
        ],
    ];
}
