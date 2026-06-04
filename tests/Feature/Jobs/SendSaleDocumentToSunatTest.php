<?php

use App\Jobs\SendSaleDocumentToSunat;
use Illuminate\Support\Facades\Queue;

it('queues a unique sunat job for the selected sale document', function (): void {
    Queue::fake();

    SendSaleDocumentToSunat::dispatch('sale-id');

    Queue::assertPushed(
        SendSaleDocumentToSunat::class,
        fn (SendSaleDocumentToSunat $job): bool => $job->uniqueId() === 'sale-id'
            && $job->tries === 3
            && $job->timeout === 120,
    );
});
