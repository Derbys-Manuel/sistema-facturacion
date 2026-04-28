<?php

namespace App\Services;

use App\Enums\Sunat\AffecType;
use App\Livewire\Forms\BoletaForm;
use App\Livewire\Forms\BoletaItemForm;

class SaleCreateService
{
    public function addItem(array $items, BoletaItemForm $item): array
    {
        $qty = (float) $item->quantity;
        $unitPrice = (float) $item->unitPrice; // precio con IGV
        $igvPercent = (float) $item->igvPercent;

        $factor = 1 + ($igvPercent / 100);

        $unitValue = round($unitPrice / $factor, 2); // sin IGV
        $itemValue = round($unitValue * $qty, 2); // subtotal sin IGV
        $igvAmount = round($itemValue * ($igvPercent / 100), 2);
        $taxesTotal = $igvAmount;
        $total = round($itemValue + $taxesTotal, 2);

        $items[] = [
            'igvAffectationType' => $item->igvAffectationType,
            'code' => $item->code,
            'description' => $item->description,
            'unit' => 'NIU',
            'quantity' => $qty,

            'unitValue' => $unitValue,
            'itemValue' => $itemValue,
            'unitPrice' => $unitPrice,

            'igvBaseAmount' => $itemValue,
            'igvPercent' => $igvPercent,
            'igvAmount' => $igvAmount,
            'taxesTotal' => $igvAmount,

            'total' => $total,
        ];

        return $items;
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

    public function applyTotals(BoletaForm $sale, array $items): void
    {
        foreach ($this->calculateTotals($items) as $key => $value) {
            $sale->{$key} = $value;
        }
    }
}
