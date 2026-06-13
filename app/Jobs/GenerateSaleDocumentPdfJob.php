<?php

namespace App\Jobs;

use App\Actions\Sales\GenerateSaleDocumentPdf;
use App\Models\SaleDocument;
use App\Services\CompanyCache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateSaleDocumentPdfJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public array $backoff = [15, 60];

    public int $uniqueFor = 300;

    public function __construct(public string $saleId)
    {
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
    ): void {
        Cache::forget(self::failureCacheKey($this->saleId));

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
