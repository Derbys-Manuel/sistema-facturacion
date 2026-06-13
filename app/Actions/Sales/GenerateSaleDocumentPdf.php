<?php

namespace App\Actions\Sales;

use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Services\SunatService;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class GenerateSaleDocumentPdf
{
    private const RENDER_VERSION = '2';

    public function __construct(private SunatService $sunatService) {}

    public static function pathFor(SaleDocument $sale): string
    {
        $fingerprint = hash('sha256', implode('|', [
            self::RENDER_VERSION,
            $sale->id,
            $sale->updated_at?->toISOString(),
            $sale->hash,
        ]));

        return "sale-documents/{$sale->id}/{$fingerprint}.pdf";
    }

    public function exists(SaleDocument $sale): bool
    {
        return Storage::disk('local')->exists(self::pathFor($sale));
    }

    public function get(SaleDocument $sale): string
    {
        return Storage::disk('local')->get(self::pathFor($sale));
    }

    public function handle(SaleDocument $sale): string
    {
        $path = self::pathFor($sale);

        if ($this->exists($sale)) {
            return $this->get($sale);
        }

        $data = $sale->toArray();
        $rawDateIssue = $sale->getRawOriginal('date_issue');

        if ($rawDateIssue instanceof DateTimeInterface || (is_string($rawDateIssue) && $rawDateIssue !== '')) {
            $localDateIssue = $rawDateIssue instanceof DateTimeInterface
                ? $rawDateIssue->format('Y-m-d H:i:s')
                : str_replace('T', ' ', substr($rawDateIssue, 0, 19));

            $data['dateIssue'] = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $localDateIssue,
                'America/Lima',
            )->toIso8601String();
        }

        $this->sunatService->setLegends($data);
        $document = $this->sunatService->getDocument($data, $sale);
        $pdf = $this->sunatService->generatePdfReport($document, company: $sale->company, hash: $sale->hash);

        Storage::disk('local')->put($path, $pdf);

        return $pdf;
    }

    public function handleSnapshot(array $snapshot): string
    {
        $sale = $this->saleFromSnapshot($snapshot);

        $path = self::pathFor($sale);

        if ($this->exists($sale)) {
            return $this->get($sale);
        }

        $data = $snapshot['data'];
        $this->sunatService->setLegends($data);
        $document = $this->sunatService->getDocument($data, $sale);
        $pdf = $this->sunatService->generatePdfReport($document, company: $sale->company, hash: $sale->hash);

        Storage::disk('local')->put($path, $pdf);

        return $pdf;
    }

    public function saleFromSnapshot(array $snapshot): SaleDocument
    {
        $sale = (new SaleDocument)->newFromBuilder($snapshot['sale']);
        $sale->setRelation('company', (new Company)->newFromBuilder($snapshot['company']));
        $sale->setRelation(
            'client',
            is_array($snapshot['client'] ?? null)
                ? (new Client)->newFromBuilder($snapshot['client'])
                : null,
        );

        return $sale;
    }
}
