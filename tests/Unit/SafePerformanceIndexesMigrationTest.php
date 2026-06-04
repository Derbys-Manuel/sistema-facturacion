<?php

it('creates performance indexes concurrently without destructive schema operations', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2).'/database/migrations/2026_06_04_195351_add_safe_performance_indexes.php',
    );

    expect($source)
        ->toContain('public $withinTransaction = false;')
        ->not->toContain('public bool $withinTransaction')
        ->toContain('CREATE INDEX CONCURRENTLY IF NOT EXISTS')
        ->toContain('DROP INDEX CONCURRENTLY IF EXISTS')
        ->not->toContain('CREATE UNIQUE INDEX')
        ->not->toContain('dropTable')
        ->not->toContain('truncate');
});
