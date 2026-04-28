<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DistrictSeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\SerieSeeder;

test('database seeder calls core seeders', function () {
    $seeder = Mockery::mock(DatabaseSeeder::class)->makePartial();

    $seeder->shouldReceive('call')
        ->once()
        ->with([
            DepartmentSeeder::class,
            ProvinceSeeder::class,
            DistrictSeeder::class,
            SerieSeeder::class,
        ]);

    $seeder->run();
});
