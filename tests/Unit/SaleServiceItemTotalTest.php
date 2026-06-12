<?php

use App\Services\SaleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

it('recalculates the unit price from the gross visual total', function (): void {
    $item = (new SaleService)->calculateItemFromTotal([
        'quantity' => 2,
        'unitPrice' => 10,
        'total' => 30,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ]);

    expect($item['unitPrice'])->toBe('15.00000')
        ->and($item['totalWithoutDiscount'])->toBe('30.00000');
});

it('calculates a discount that reaches the desired item total', function (): void {
    $item = (new SaleService)->calculateItemFromDesiredTotal([
        'quantity' => 2,
        'unitPrice' => 10,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ], 15);

    expect($item['unitPrice'])->toBe('10.00000')
        ->and($item['totalWithoutDiscount'])->toBe('20.00000')
        ->and($item['total'])->toBe('15.00000')
        ->and(data_get($item, 'discounts.0.applyTo'))->toBe('base')
        ->and(data_get($item, 'discounts.0.baseAmount'))->toBe('16.95000')
        ->and(data_get($item, 'discounts.0.discountAmount'))->toBe('4.24')
        ->and($item['itemValue'])->toBe('12.71000')
        ->and($item['igvAmount'])->toBe('2.29000');

    $productionExample = (new SaleService)->calculateItemFromDesiredTotal([
        'quantity' => 2,
        'unitPrice' => 109.90,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ], 164.90);

    expect($productionExample['total'])->toBe('164.90000')
        ->and($productionExample['itemValue'])->toBe('139.75000')
        ->and($productionExample['igvAmount'])->toBe('25.15000')
        ->and($productionExample['unitPriceWithDiscount'])->toBe('82.45000')
        ->and(data_get($productionExample, 'discounts.0.baseAmount'))->toBe('186.27000')
        ->and(data_get($productionExample, 'discounts.0.discountAmount'))->toBe('46.52');
});

it('limits the desired item total to the gross total', function (): void {
    $item = (new SaleService)->calculateItemFromDesiredTotal([
        'quantity' => 1,
        'unitPrice' => 10,
        'igvPercent' => 18,
        'igvAffectationType' => '10',
        'discounts' => [],
    ], 12);

    expect($item['totalWithoutDiscount'])->toBe('10.00000')
        ->and($item['total'])->toBe('10.00000')
        ->and(data_get($item, 'discounts.0.discountAmount'))->toBe('0.00');
});

it('calculates document total from fiscal amounts rounded to two decimals', function (): void {
    $totals = (new SaleService)->calculateTotals([
        [
            'igvAffectationType' => '10',
            'itemValue' => '279.48500',
            'igvAmount' => '50.30700',
        ],
    ]);

    expect($totals['saleValue'])->toBe('279.49000')
        ->and($totals['totalTaxes'])->toBe('50.31000')
        ->and($totals['total'])->toBe('329.80000');
});

it('keeps one thousand random documents fiscally consistent', function (): void {
    mt_srand(20260612);
    $saleService = new SaleService;

    for ($case = 0; $case < 1000; $case++) {
        $items = [];
        $itemCount = mt_rand(1, 12);

        for ($index = 0; $index < $itemCount; $index++) {
            $quantity = number_format(mt_rand(1, 500000) / 100000, 5, '.', '');
            $unitPrice = number_format(mt_rand(1, 10000000) / 100000, 5, '.', '');
            $discountPercent = mt_rand(0, 7000) / 100;
            $payload = [
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'igvPercent' => 18,
                'igvAffectationType' => '10',
                'discounts' => [],
            ];
            $grossItem = $saleService->calculateItem($payload);

            $item = match (mt_rand(0, 2)) {
                0 => $saleService->calculateItem([
                    ...$payload,
                    'discounts' => [[
                        ...$saleService->newDiscount(),
                        'uiPercent' => number_format($discountPercent, 2, '.', ''),
                        'mode' => 'percent',
                    ]],
                ], discountRecalculateFrom: 'percent'),
                1 => $saleService->calculateItem([
                    ...$payload,
                    'discounts' => [[
                        ...$saleService->newDiscount(),
                        'discountAmount' => BigDecimal::of($grossItem['itemValue'])
                            ->multipliedBy((string) ($discountPercent / 100))
                            ->toScale(2, RoundingMode::HALF_UP),
                        'mode' => 'amount',
                    ]],
                ], discountRecalculateFrom: 'amount'),
                2 => $saleService->calculateItemFromDesiredTotal(
                    $payload,
                    BigDecimal::of($grossItem['total'])
                        ->multipliedBy((string) ((100 - $discountPercent) / 100))
                        ->toScale(2, RoundingMode::HALF_UP),
                ),
            };

            $items[] = $item;
        }

        $totals = $saleService->calculateTotals($items);
        $roundedBase = BigDecimal::of($totals['saleValue'])
            ->toScale(2, RoundingMode::HALF_UP);
        $roundedTaxes = BigDecimal::of($totals['totalTaxes'])
            ->toScale(2, RoundingMode::HALF_UP);
        $roundedTotal = BigDecimal::of($totals['total'])
            ->toScale(2, RoundingMode::HALF_UP);

        expect((string) $roundedTotal)->toBe(
            (string) $roundedBase->plus($roundedTaxes)->toScale(2, RoundingMode::HALF_UP),
            "The fiscal totals are inconsistent in random case {$case}.",
        );
    }
});

it('matches ten thousand client promotion allocations', function (): void {
    mt_srand(20260612);
    $saleService = new SaleService;
    $promotions = [
        ['prices' => ['109.90', '25.00', '35.00'], 'target' => '149.90'],
        ['prices' => ['219.80', '50.00', '70.00'], 'target' => '299.80'],
        ['prices' => ['109.90', '25.00'], 'target' => '97.95'],
        ['prices' => ['219.80', '50.00', '35.00'], 'target' => '189.90'],
        ['prices' => ['219.80', '25.00', '35.00'], 'target' => '189.90'],
        ['prices' => ['219.80', '50.00'], 'target' => '215.80'],
        ['prices' => ['219.80'], 'target' => '164.90'],
        ['prices' => ['150.00'], 'target' => '108.00'],
        ['prices' => ['329.70', '50.00', '70.00'], 'target' => '235.90'],
        ['prices' => ['109.90', '25.00', '105.00'], 'target' => '219.90'],
    ];

    for ($case = 0; $case < 10000; $case++) {
        $promotion = $promotions[array_rand($promotions)];
        $grossTotal = array_reduce(
            $promotion['prices'],
            fn (BigDecimal $total, string $price): BigDecimal => $total->plus($price),
            BigDecimal::zero(),
        );
        $targetTotal = BigDecimal::of($promotion['target']);
        $allocatedTotal = BigDecimal::zero();
        $items = [];
        $lastIndex = count($promotion['prices']) - 1;

        foreach ($promotion['prices'] as $index => $price) {
            $desiredTotal = $index === $lastIndex
                ? $targetTotal->minus($allocatedTotal)
                : $targetTotal
                    ->multipliedBy($price)
                    ->dividedBy($grossTotal, 2, RoundingMode::HALF_UP);

            $allocatedTotal = $allocatedTotal->plus($desiredTotal);
            $items[] = $saleService->calculateItemFromDesiredTotal([
                'quantity' => 1,
                'unitPrice' => $price,
                'igvPercent' => 18,
                'igvAffectationType' => '10',
                'discounts' => [],
            ], (string) $desiredTotal);
        }

        $totals = $saleService->calculateTotals($items);
        $calculatedTotal = BigDecimal::of($totals['total'])
            ->toScale(2, RoundingMode::HALF_UP);
        $calculatedBase = BigDecimal::of($totals['saleValue'])
            ->toScale(2, RoundingMode::HALF_UP);
        $calculatedTaxes = BigDecimal::of($totals['totalTaxes'])
            ->toScale(2, RoundingMode::HALF_UP);

        expect((string) $calculatedTotal)->toBe(
            (string) $targetTotal,
            "The client promotion total is inconsistent in case {$case}.",
        )->and((string) $calculatedBase->plus($calculatedTaxes))->toBe(
            (string) $targetTotal,
            "The client promotion base and IGV are inconsistent in case {$case}.",
        );
    }
});

it('triggers the desired total calculation when the field loses focus', function (): void {
    $modalSource = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/components/sale/⚡modal-item.blade.php',
    );

    expect($modalSource)
        ->toContain('wire:model.blur="saleItem.desiredTotal"')
        ->toContain('wire:blur="calculateFromDesiredTotal"');
});
