<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SerieSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('series')->upsert([
            [
                'id' => (string) Str::uuid(),
                'document_type' => DocumentType::SALE->value,
                'description' => 'Venta',
                'code' => 'V001',
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'document_type' => DocumentType::CREDIT_NOTE->value,
                'description' => 'Nota de crédito',
                'code' => 'NC01',
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'document_type' => DocumentType::DEBIT_NOTE->value,
                'description' => 'Nota de débito',
                'code' => 'ND01',
                'correlative' => '00000001',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], ['document_type', 'description', 'correlative', 'is_active', 'updated_at']);
    }
}
