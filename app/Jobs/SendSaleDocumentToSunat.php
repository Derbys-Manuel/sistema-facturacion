<?php

namespace App\Jobs;

use App\Actions\Sales\SendSaleDocumentToSunatAction;
use App\Enums\DocumentStatus;
use App\Models\SaleDocument;
use App\Services\SaleDocumentStatusCache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSaleDocumentToSunat implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 120, 300];

    public int $uniqueFor = 600;

    public function __construct(public string $saleId)
    {
        $this->afterCommit();
    }

    public function handle(SendSaleDocumentToSunatAction $sendSaleDocumentToSunat): void
    {
        $sendSaleDocumentToSunat->handle($this->saleId);
    }

    public function uniqueId(): string
    {
        return $this->saleId;
    }

    public function failed(?Throwable $exception): void
    {
        $sunatResponse = [
            'success' => false,
            'error' => [
                'code' => 'QUEUE_FAILED',
                'message' => $exception?->getMessage() ?? 'No se pudo procesar el envio a SUNAT.',
            ],
        ];

        SaleDocument::query()
            ->whereKey($this->saleId)
            ->where('status', DocumentStatus::WAITING->value)
            ->update([
                'status' => DocumentStatus::REJECTED->value,
                'cdr' => $sunatResponse,
            ]);

        app(SaleDocumentStatusCache::class)->put(
            $this->saleId,
            DocumentStatus::REJECTED,
            $sunatResponse,
        );
    }
}
