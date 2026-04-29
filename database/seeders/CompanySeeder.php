<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->create([
            'company_name' => 'Eunoia S.A.C.',
            'ruc' => '20609278235',
            'urbanization' => 'Urb. Miraflores Country Club',
            'address' => 'Av. Andrés Avelino Cáceres 221',
            'cod_local' => '0000',
            'sol_user' => 'MODDATOS',
            'sol_pass' => 'moddatos',
            'cert_path' => 'app/private/certificado-prueba.pem',
            'logo_path' => 'logos/eunoia.png',
            'production' => false,
            'ubigueo' => '200101',
            'department' => 'PIURA',
            'province' => 'PIURA',
            'district' => 'PIURA',
        ]);

        Company::query()->create([
            'company_name' => 'Virale Perú S.A.C.',
            'ruc' => '20610547896',
            'urbanization' => 'Urb. Santa Isabel',
            'address' => 'Mz A Lt 18 Calle Comercio',
            'cod_local' => '0001',
            'sol_user' => 'MODDATOS',
            'sol_pass' => 'moddatos',
            'cert_path' => 'app/private/certificado-prueba.pem',
            'logo_path' => 'logos/virale.png',
            'production' => false,
            'ubigueo' => '150101',
            'department' => 'LIMA',
            'province' => 'LIMA',
            'district' => 'LIMA',
        ]);

        Company::query()->create([
            'company_name' => 'Altiori Naturals S.A.C.',
            'ruc' => '20611856327',
            'urbanization' => 'Urb. Los Ejidos',
            'address' => 'Av. Los Algarrobos 455',
            'cod_local' => '0002',
            'sol_user' => 'MODDATOS',
            'sol_pass' => 'moddatos',
            'cert_path' => 'app/private/certificado-prueba.pem',
            'logo_path' => 'logos/altiori.png',
            'production' => false,
            'ubigueo' => '200101',
            'department' => 'PIURA',
            'province' => 'PIURA',
            'district' => 'PIURA',
        ]);
    }
}

