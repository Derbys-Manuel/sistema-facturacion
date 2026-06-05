<?php

it('defines a portable and supervised queue worker', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/ecosystem.config.cjs');

    expect($source)
        ->toContain('process.env.APP_PATH')
        ->toContain('process.env.PHP_BINARY')
        ->toContain('queue:work database')
        ->toContain("script: isWindows ? 'cmd.exe' : phpBinary")
        ->toContain("interpreter: 'none'")
        ->toContain('autorestart: true')
        ->toContain("max_memory_restart: '256M'");
});
