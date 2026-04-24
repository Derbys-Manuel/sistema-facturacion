<?php

use Illuminate\Support\Str;

test('migrations with constrained foreign keys run after referenced tables', function () {
    $migrationFiles = collect(glob(base_path('database/migrations/*.php')))
        ->sort()
        ->values();

    $tableCreatedAt = $migrationFiles
        ->mapWithKeys(function (string $path): array {
            $filename = basename($path);
            $content = file_get_contents($path);

            preg_match("/Schema::create\\('([^']+)'/", $content, $matches);

            if (! isset($matches[1])) {
                return [];
            }

            $table = $matches[1];
            $timestamp = Str::beforeLast($filename, '_create_');

            return [$table => $timestamp];
        });

    expect($tableCreatedAt)->not->toBeEmpty();

    foreach ($migrationFiles as $path) {
        $filename = basename($path);
        $timestamp = Str::beforeLast($filename, '_create_');
        $content = file_get_contents($path);

        preg_match_all("/->constrained\\('([^']+)'\\)/", $content, $matches);

        $referencedTables = collect($matches[1] ?? [])->unique()->values();

        foreach ($referencedTables as $referencedTable) {
            expect($tableCreatedAt->has($referencedTable))
                ->toBeTrue("Missing migration that creates referenced table [{$referencedTable}] (referenced by [{$filename}]).");

            expect($tableCreatedAt->get($referencedTable))
                ->toBeLessThan($timestamp, "Migration [{$filename}] runs before referenced table [{$referencedTable}] is created.");
        }
    }
});

