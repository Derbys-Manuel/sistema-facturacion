<?php

use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DistrictSeeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('department seeder upserts departments', function () {
    $query = \Mockery::mock();

    $query->shouldReceive('upsert')
        ->once()
        ->with(
            \Mockery::on(function (array $rows): bool {
                $departmentsByCode = collect($rows)->keyBy('code');

                $amazonas = $departmentsByCode->get('01');
                $ancash = $departmentsByCode->get('02');

                if ($amazonas === null || $ancash === null) {
                    return false;
                }

                return Str::isUuid($amazonas['id'])
                    && $amazonas['description'] === 'Amazonas'
                    && $amazonas['is_active'] === true
                    && array_key_exists('created_at', $amazonas)
                    && array_key_exists('updated_at', $amazonas)
                    && Str::isUuid($ancash['id'])
                    && $ancash['description'] === 'Áncash';
            }),
            ['code'],
            ['description', 'is_active', 'updated_at'],
        );

    DB::shouldReceive('table')
        ->once()
        ->with('departments')
        ->andReturn($query);

    (new DepartmentSeeder())->run();
});

test('province seeder upserts provinces and links to departments', function () {
    $departmentsQuery = \Mockery::mock();
    $query = \Mockery::mock();

    $departmentsQuery->shouldReceive('pluck')
        ->once()
        ->with('id', 'code')
        ->andReturn(collect([
            '01' => '00000000-0000-0000-0000-000000000001',
            '18' => '00000000-0000-0000-0000-000000000018',
        ]));

    $query->shouldReceive('upsert')
        ->once()
        ->with(
            \Mockery::on(function (array $rows): bool {
                $provincesByCode = collect($rows)->keyBy('code');

                $chachapoyas = $provincesByCode->get('0101');
                $generalSanchezCerro = $provincesByCode->get('1802');

                if ($chachapoyas === null || $generalSanchezCerro === null) {
                    return false;
                }

                return Str::isUuid($chachapoyas['id'])
                    && $chachapoyas['description'] === 'Chachapoyas'
                    && $chachapoyas['department_id'] === '00000000-0000-0000-0000-000000000001'
                    && $chachapoyas['is_active'] === true
                    && Str::isUuid($generalSanchezCerro['id'])
                    && $generalSanchezCerro['description'] === 'General Sánchez Cerro'
                    && $generalSanchezCerro['department_id'] === '00000000-0000-0000-0000-000000000018';
            }),
            ['code'],
            ['description', 'department_id', 'is_active', 'updated_at'],
        );

    DB::shouldReceive('table')
        ->once()
        ->with('departments')
        ->andReturn($departmentsQuery);

    DB::shouldReceive('table')
        ->once()
        ->with('provinces')
        ->andReturn($query);

    (new ProvinceSeeder())->run();
});

test('district seeder upserts districts and links to provinces', function () {
    $provincesQuery = \Mockery::mock();
    $query = \Mockery::mock();

    $provincesQuery->shouldReceive('pluck')
        ->once()
        ->with('id', 'code')
        ->andReturn(collect([
            '0101' => '00000000-0000-0000-0000-000000000101',
            '2304' => '00000000-0000-0000-0000-000000002304',
        ]));

    $query->shouldReceive('upsert')
        ->once()
        ->with(
            \Mockery::on(function (array $rows): bool {
                $districtsByCode = collect($rows)->keyBy('code');

                $asuncion = $districtsByCode->get('010102');
                $heroesAlbarracin = $districtsByCode->get('230402');

                if ($asuncion === null || $heroesAlbarracin === null) {
                    return false;
                }

                return Str::isUuid($asuncion['id'])
                    && $asuncion['description'] === 'Asunción'
                    && $asuncion['province_id'] === '00000000-0000-0000-0000-000000000101'
                    && $asuncion['is_active'] === true
                    && Str::isUuid($heroesAlbarracin['id'])
                    && $heroesAlbarracin['description'] === 'Héroes Albarracín'
                    && $heroesAlbarracin['province_id'] === '00000000-0000-0000-0000-000000002304';
            }),
            ['code'],
            ['description', 'province_id', 'is_active', 'updated_at'],
        );

    DB::shouldReceive('table')
        ->once()
        ->with('provinces')
        ->andReturn($provincesQuery);

    DB::shouldReceive('table')
        ->once()
        ->with('districts')
        ->andReturn($query);

    (new DistrictSeeder())->run();
});
