<?php

namespace App\Actions\Sales;

use App\Enums\DocumentStatus;
use App\Enums\Sunat\DocSunatType;
use App\Models\SaleDocument;
use App\Services\SaleService;
use App\Services\SunatService;
use RuntimeException;

class SendSaleDocumentToSunatAction
{
    public function __construct(
        private SunatService $sunatService,
        private SaleService $saleService,
    ) {}

    public function handle(string $saleId): array
    {
        $sale = SaleDocument::query()
            ->with(['items', 'client', 'company', 'discounts', 'items.discounts'])
            ->findOrFail($saleId);

        if ($sale->status === DocumentStatus::APPROVED) {
            return ['sunat' => ['success' => true, 'alreadyApproved' => true]];
        }

        if (! in_array($sale->status, [DocumentStatus::DRAFT, DocumentStatus::REJECTED, DocumentStatus::WAITING], true)) {
            return ['sunat' => ['success' => false, 'skipped' => true]];
        }

        $data = $sale->toArray();
        $data['discounts'] = $this->activeDiscounts($data['discounts'] ?? []);
        $data['items'] = collect($this->saleService->hydrateItemsForSunatFromDatabase($data['items'] ?? []))
            ->map(function ($item) {
                if (is_array($item)) {
                    $item['discounts'] = $this->activeDiscounts($item['discounts'] ?? []);
                }

                return $item;
            })
            ->values()
            ->all();

        $this->validateCreditNoteTotal($data);

        $response = $this->sunatService->send($data, $sale);
        $sunatSuccess = $response['sunatResponse']['success'] ?? false;

        if (($response['sunatResponse']['error']['code'] ?? null) === 'CONNECTION_ERROR') {
            throw new RuntimeException(
                $response['sunatResponse']['error']['message'] ?? 'No se pudo conectar con SUNAT.',
            );
        }

        $sale->update([
            'xml' => $response['xml'] ?? null,
            'hash' => $response['hash'] ?? null,
            'cdr' => $response['sunatResponse'] ?? null,
            'status' => $sunatSuccess
                ? DocumentStatus::APPROVED->value
                : DocumentStatus::REJECTED->value,
        ]);

        return ['sunat' => $response];
    }

    private function activeDiscounts(array $discounts): array
    {
        return collect($discounts)
            ->filter(fn ($discount) => (float) ($discount['discountAmount'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function validateCreditNoteTotal(array $data): void
    {
        if (($data['docSunatType'] ?? null) !== DocSunatType::NOTA_CREDITO->value) {
            return;
        }

        $affectedSaleDocumentId = (string) ($data['affectedSaleDocumentId'] ?? '');
        $affected = filled($affectedSaleDocumentId)
            ? SaleDocument::query()->find($affectedSaleDocumentId)
            : null;

        if ($affected && round((float) ($data['total'] ?? 0), 2) > round((float) $affected->total, 2)) {
            throw new RuntimeException('La nota de crédito no puede exceder el total del comprobante afectado.');
        }
    }
}
