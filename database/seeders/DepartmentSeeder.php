<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $ubigeo_peru_departments = [
            ['code' => '01', 'name' => 'Amazonas'],
            ['code' => '02', 'name' => 'Áncash'],
            ['code' => '03', 'name' => 'Apurímac'],
            ['code' => '04', 'name' => 'Arequipa'],
            ['code' => '05', 'name' => 'Ayacucho'],
            ['code' => '06', 'name' => 'Cajamarca'],
            ['code' => '07', 'name' => 'Callao'],
            ['code' => '08', 'name' => 'Cusco'],
            ['code' => '09', 'name' => 'Huancavelica'],
            ['code' => '10', 'name' => 'Huánuco'],
            ['code' => '11', 'name' => 'Ica'],
            ['code' => '12', 'name' => 'Junín'],
            ['code' => '13', 'name' => 'La Libertad'],
            ['code' => '14', 'name' => 'Lambayeque'],
            ['code' => '15', 'name' => 'Lima'],
            ['code' => '16', 'name' => 'Loreto'],
            ['code' => '17', 'name' => 'Madre de Dios'],
            ['code' => '18', 'name' => 'Moquegua'],
            ['code' => '19', 'name' => 'Pasco'],
            ['code' => '20', 'name' => 'Piura'],
            ['code' => '21', 'name' => 'Puno'],
            ['code' => '22', 'name' => 'San Martín'],
            ['code' => '23', 'name' => 'Tacna'],
            ['code' => '24', 'name' => 'Tumbes'],
            ['code' => '25', 'name' => 'Ucayali'],
        ];

        $now = now();

        $rows = collect($ubigeo_peru_departments)->map(fn (array $department): array => [
            'id' => (string) Str::uuid(),
            'code' => $department['code'],
            'description' => $this->cleanText($department['name']),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('departments')->upsert($rows, ['code'], ['description', 'is_active', 'updated_at']);
    }

    private function cleanText(string $value): string
    {
        if (! str_contains($value, 'Ã') && ! str_contains($value, 'Â')) {
            return $value;
        }

        $fixed = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);

        return $fixed === false ? $value : $fixed;
    }
}
