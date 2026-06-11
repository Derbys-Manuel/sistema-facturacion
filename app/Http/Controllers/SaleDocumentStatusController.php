<?php

namespace App\Http\Controllers;

use App\Services\SaleDocumentStatusCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleDocumentStatusController extends Controller
{
    public function __invoke(Request $request, SaleDocumentStatusCache $statusCache): JsonResponse
    {
        $saleIds = array_slice(
            array_values(array_unique(array_filter(
                explode(',', (string) $request->query('ids', '')),
                fn (string $saleId): bool => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                    $saleId,
                ) === 1,
            ))),
            0,
            50,
        );

        return response()->json([
            'statuses' => $statusCache->getMany($saleIds),
        ]);
    }
}
