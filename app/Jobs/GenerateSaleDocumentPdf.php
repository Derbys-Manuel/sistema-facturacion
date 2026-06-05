<?php

namespace App\Jobs;

use App\Actions\Sales\GenerateSaleDocumentPdf as GenerateSaleDocumentPdfAction;
use App\Models\SaleDocument;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateSaleDocumentPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public array $backoff = [10, 30];

    public int $uniqueFor = 600;

    public function __construct(public string $saleId)
    {
        $this->afterCommit();
    }

    public function handle(GenerateSaleDocumentPdfAction $generateSaleDocumentPdf): void
    {
        $sale = SaleDocument::query()
            ->with(['items.discounts', 'discounts', 'client', 'company'])
            ->findOrFail($this->saleId);

        $generateSaleDocumentPdf->handle($sale);
    }

    public function uniqueId(): string
    {
        return $this->saleId;
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
