<?php

namespace App\Services;

use App\Models\Serie;

class SerieService
{
    public function getSerieForUpdate(string $docSunatType, string $companyId): Serie
    {
        return Serie::query()
            ->where('doc_sunat_type', $docSunatType)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();
    }
    public function nextCorrelative(string $current): string
    {
        $number = (int) $current;
        if ($number >= 999999999) {
            throw new \Exception('La serie llegó al correlativo máximo permitido.');
        }
        return str_pad((string) ($number + 1), 8, '0', STR_PAD_LEFT);
    }
}