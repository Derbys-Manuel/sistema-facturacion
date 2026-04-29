<?php

namespace App\Services;

use App\Enums\Sunat\AffecType;
use App\Livewire\Forms\SaleForm;
use App\Livewire\Forms\SaleItemForm;

class SaleService
{
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
        ];
        $items[] = $this->calculateItem($item);
        return $items;
    }
    public function calculateItem(array $item, $totalItem = null): array
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $unitPrice = (float) ($item['unitPrice'] ?? 0);
        $igvPercent = (float) ($item['igvPercent'] ?? 18);

        if ($totalItem !== null) {
            $total = (float) $totalItem;
        } else {
            $total = round($quantity * $unitPrice, 2);
        }

        $igvAffectationType = (string) ($item['igvAffectationType'] ?? AffecType::GRAVADO->value);

        if ($igvAffectationType === AffecType::GRAVADO->value) {
            $unitValue = round($unitPrice / (1 + ($igvPercent / 100)), 6);
            $itemValue = round($quantity * $unitValue, 2);
            $igvAmount = round($total - $itemValue, 2);
            $igvBaseAmount = $itemValue;
            $taxesTotal = $igvAmount;
        } else {
            $unitValue = round($unitPrice, 6);
            $itemValue = round($quantity * $unitValue, 2);
            $igvAmount = 0.0;
            $igvBaseAmount = 0.0;
            $taxesTotal = 0.0;
        }

        return array_merge($item, [
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'unitValue' => $unitValue,
            'saleValue' => $itemValue,
            'itemValue' => $itemValue,
            'total' => $total,
            'igv' => $igvAmount,
            'igvBaseAmount' => $igvBaseAmount,
            'igvAmount' => $igvAmount,
            'totalTaxes' => $taxesTotal,
            'taxesTotal' => $taxesTotal,
        ]);
    }
    public function calculateItemFromTotal(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $total = (float) ($item['total'] ?? 0);
        $igvPercent = (float) ($item['igvPercent'] ?? 18);
        $igvAffectationType = (string) ($item['igvAffectationType'] ?? AffecType::GRAVADO->value);

        if ($quantity <= 0) {
            $quantity = 1;
        }

        $unitPrice = round($total / $quantity, 2);

        if ($igvAffectationType === AffecType::GRAVADO->value) {
            $unitValue = round($unitPrice / (1 + ($igvPercent / 100)), 6);
            $itemValue = round($quantity * $unitValue, 2);
            $igvAmount = round($total - $itemValue, 2);
            $igvBaseAmount = $itemValue;
            $taxesTotal = $igvAmount;
        } else {
            $unitValue = round($unitPrice, 6);
            $itemValue = round($quantity * $unitValue, 2);
            $igvAmount = 0.0;
            $igvBaseAmount = 0.0;
            $taxesTotal = 0.0;
        }

        return array_merge($item, [
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'unitValue' => $unitValue,
            'saleValue' => $itemValue,
            'itemValue' => $itemValue,
            'igvBaseAmount' => $igvBaseAmount,
            'total' => round($total, 2),
            'igv' => $igvAmount,
            'igvAmount' => $igvAmount,
            'totalTaxes' => $taxesTotal,
            'taxesTotal' => $taxesTotal,
        ]);
    }
    public function calculateTotals(array $items): array
    {
        $details = collect($items);
        $totalTaxed = $details
            ->where('igvAffectationType', AffecType::GRAVADO->value)
            ->sum('itemValue');

        $totalExempted = $details
            ->where('igvAffectationType', AffecType::EXONERADO->value)
            ->sum('itemValue');

        $totalUnaffected = $details
            ->where('igvAffectationType', AffecType::INAFECTO->value)
            ->sum('itemValue');

        $totalExport = 0;

        $totalFree = $details
            ->where('igvAffectationType', AffecType::GRATUITO->value)
            ->sum('itemValue');

        $totalIgv = $details
            ->where('igvAffectationType', AffecType::GRAVADO->value)
            ->sum('igvAmount');

        $totalIgvFree = $details
            ->where('igvAffectationType', AffecType::GRATUITO->value)
            ->sum('igvAmount');

        $icbper = 0;
        $totalTaxes = round($totalIgv + $icbper, 2);
        $saleValue = round($totalTaxed + $totalExempted + $totalUnaffected + $totalExport, 2);
        $subTotal = round($saleValue + $totalTaxes, 2);
        $totalSale = round($subTotal, 2);
        $rounding = round($totalSale - $subTotal, 2);

        return [
            'totalTaxed' => round($totalTaxed, 2),
            'totalExempted' => round($totalExempted, 2),
            'totalUnaffected' => round($totalUnaffected, 2),
            'totalExport' => round($totalExport, 2),
            'totalFree' => round($totalFree, 2),
            'totalIgv' => round($totalIgv, 2),
            'totalIgvFree' => round($totalIgvFree, 2),
            'icbper' => $icbper,
            'totalTaxes' => $totalTaxes,
            'saleValue' => $saleValue,
            'subTotal' => $subTotal,
            'totalSale' => $totalSale,
            'rounding' => $rounding,
            'total' => $totalSale,
        ];
    }

    public function applyTotals(SaleForm $sale, array $items): void
    {
        foreach ($this->calculateTotals($items) as $key => $value) {
            $sale->{$key} = $value;
        }
    }
}
