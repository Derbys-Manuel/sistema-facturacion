<?php

use App\Enums\Sunat\DocSunatType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $companyMap = [
            '20615778207' => 1, // NEXARA => 01
            '20615778206' => 2, // ANONIMA => 02
        ];

        $companies = DB::table('companies')
            ->select('id', 'ruc')
            ->whereIn('ruc', array_keys($companyMap))
            ->get();

        foreach ($companies as $company) {
            $ruc = (string) $company->ruc;

            if (! isset($companyMap[$ruc])) {
                continue;
            }

            $number = $companyMap[$ruc];
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            // FC{suffix}: Nota crédito de factura
            $this->upsertSerieByCode(
                code: "FC{$suffix}",
                now: $now,
                values: [
                    'company_id' => $company->id,
                    'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
                    'affected_doc_sunat_type' => DocSunatType::FACTURA->value,
                    'description' => 'Nota de crédito - Factura',
                ],
            );

            // BC{suffix}: Nota crédito de boleta
            $this->upsertSerieByCode(
                code: "BC{$suffix}",
                now: $now,
                values: [
                    'company_id' => $company->id,
                    'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
                    'affected_doc_sunat_type' => DocSunatType::BOLETA->value,
                    'description' => 'Nota de crédito - Boleta',
                    'correlative' => '00000000',
                    'is_active' => true,
                ],
            );

            // FD{suffix}: Nota débito de factura
            $this->upsertSerieByCode(
                code: "FD{$suffix}",
                now: $now,
                values: [
                    'company_id' => $company->id,
                    'doc_sunat_type' => DocSunatType::NOTA_DEBITO->value,
                    'affected_doc_sunat_type' => DocSunatType::FACTURA->value,
                    'description' => 'Nota de débito - Factura',
                ],
            );

            // BD{suffix}: Nota débito de boleta
            $this->upsertSerieByCode(
                code: "BD{$suffix}",
                now: $now,
                values: [
                    'company_id' => $company->id,
                    'doc_sunat_type' => DocSunatType::NOTA_DEBITO->value,
                    'affected_doc_sunat_type' => DocSunatType::BOLETA->value,
                    'description' => 'Nota de débito - Boleta',
                    'correlative' => '00000000',
                    'is_active' => true,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('series')
            ->whereIn('code', ['BC01', 'BD01', 'BC02', 'BD02'])
            ->delete();

        DB::table('series')
            ->whereIn('code', ['FC01', 'FC02'])
            ->update([
                'description' => 'Nota de crédito',
                'affected_doc_sunat_type' => null,
            ]);

        DB::table('series')
            ->whereIn('code', ['FD01', 'FD02'])
            ->update([
                'description' => 'Nota de débito',
                'affected_doc_sunat_type' => null,
            ]);
    }

    private function upsertSerieByCode(string $code, $now, array $values): void
    {
        $existing = DB::table('series')
            ->select('id')
            ->where('code', $code)
            ->first();

        if ($existing) {
            DB::table('series')
                ->where('code', $code)
                ->update(array_merge($values, [
                    'updated_at' => $now,
                ]));

            return;
        }

        DB::table('series')->insert(array_merge([
            'id' => (string) Str::uuid(),
            'code' => $code,
            'created_at' => $now,
            'updated_at' => $now,
        ], $values));
    }
};
