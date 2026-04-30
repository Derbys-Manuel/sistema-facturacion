<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            'Jabon de azufre',
            'Jabon de curcuman',
            'Arcilla para piel 200gr Rosa',
            'Arcilla para piel 200gr Verde',
            'Ampolla antiacne',
        ];

        foreach ($products as $name) {
            Product::query()->updateOrCreate(
                ['name' => $name],
                [
                    'unit' => 'NIU',
                    'sku' => null,
                    'price' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
