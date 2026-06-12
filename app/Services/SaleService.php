<?php

namespace App\Services;

use App\Enums\Sunat\AffecType;
use App\Enums\Sunat\DiscountType;
use App\Livewire\Forms\SaleForm;
use App\Livewire\Forms\SaleItemForm;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class SaleService
{
    private const SCALE_UNIT = 5;

    private const SCALE_BASE = 5;

    private const SCALE_DISCOUNT = 2;

    private const SCALE_MONEY = 5;

    private const SCALE_FACTOR = 5;

    private const SCALE_CALC = 10;

    public function newDiscount(string $type = DiscountType::ITEM->value): array
    {
        return [
            'type' => $type,
            'baseAmount' => '0.00000',
            'factorPorcentage' => '0.00000',
            'discountAmount' => '0.00',
            'uiPercent' => '0.00',
            'enabled' => true,
            'mode' => 'amount',

            // base = descuento SUNAT, total = descuento comercial
            'applyTo' => 'base',
        ];
    }

    public function addItem(array $items, SaleItemForm $saleItem): array
    {
        $item = [
            'igvAffectationType' => $saleItem->igvAffectationType,
            'code' => $saleItem->code,
            'description' => $saleItem->description,
            'unit' => $saleItem->unit,
            'quantity' => $saleItem->quantity,
            'unitPrice' => $saleItem->unitPrice,
            'igvPercent' => $saleItem->igvPercent,
            'discounts' => $saleItem->discounts ?? [],
        ];

        $items[] = $this->calculateItem($item);

        return $items;
    }

    public function calculateItem(array $item, $totalItem = null, ?string $discountRecalculateFrom = null): array
    {
        $quantity = $this->bd($item['quantity'] ?? '1');
        $unitPrice = $this->bd($item['unitPrice'] ?? '0');
        $igvPercent = $this->bd($item['igvPercent'] ?? '18');

        if ($quantity->isLessThanOrEqualTo('0')) {
            $quantity = $this->bd('1');
        }

        $igvAffectationType = (string) ($item['igvAffectationType'] ?? AffecType::GRAVADO->value);

        $grossTotal = $quantity
            ->multipliedBy($unitPrice)
            ->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

        [$unitValue, $grossBaseAmount] = $this->calculateItemAmounts(
            quantity: $quantity,
            unitPrice: $unitPrice,
            igvPercent: $igvPercent,
            igvAffectationType: $igvAffectationType,
        );

        $discountApplyTo = (string) data_get($item, 'discounts.0.applyTo', 'base');

        if ($discountApplyTo === 'total') {
            [$discounts, $discountAmount] = $this->normalizeDiscounts(
                discounts: $item['discounts'] ?? null,
                baseAmount: $grossTotal,
                recalculateFrom: $discountRecalculateFrom
            );

            $totalWithDiscount = $grossTotal
                ->minus($discountAmount)
                ->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

            if ($totalWithDiscount->isLessThan('0')) {
                $totalWithDiscount = $this->bd('0')->toScale(self::SCALE_MONEY);
            }

            if ($igvAffectationType === AffecType::GRAVADO->value) {
                $taxFactor = $this->taxFactor($igvPercent);

                $itemValue = $totalWithDiscount
                    ->dividedBy($taxFactor, self::SCALE_BASE, RoundingMode::HALF_UP);

                $igvAmount = $totalWithDiscount
                    ->minus($itemValue)
                    ->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

                $igvBaseAmount = $itemValue;
                $taxesTotal = $igvAmount;
            } else {
                $itemValue = $totalWithDiscount->toScale(self::SCALE_BASE, RoundingMode::HALF_UP);
                $igvAmount = $this->bd('0')->toScale(self::SCALE_MONEY);
                $igvBaseAmount = $this->bd('0')->toScale(self::SCALE_BASE);
                $taxesTotal = $this->bd('0')->toScale(self::SCALE_MONEY);
            }
        } else {
            [$discounts, $discountAmount] = $this->normalizeDiscounts(
                discounts: $item['discounts'] ?? null,
                baseAmount: $grossBaseAmount,
                recalculateFrom: $discountRecalculateFrom
            );

            $itemValue = $grossBaseAmount
                ->minus($discountAmount)
                ->toScale(self::SCALE_BASE, RoundingMode::HALF_UP);

            if ($itemValue->isLessThan('0')) {
                $itemValue = $this->bd('0')->toScale(self::SCALE_BASE);
            }

            if ($igvAffectationType === AffecType::GRAVADO->value) {
                $igvAmount = $itemValue
                    ->multipliedBy($igvPercent)
                    ->dividedBy('100', self::SCALE_MONEY, RoundingMode::HALF_UP);

                $igvBaseAmount = $itemValue;
                $taxesTotal = $igvAmount;
            } else {
                $igvAmount = $this->bd('0')->toScale(self::SCALE_MONEY);
                $igvBaseAmount = $this->bd('0')->toScale(self::SCALE_BASE);
                $taxesTotal = $this->bd('0')->toScale(self::SCALE_MONEY);
            }

            $totalWithDiscount = $itemValue
                ->plus($taxesTotal)
                ->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);
        }

        $unitPriceWithDiscount = $quantity->isGreaterThan('0')
            ? $totalWithDiscount->dividedBy($quantity, self::SCALE_MONEY, RoundingMode::HALF_UP)
            : $this->bd('0')->toScale(self::SCALE_MONEY);

        return array_merge($item, [
            'quantity' => (string) $quantity,
            'unitPrice' => $this->format($unitPrice, self::SCALE_MONEY),
            'unitValue' => $this->format($unitValue, self::SCALE_UNIT),
            'saleValue' => $this->format($itemValue, self::SCALE_BASE),
            'itemValue' => $this->format($itemValue, self::SCALE_BASE),
            'total' => $this->format($totalWithDiscount, self::SCALE_MONEY),
            'igv' => $this->format($igvAmount, self::SCALE_MONEY),
            'igvBaseAmount' => $this->format($igvBaseAmount, self::SCALE_BASE),
            'igvAmount' => $this->format($igvAmount, self::SCALE_MONEY),
            'totalTaxes' => $this->format($taxesTotal, self::SCALE_MONEY),
            'taxesTotal' => $this->format($taxesTotal, self::SCALE_MONEY),
            'discounts' => $discounts,
            'discountAmount' => $this->format($discountAmount, self::SCALE_DISCOUNT),
            'unitPriceWithDiscount' => $this->format($unitPriceWithDiscount, self::SCALE_MONEY),
            'totalWithoutDiscount' => $this->format($grossTotal, self::SCALE_MONEY),
        ]);
    }

    public function calculateItemFromTotal(array $item, ?string $discountRecalculateFrom = null): array
    {
        $quantity = $this->bd($item['quantity'] ?? '1');
        $total = $this->bd($item['total'] ?? '0');

        if ($quantity->isLessThanOrEqualTo('0')) {
            $quantity = $this->bd('1');
        }

        $unitPrice = $total->dividedBy($quantity, self::SCALE_MONEY, RoundingMode::HALF_UP);

        return $this->calculateItem(
            array_merge($item, [
                'quantity' => (string) $quantity,
                'unitPrice' => (string) $unitPrice,
            ]),
            null,
            $discountRecalculateFrom
        );
    }

    public function calculateItemFromDesiredTotal(array $item, string|int|float|null $desiredTotal): array
    {
        $calculated = $this->calculateItem($item);
        $grossTotal = $this->bd($calculated['totalWithoutDiscount'] ?? '0')
            ->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);
        $desiredTotal = $this->bd($desiredTotal)
            ->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

        if ($desiredTotal->isLessThan('0')) {
            $desiredTotal = $this->bd('0');
        }

        if ($desiredTotal->isGreaterThan($grossTotal)) {
            $desiredTotal = $grossTotal;
        }

        $igvAffectationType = (string) ($item['igvAffectationType'] ?? AffecType::GRAVADO->value);
        $igvPercent = $this->bd($item['igvPercent'] ?? '18');
        $grossBaseAmount = $this->bd($calculated['unitValue'] ?? '0')
            ->multipliedBy($this->bd($calculated['quantity'] ?? '1'))
            ->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

        $itemValue = $igvAffectationType === AffecType::GRAVADO->value
            ? $desiredTotal->dividedBy(
                $this->taxFactor($igvPercent),
                self::SCALE_DISCOUNT,
                RoundingMode::HALF_UP,
            )
            : $desiredTotal;

        $igvAmount = $igvAffectationType === AffecType::GRAVADO->value
            ? $desiredTotal->minus($itemValue)->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP)
            : $this->bd('0')->toScale(self::SCALE_DISCOUNT);

        $discountAmount = $grossBaseAmount
            ->minus($itemValue)
            ->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

        if ($discountAmount->isLessThan('0')) {
            $discountAmount = $this->bd('0')->toScale(self::SCALE_DISCOUNT);
        }

        $factor = $grossBaseAmount->isGreaterThan('0')
            ? $discountAmount->dividedBy($grossBaseAmount, self::SCALE_FACTOR, RoundingMode::HALF_UP)
            : $this->bd('0')->toScale(self::SCALE_FACTOR);

        $discount = [
            'type' => DiscountType::ITEM->value,
            'baseAmount' => $this->format($grossBaseAmount, self::SCALE_BASE),
            'factorPorcentage' => $this->format($factor, self::SCALE_FACTOR),
            'discountAmount' => $this->format($discountAmount, self::SCALE_DISCOUNT),
            'uiPercent' => $this->format($factor->multipliedBy('100'), self::SCALE_MONEY),
            'enabled' => true,
            'mode' => 'amount',
            'applyTo' => 'base',
        ];

        $quantity = $this->bd($calculated['quantity'] ?? '1');
        $unitPriceWithDiscount = $quantity->isGreaterThan('0')
            ? $desiredTotal->dividedBy($quantity, self::SCALE_MONEY, RoundingMode::HALF_UP)
            : $this->bd('0')->toScale(self::SCALE_MONEY);

        return array_merge($calculated, [
            'saleValue' => $this->format($itemValue, self::SCALE_BASE),
            'itemValue' => $this->format($itemValue, self::SCALE_BASE),
            'total' => $this->format($desiredTotal, self::SCALE_MONEY),
            'igv' => $this->format($igvAmount, self::SCALE_MONEY),
            'igvBaseAmount' => $igvAffectationType === AffecType::GRAVADO->value
                ? $this->format($itemValue, self::SCALE_BASE)
                : $this->format($this->bd('0'), self::SCALE_BASE),
            'igvAmount' => $this->format($igvAmount, self::SCALE_MONEY),
            'totalTaxes' => $this->format($igvAmount, self::SCALE_MONEY),
            'taxesTotal' => $this->format($igvAmount, self::SCALE_MONEY),
            'discounts' => [$discount],
            'discountAmount' => $this->format($discountAmount, self::SCALE_DISCOUNT),
            'unitPriceWithDiscount' => $this->format($unitPriceWithDiscount, self::SCALE_MONEY),
        ]);
    }

    public function hydrateItemsForSunatFromDatabase(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }
                $discounts = $item['discounts'] ?? [];
                if (! is_array($discounts)) {
                    $discounts = [];
                }
                $payload = [
                    'igvAffectationType' => (string) ($item['igvAffectationType'] ?? AffecType::GRAVADO->value),
                    'code' => (string) ($item['code'] ?? '00000'),
                    'description' => (string) ($item['description'] ?? 'PRODUCTO'),
                    'unit' => (string) ($item['unit'] ?? 'NIU'),
                    'quantity' => (string) ($item['quantity'] ?? '1'),
                    'unitPrice' => (string) ($item['unitPrice'] ?? '0'),
                    'igvPercent' => (string) ($item['igvPercent'] ?? '18'),
                    'discounts' => $discounts,
                ];

                $calculated = $this->calculateItem($payload);
                $calculated['unitValue'] = (string) ($item['unitValue'] ?? $calculated['unitValue']);
                $calculated['itemValue'] = (string) ($item['itemValue'] ?? $calculated['itemValue']);
                $calculated['saleValue'] = (string) ($item['saleValue'] ?? $calculated['saleValue']);
                $calculated['igvBaseAmount'] = (string) ($item['igvBaseAmount'] ?? $calculated['igvBaseAmount']);
                $calculated['igvAmount'] = (string) ($item['igvAmount'] ?? $calculated['igvAmount']);
                $calculated['igv'] = (string) ($item['igv'] ?? $calculated['igv']);
                $calculated['totalTaxes'] = (string) ($item['totalTaxes'] ?? $calculated['totalTaxes']);
                $calculated['taxesTotal'] = (string) ($item['taxesTotal'] ?? $calculated['taxesTotal']);

                $calculated['total'] = (string) ($item['total'] ?? $this->format(
                    $this->bd($calculated['itemValue'] ?? '0')
                        ->plus($this->bd($calculated['totalTaxes'] ?? $calculated['taxesTotal'] ?? '0')),
                    self::SCALE_MONEY,
                ));
                $calculated['unitPriceWithDiscount'] = (string) ($item['unitPriceWithDiscount'] ?? $calculated['unitPriceWithDiscount']);
                $calculated['totalWithoutDiscount'] = (string) ($item['totalWithoutDiscount'] ?? $calculated['totalWithoutDiscount']);
                $calculated['discountAmount'] = (string) ($item['discountAmount'] ?? $calculated['discountAmount']);

                // $calculated = $this->normalizeItemDiscountForSunat($calculated);

                // $discountAmount = (float) data_get($calculated, 'discounts.0.discountAmount', 0);
                // $quantity = (float) ($calculated['quantity'] ?? 0);
                // if ($discountAmount > 0 && $quantity > 0) {
                //     $lineTotalWithTaxes = (float) ($calculated['itemValue'] ?? 0) + (float) ($calculated['totalTaxes'] ?? 0);
                //     $unitPriceWithDiscount = round($lineTotalWithTaxes / $quantity, 2);
                //     $calculated['unitPriceWithDiscount'] = number_format($unitPriceWithDiscount, 2, '.', '');
                // }

                return $calculated;
            })
            ->values()
            ->all();
    }

    public function calculateTotals(array $items): array
    {
        $totalTaxed = $this->fiscalAmount(
            $this->sumWhere($items, 'igvAffectationType', AffecType::GRAVADO->value, 'itemValue', self::SCALE_BASE),
        );
        $totalExempted = $this->fiscalAmount(
            $this->sumWhere($items, 'igvAffectationType', AffecType::EXONERADO->value, 'itemValue', self::SCALE_BASE),
        );
        $totalUnaffected = $this->fiscalAmount(
            $this->sumWhere($items, 'igvAffectationType', AffecType::INAFECTO->value, 'itemValue', self::SCALE_BASE),
        );
        $totalFree = $this->fiscalAmount(
            $this->sumWhere($items, 'igvAffectationType', AffecType::GRATUITO->value, 'itemValue', self::SCALE_BASE),
        );

        $totalExport = $this->bd('0')->toScale(self::SCALE_BASE);
        $icbper = $this->bd('0')->toScale(self::SCALE_MONEY);

        $totalIgv = $this->fiscalAmount(
            $this->sumWhere($items, 'igvAffectationType', AffecType::GRAVADO->value, 'igvAmount', self::SCALE_MONEY),
        );
        $totalIgvFree = $this->fiscalAmount(
            $this->sumWhere($items, 'igvAffectationType', AffecType::GRATUITO->value, 'igvAmount', self::SCALE_MONEY),
        );

        $totalTaxes = $totalIgv->plus($icbper)->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

        $saleValue = $totalTaxed
            ->plus($totalExempted)
            ->plus($totalUnaffected)
            ->plus($totalExport)
            ->toScale(self::SCALE_BASE, RoundingMode::HALF_UP);

        $subTotal = $saleValue->plus($totalTaxes)->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

        return [
            'totalTaxed' => $this->format($totalTaxed, self::SCALE_BASE),
            'totalExempted' => $this->format($totalExempted, self::SCALE_BASE),
            'totalUnaffected' => $this->format($totalUnaffected, self::SCALE_BASE),
            'totalExport' => $this->format($totalExport, self::SCALE_BASE),
            'totalFree' => $this->format($totalFree, self::SCALE_BASE),

            'totalIgv' => $this->format($totalIgv, self::SCALE_MONEY),
            'totalIgvFree' => $this->format($totalIgvFree, self::SCALE_MONEY),
            'icbper' => $this->format($icbper, self::SCALE_MONEY),

            'totalTaxes' => $this->format($totalTaxes, self::SCALE_MONEY),
            'saleValue' => $this->format($saleValue, self::SCALE_BASE),
            'subTotal' => $this->format($subTotal, self::SCALE_MONEY),
            'totalSale' => $this->format($subTotal, self::SCALE_MONEY),
            'rounding' => '0.00',
            'total' => $this->format($subTotal, self::SCALE_MONEY),
        ];
    }

    public function applyTotals(SaleForm $sale, array $items): void
    {
        $totals = $this->calculateTotals($items);

        foreach ($totals as $key => $value) {
            $sale->{$key} = $value;
        }
    }

    private function calculateItemAmounts(
        BigDecimal $quantity,
        BigDecimal $unitPrice,
        BigDecimal $igvPercent,
        string $igvAffectationType,
    ): array {
        if ($igvAffectationType === AffecType::GRAVADO->value) {
            $unitValue = $unitPrice->dividedBy($this->taxFactor($igvPercent), self::SCALE_UNIT, RoundingMode::HALF_UP);
        } else {
            $unitValue = $unitPrice->toScale(self::SCALE_UNIT, RoundingMode::HALF_UP);
        }

        $baseAmount = $unitValue
            ->multipliedBy($quantity)
            ->toScale(self::SCALE_BASE, RoundingMode::HALF_UP);

        return [$unitValue, $baseAmount];
    }

    private function normalizeDiscounts(?array $discounts, BigDecimal $baseAmount, ?string $recalculateFrom): array
    {
        if (! is_array($discounts) || empty($discounts)) {
            return [[], $this->bd('0')->toScale(self::SCALE_DISCOUNT)];
        }

        $first = is_array($discounts[0] ?? null) ? $discounts[0] : [];
        $enabled = (bool) ($first['enabled'] ?? true);

        if (! $enabled) {
            return [[], $this->bd('0')->toScale(self::SCALE_DISCOUNT)];
        }

        $baseAmount = $baseAmount->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

        $uiPercent = $this->bd($first['uiPercent'] ?? $first['percent'] ?? '0');
        $factor = $this->bd($first['factorPorcentage'] ?? '0');
        $discountAmount = $this->bd($first['discountAmount'] ?? '0');
        $mode = (string) ($first['mode'] ?? '');
        $applyTo = (string) ($first['applyTo'] ?? 'base');

        if ($recalculateFrom === 'percent' || $mode === 'percent') {
            if ($uiPercent->isLessThanOrEqualTo('0') && $factor->isGreaterThan('0')) {
                $uiPercent = $factor->multipliedBy('100');
            }

            if ($uiPercent->isLessThan('0')) {
                $uiPercent = $this->bd('0');
            }

            if ($uiPercent->isGreaterThan('100')) {
                $uiPercent = $this->bd('100');
            }

            $factor = $uiPercent->dividedBy('100', self::SCALE_FACTOR, RoundingMode::HALF_UP);

            $discountAmount = $baseAmount
                ->multipliedBy($factor)
                ->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

            $mode = 'percent';
        } else {
            if ($discountAmount->isLessThan('0')) {
                $discountAmount = $this->bd('0');
            }

            if ($discountAmount->isGreaterThan($baseAmount)) {
                $discountAmount = $baseAmount;
            }

            $discountAmount = $discountAmount->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

            $factor = $baseAmount->isGreaterThan('0')
                ? $discountAmount->dividedBy($baseAmount, self::SCALE_FACTOR, RoundingMode::HALF_UP)
                : $this->bd('0')->toScale(self::SCALE_FACTOR);

            $uiPercent = $factor->multipliedBy('100')->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

            $mode = 'amount';
        }

        $normalized = [[
            'type' => (string) ($first['type'] ?? '00'),
            'baseAmount' => $this->format($baseAmount, self::SCALE_BASE),
            'factorPorcentage' => $this->format($factor, self::SCALE_FACTOR),
            'discountAmount' => $this->format($discountAmount, self::SCALE_DISCOUNT),
            'uiPercent' => $this->format($uiPercent, self::SCALE_MONEY),
            'enabled' => true,
            'mode' => $mode,
            'applyTo' => $applyTo,
        ]];

        if ($baseAmount->isLessThanOrEqualTo('0') || $discountAmount->isLessThanOrEqualTo('0')) {
            return [$normalized, $this->bd('0')->toScale(self::SCALE_DISCOUNT)];
        }

        return [$normalized, $discountAmount];
    }

    private function sumWhere(array $items, string $whereKey, string $whereValue, string $sumKey, int $scale): BigDecimal
    {
        $total = $this->bd('0');

        foreach ($items as $item) {
            if ((string) ($item[$whereKey] ?? '') !== $whereValue) {
                continue;
            }

            $total = $total->plus($this->bd($item[$sumKey] ?? '0'));
        }

        return $total->toScale($scale, RoundingMode::HALF_UP);
    }

    private function fiscalAmount(BigDecimal $amount): BigDecimal
    {
        return $amount
            ->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP)
            ->toScale(self::SCALE_MONEY);
    }

    private function taxFactor(BigDecimal $igvPercent): BigDecimal
    {
        return $this->bd('1')->plus(
            $igvPercent->dividedBy('100', self::SCALE_CALC, RoundingMode::HALF_UP)
        );
    }

    private function bd(string|int|float|null $value): BigDecimal
    {
        $value = trim((string) $value);

        return BigDecimal::of($value === '' ? '0' : $value);
    }

    private function format(BigDecimal $value, int $scale): string
    {
        return (string) $value->toScale($scale, RoundingMode::HALF_UP);
    }

    public function normalizeItemDiscountForSunat(array $item): array
    {
        $quantity = $this->bd($item['quantity'] ?? '1');
        if ($quantity->isLessThanOrEqualTo('0')) {
            $quantity = $this->bd('1');
        }

        $unitValue = $this->bd($item['unitValue'] ?? '0');
        $lineExtension = $this->bd($item['itemValue'] ?? $item['saleValue'] ?? '0');

        $baseAmount = $unitValue
            ->multipliedBy($quantity)
            ->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

        $lineExtension = $lineExtension->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

        $discountAmount = $baseAmount->minus($lineExtension)->toScale(self::SCALE_DISCOUNT, RoundingMode::HALF_UP);

        if ($discountAmount->isLessThanOrEqualTo('0')) {
            return $item;
        }

        $factor = $baseAmount->isGreaterThan('0')
            ? $discountAmount->dividedBy($baseAmount, self::SCALE_FACTOR, RoundingMode::HALF_UP)
            : $this->bd('0')->toScale(self::SCALE_FACTOR);

        $normalized = [
            'type' => DiscountType::ITEM->value,
            'baseAmount' => $this->format($baseAmount, self::SCALE_MONEY),
            'factorPorcentage' => $this->format($factor, self::SCALE_FACTOR),
            'discountAmount' => $this->format($discountAmount, self::SCALE_DISCOUNT),
            'uiPercent' => $this->format($factor->multipliedBy('100'), self::SCALE_MONEY),
            'enabled' => true,
            'mode' => 'amount',
            'applyTo' => 'base',
        ];

        $item['discounts'] = [$normalized];

        $taxesTotal = $this->bd($item['totalTaxes'] ?? $item['taxesTotal'] ?? '0')
            ->toScale(self::SCALE_MONEY, RoundingMode::HALF_UP);

        $unitPriceWithDiscount = $quantity->isGreaterThan('0')
            ? $lineExtension
                ->plus($taxesTotal)
                ->dividedBy($quantity, self::SCALE_MONEY, RoundingMode::HALF_UP)
            : $this->bd('0')->toScale(self::SCALE_MONEY);

        $item['unitPriceWithDiscount'] = $this->format($unitPriceWithDiscount, self::SCALE_MONEY);

        return $item;
    }
}
