<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SerieSeeder;

test('database seeder calls core seeders', function () {
    $seeder = Mockery::mock(DatabaseSeeder::class)->makePartial();

    $seeder->shouldReceive('call')
        ->once()
        ->with([
            CompanySeeder::class,
            SerieSeeder::class,
            ProductSeeder::class,
        ]);

    $seeder->run();
});
