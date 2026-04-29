<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\Sunat\DocSunatType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SerieSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $companies = DB::table('companies')->select('id')->get();

        $series = [];

        for ($i = 0; $i < $companies->count(); $i++) {
            $number = $i + 1;
            // Boleta
            $series[] = [
                'id' => (string) Str::uuid(),
                'company_id' => $companies[$i]->id,
                'doc_sunat_type' => DocSunatType::BOLETA->value,
                'description' => 'Boleta',
                'code' => "B00$number",
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Factura
            $series[] = [
                'id' => (string) Str::uuid(),
                'company_id' => $companies[$i]->id,
                'doc_sunat_type' => DocSunatType::FACTURA->value,
                'description' => 'Factura',
                'code' => "F00$number",
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Nota de crédito
            $series[] = [
                'id' => (string) Str::uuid(),
                'company_id' => $companies[$i]->id,
                'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
                'description' => 'Nota de crédito',
                'code' => "FC0$number",
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Nota de débito
            $series[] = [
                'id' => (string) Str::uuid(),
                'company_id' => $companies[$i]->id,
                'doc_sunat_type' => DocSunatType::NOTA_DEBITO->value,
                'description' => 'Nota de débito',
                'code' => "FD0$number",
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('series')->upsert(
            $series,
            ['code'],
            [
                'doc_sunat_type',
                'description',
                'correlative',
                'is_active',
                'updated_at',
            ]
        );
    }
}