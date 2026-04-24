<?php

use App\Enums\DocumentType;
use Database\Seeders\SerieSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('it seeds default series for each document type', function () {
    $query = \Mockery::mock();

    $query->shouldReceive('upsert')
        ->once()
        ->with(
            \Mockery::on(function (array $rows): bool {
                if (count($rows) !== 3) {
                    return false;
                }

                $seriesByCode = collect($rows)->keyBy('code');

                $sale = $seriesByCode->get('V001');
                $creditNote = $seriesByCode->get('NC01');
                $debitNote = $seriesByCode->get('ND01');

                if ($sale === null || $creditNote === null || $debitNote === null) {
                    return false;
                }

                $required = ['id', 'document_type', 'description', 'code', 'correlative', 'is_active', 'created_at', 'updated_at'];

                foreach ([$sale, $creditNote, $debitNote] as $row) {
                    foreach ($required as $key) {
                        if (! array_key_exists($key, $row)) {
                            return false;
                        }
                    }

                    if (! Str::isUuid($row['id'])) {
                        return false;
                    }
                }

                return $sale['document_type'] === DocumentType::SALE->value
                    && $sale['correlative'] === '00000001'
                    && $sale['is_active'] === true
                    && $creditNote['document_type'] === DocumentType::CREDIT_NOTE->value
                    && $creditNote['correlative'] === '00000001'
                    && $creditNote['is_active'] === true
                    && $debitNote['document_type'] === DocumentType::DEBIT_NOTE->value
                    && $debitNote['correlative'] === '00000001'
                    && $debitNote['is_active'] === true;
            }),
            ['code'],
            ['document_type', 'description', 'correlative', 'is_active', 'updated_at'],
        );

    DB::shouldReceive('table')
        ->once()
        ->with('series')
        ->andReturn($query);

    (new SerieSeeder())->run();
});
