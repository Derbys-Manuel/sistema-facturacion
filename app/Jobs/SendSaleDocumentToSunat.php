<?php

namespace App\Jobs;

use App\Actions\Sales\SendSaleDocumentToSunatAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
}
