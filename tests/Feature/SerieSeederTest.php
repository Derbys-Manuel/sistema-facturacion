<?php

use App\Enums\Sunat\DocSunatType;
use Database\Seeders\SerieSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('it seeds default series for each company', function () {
    $companies = collect([
        (object) ['id' => '00000000-0000-0000-0000-000000000001'],
        (object) ['id' => '00000000-0000-0000-0000-000000000002'],
    ]);

    $companiesQuery = \Mockery::mock();
    $companiesQuery->shouldReceive('select')->once()->with('id')->andReturnSelf();
    $companiesQuery->shouldReceive('get')->once()->andReturn($companies);

    $seriesQuery = \Mockery::mock();
    $seriesQuery->shouldReceive('upsert')
        ->once()
        ->with(
            \Mockery::on(function (array $rows) use ($companies): bool {
                if (count($rows) !== ($companies->count() * 4)) {
                    return false;
                }

                $required = [
                    'id',
                    'company_id',
                    'doc_sunat_type',
                    'description',
                    'code',
                    'correlative',
                    'is_active',
                    'created_at',
                    'updated_at',
                ];

                foreach ($rows as $row) {
                    foreach ($required as $key) {
                        if (! array_key_exists($key, $row)) {
                            return false;
                        }
                    }

                    if (! Str::isUuid((string) $row['id'])) {
                        return false;
                    }
                }

                $byCompany = collect($rows)->groupBy('company_id');

                foreach ($companies as $i => $company) {
                    $number = $i + 1;

                    $codes = collect($byCompany->get($company->id, []))
                        ->keyBy('code');

                    $boleta = $codes->get("B00{$number}");
                    $factura = $codes->get("F00{$number}");
                    $notaCredito = $codes->get("FC0{$number}");
                    $notaDebito = $codes->get("FD0{$number}");

                    if (! $boleta || ! $factura || ! $notaCredito || ! $notaDebito) {
                        return false;
                    }

                    if ($boleta['doc_sunat_type'] !== DocSunatType::BOLETA->value || $boleta['correlative'] !== '00000002') {
                        return false;
                    }

                    if ($factura['doc_sunat_type'] !== DocSunatType::FACTURA->value || $factura['correlative'] !== '00000000') {
                        return false;
                    }

                    if ($notaCredito['doc_sunat_type'] !== DocSunatType::NOTA_CREDITO->value || $notaCredito['correlative'] !== '00000000') {
                        return false;
                    }

                    if ($notaDebito['doc_sunat_type'] !== DocSunatType::NOTA_DEBITO->value || $notaDebito['correlative'] !== '00000000') {
                        return false;
                    }
                }

                return true;
            }),
            ['code'],
            ['doc_sunat_type', 'description', 'correlative', 'is_active', 'updated_at'],
        );

    DB::shouldReceive('table')->once()->with('companies')->andReturn($companiesQuery);
    DB::shouldReceive('table')->once()->with('series')->andReturn($seriesQuery);

    (new SerieSeeder())->run();
});

