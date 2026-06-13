<?php

namespace App\Jobs;

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Models\SaleDocument;
use App\Services\CompanyCache;
use App\Services\SaleDocumentPdfSnapshot;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateSaleDocumentPdfJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public array $backoff = [15, 60];

    public int $uniqueFor = 300;

    public function __construct(
        public string $saleId,
        public ?string $snapshotPath = null,
    ) {
        Cache::forget(self::failureCacheKey($this->saleId));
        $this->afterCommit();
    }

    public static function failureCacheKey(string $saleId): string
    {
        return "sale-document-pdf:{$saleId}:failed";
    }

    public function handle(
        GenerateSaleDocumentPdf $generatePdf,
        CompanyCache $companyCache,
        SaleDocumentPdfSnapshot $pdfSnapshot,
    ): void {
        Cache::forget(self::failureCacheKey($this->saleId));

        if (filled($this->snapshotPath) && Storage::disk('local')->exists($this->snapshotPath)) {
            $snapshot = $pdfSnapshot->get($this->snapshotPath);
            $generatePdf->handleSnapshot($snapshot);

            return;
        }

        $sale = SaleDocument::query()
            ->with(['items', 'client', 'items.discounts'])
            ->findOrFail($this->saleId);

        $sale->setRelation('company', $companyCache->findOrFail((string) $sale->company_id));

        $generatePdf->handle($sale);
    }

    public function uniqueId(): string
    {
        return $this->saleId;
    }

    public function failed(?Throwable $exception): void
    {
        Cache::put(
            self::failureCacheKey($this->saleId),
            $exception?->getMessage() ?? 'No se pudo generar el PDF.',
            now()->addMinutes(10),
        );
    }
}
