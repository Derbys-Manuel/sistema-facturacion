<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->create([
            'company_name' => 'NEXARA CORP SOCIEDAD ANONIMA CERRADA',
            'ruc' => '20615778207',
            'urbanization' => '-',
            'address' => 'URB. SANTA MARIA DEL PINAR 4 ETAPA MZ B LOTE 04',
            'cod_local' => '0000',
            'sol_user' => config('services.sunat-nexara.sol_user'),
            'sol_pass' => config('services.sunat-nexara.sol_pass'),
            'cert_path' => 'app/private/certificado.pem',
            'logo_path' => 'logos/eunoia.png',
            'production' => true,
            'ubigueo' => '200101',
            'department' => 'PIURA',
            'province' => 'PIURA',
            'district' => 'PIURA',
        ]);
        Company::query()->create([
            'company_name' => 'ANONIMA CERRADA',
            'ruc' => '20615778206',
            'urbanization' => '-',
            'address' => 'URB. SANTA MARIA',
            'cod_local' => '0000',
            'sol_user' => 'USUARIO-PRUEBA',
            'sol_pass' => 'usuario-prueba',
            'cert_path' => 'app/private/certificado-prueba.pem',
            'logo_path' => 'logos/eunoia.png',
            'production' => false,
            'ubigueo' => '200101',
            'department' => 'PIURA',
            'province' => 'PIURA',
            'district' => 'PIURA',
        ]);
    }
}

