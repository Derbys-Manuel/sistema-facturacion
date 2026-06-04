<?php

namespace App\Actions\Sales;

use App\Models\SaleDocument;
use App\Services\SunatService;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class GenerateSaleDocumentPdf
{
    private const RENDER_VERSION = '2';

    public function __construct(private SunatService $sunatService) {}

    public function handle(SaleDocument $sale): string
    {
        $fingerprint = hash('sha256', implode('|', [
            self::RENDER_VERSION,
            $sale->id,
            $sale->updated_at?->toISOString(),
            $sale->hash,
        ]));
        $path = "sale-documents/{$sale->id}/{$fingerprint}.pdf";

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->get($path);
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
}
