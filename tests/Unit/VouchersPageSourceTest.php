<?php

it('does not rerun mount after voucher actions', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/pages/sale/⚡vouchers.blade.php',
    );

    expect(substr_count($source, '$this->mount();'))->toBe(0);
});
