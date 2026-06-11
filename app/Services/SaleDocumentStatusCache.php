<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use Illuminate\Support\Facades\Cache;

class SaleDocumentStatusCache
{
    private const KEY_VERSION = 'v3';

    public function put(
        string $saleId,
        DocumentStatus|string $status,
        ?array $sunatResponse = null,
    ): void {
        $statusValue = $status instanceof DocumentStatus ? $status->value : $status;

        Cache::put($this->key($saleId), [
            'status' => $statusValue,
            'sunatResponse' => $sunatResponse,
            'updatedAt' => now()->toIso8601String(),
        ], now()->addDay());
    }

    public function get(string $saleId): ?array
    {
        $value = Cache::get($this->key($saleId));

        return is_array($value) ? $value : null;
    }

    public function getMany(array $saleIds): array
    {
        $statuses = [];

        foreach ($saleIds as $saleId) {
            $status = $this->get((string) $saleId);

            if ($status !== null) {
                $statuses[(string) $saleId] = $status;
            }
        }

        return $statuses;
    }

    private function key(string $saleId): string
    {
        return 'sale-status:'.self::KEY_VERSION.":{$saleId}";
    }
}
