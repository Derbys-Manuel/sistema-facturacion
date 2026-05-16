<?php

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\Sunat\AffecType;
use App\Enums\Sunat\DocIdentityType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use App\Models\Client;
use App\Models\Company;
use App\Models\SaleDocument;
use App\Services\SaleService;
use App\Services\SunatService;
use Greenter\Xml\Builder\NoteBuilder;

function bd_money(string $value): BigDecimal
{
    return BigDecimal::of(trim($value))->toScale(2, RoundingMode::HALF_UP);
}

function xpath_text(DOMXPath $xpath, string $query, ?DOMNode $context = null): ?string
{
    $node = $xpath->query($query, $context)->item(0);
    if (! $node instanceof DOMElement) {
        return null;
    }

    return trim($node->nodeValue);
}

function xpath_sum_money(DOMXPath $xpath, string $query, ?DOMNode $context = null): BigDecimal
{
    $sum = BigDecimal::of('0')->toScale(2, RoundingMode::HALF_UP);

    foreach ($xpath->query($query, $context) as $node) {
        if (! $node instanceof DOMElement) {
            continue;
        }

        $sum = $sum->plus(bd_money($node->nodeValue));
    }

    return $sum->toScale(2, RoundingMode::HALF_UP);
}

/**
 * Validaciones internas (consistencia) para reducir observaciones SUNAT:
 * - Sumatorias: TaxTotal/TaxAmount = sum(TaxSubtotal/TaxAmount)
 * - IGV por línea: TaxableAmount = LineExtensionAmount; TaxAmount = TaxableAmount*Percent/100 (2 dec)
 * - IGV documento: TaxSubtotal(1000) cuadra con sumatoria de líneas
 * - PayableAmount = sum(taxable subtotals) + TaxTotal + ChargeTotal + Rounding
 */
function emulateSunatCreditNoteConsistency(string $xml): array
{
    $dom = new DOMDocument();
    $dom->loadXML($xml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('cn', 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

    $notes = [];

    $docTaxTotalNode = $xpath->query('/cn:CreditNote/cac:TaxTotal')->item(0);
    if (! $docTaxTotalNode instanceof DOMElement) {
        return ['DOC_TAXTOTAL_MISSING'];
    }

    $docTaxTotal = xpath_text($xpath, './cbc:TaxAmount', $docTaxTotalNode);
    if ($docTaxTotal !== null) {
        $docTaxTotalMoney = bd_money($docTaxTotal);
        $docTaxSubtotalSum = xpath_sum_money($xpath, './cac:TaxSubtotal/cbc:TaxAmount', $docTaxTotalNode);
        if (! $docTaxTotalMoney->isEqualTo($docTaxSubtotalSum)) {
            $notes[] = 'DOC_TAX_TOTAL_MISMATCH';
        }
    }

    $lineTaxableSum = BigDecimal::of('0')->toScale(2, RoundingMode::HALF_UP);
    $lineIgvSum = BigDecimal::of('0')->toScale(2, RoundingMode::HALF_UP);

    /** @var DOMElement $line */
    foreach ($xpath->query('//cac:CreditNoteLine') as $line) {
        $lineId = xpath_text($xpath, './cbc:ID', $line) ?? '?';

        $lineExtText = xpath_text($xpath, './cbc:LineExtensionAmount', $line) ?? '0';
        $lineExt = bd_money($lineExtText);

        $lineTaxTotalText = xpath_text($xpath, './cac:TaxTotal/cbc:TaxAmount', $line);
        if ($lineTaxTotalText !== null) {
            $lineTaxTotal = bd_money($lineTaxTotalText);
            $lineTaxSubtotalSum = xpath_sum_money($xpath, './cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', $line);
            if (! $lineTaxTotal->isEqualTo($lineTaxSubtotalSum)) {
                $notes[] = "LINE_TAX_TOTAL_MISMATCH:L{$lineId}";
            }
        }

        $percentText = xpath_text(
            $xpath,
            './cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="1000"]/cac:TaxCategory/cbc:Percent',
            $line
        );
        $taxableText = xpath_text(
            $xpath,
            './cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="1000"]/cbc:TaxableAmount',
            $line
        );
        $igvText = xpath_text(
            $xpath,
            './cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="1000"]/cbc:TaxAmount',
            $line
        );

        if ($percentText !== null && $taxableText !== null && $igvText !== null) {
            $percent = BigDecimal::of($percentText);
            $taxable = bd_money($taxableText);
            $igv = bd_money($igvText);

            if (! $taxable->isEqualTo($lineExt)) {
                $notes[] = "LINE_TAXABLE_NEQ_LINEEXT:L{$lineId}";
            }

            $expectedIgv = $taxable
                ->multipliedBy($percent)
                ->dividedBy('100', 2, RoundingMode::HALF_UP)
                ->toScale(2, RoundingMode::HALF_UP);

            if (! $expectedIgv->isEqualTo($igv)) {
                $notes[] = "LINE_IGV_MISMATCH:L{$lineId}";
            }

            $lineTaxableSum = $lineTaxableSum->plus($taxable)->toScale(2, RoundingMode::HALF_UP);
            $lineIgvSum = $lineIgvSum->plus($igv)->toScale(2, RoundingMode::HALF_UP);
        }
    }

    $docIgvTaxable = xpath_text(
        $xpath,
        './cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="1000"]/cbc:TaxableAmount',
        $docTaxTotalNode
    );
    $docIgvAmount = xpath_text(
        $xpath,
        './cac:TaxSubtotal[cac:TaxCategory/cac:TaxScheme/cbc:ID="1000"]/cbc:TaxAmount',
        $docTaxTotalNode
    );

    if ($docIgvTaxable !== null && $docIgvAmount !== null) {
        if (! bd_money($docIgvTaxable)->isEqualTo($lineTaxableSum)) {
            $notes[] = 'DOC_IGV_TAXABLE_MISMATCH';
        }

        if (! bd_money($docIgvAmount)->isEqualTo($lineIgvSum)) {
            $notes[] = 'DOC_IGV_AMOUNT_MISMATCH';
        }
    }

    $payableText = xpath_text($xpath, '//cac:LegalMonetaryTotal/cbc:PayableAmount');
    if ($payableText !== null) {
        $payable = bd_money($payableText);
        $docTaxTotalMoney = $docTaxTotal !== null ? bd_money($docTaxTotal) : BigDecimal::of('0')->toScale(2);

        $chargeTotalText = xpath_text($xpath, '//cac:LegalMonetaryTotal/cbc:ChargeTotalAmount');
        $chargeTotal = $chargeTotalText !== null ? bd_money($chargeTotalText) : BigDecimal::of('0')->toScale(2);

        $roundingText = xpath_text($xpath, '//cac:LegalMonetaryTotal/cbc:PayableRoundingAmount');
        $rounding = $roundingText !== null ? bd_money($roundingText) : BigDecimal::of('0')->toScale(2);

        $taxableDocSum = xpath_sum_money($xpath, './cac:TaxSubtotal/cbc:TaxableAmount', $docTaxTotalNode);

        $expectedPayable = $taxableDocSum
            ->plus($docTaxTotalMoney)
            ->plus($chargeTotal)
            ->plus($rounding)
            ->toScale(2, RoundingMode::HALF_UP);

        if (! $expectedPayable->isEqualTo($payable)) {
            $notes[] = 'DOC_PAYABLE_MISMATCH';
        }
    }

    return $notes;
}

function buildCreditNoteXmlForConsistencyCheck(array $items, array $dataOverrides, array $affectedOverrides, string $correlative): string
{
    $company = Company::create([
        'company_name' => 'Test Company SAC',
        'ruc' => '20123456789',
        'sol_user' => 'TEST',
        'sol_pass' => 'TEST',
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
        'ubigueo' => '150101',
        'address' => 'AV. TEST 123',
    ]);

    $client = Client::create([
        'name' => 'Juan Perez',
        'trade_name' => null,
        'doc_identity_type' => DocIdentityType::DNI->value,
        'document_number' => '12345678',
        'address' => null,
        'department' => 'LIMA',
        'province' => 'LIMA',
        'district' => 'LIMA',
        'telephone' => null,
        'is_active' => true,
    ]);

    $affected = SaleDocument::create(array_merge([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000018',
        'total_taxed' => 160.93,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 28.97,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 28.97,
        'sale_value' => 160.93,
        'sub_total' => 189.90,
        'total_sale' => 189.90,
        'rounding' => 0,
        'total' => 189.90,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'additional_info' => null,
        'status' => DocumentStatus::APPROVED->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
    ], $affectedOverrides));

    $creditNoteSale = SaleDocument::create(array_merge([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => $correlative,
        'total_taxed' => 0,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 0,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 0,
        'sale_value' => 0,
        'sub_total' => 0,
        'total_sale' => 0,
        'rounding' => 0,
        'total' => 0,
        'date_issue' => now('America/Lima'),
        'date_expiration' => now('America/Lima'),
        'additional_info' => null,
        'status' => DocumentStatus::DRAFT->value,
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sunat_state' => true,
        'affected_sale_document_id' => $affected->id,
        'affected_doc_sunat_type' => DocSunatType::BOLETA->value,
        'affected_serie' => $affected->serie,
        'affected_correlative' => $affected->correlative,
        'note_reason_code' => '01',
        'note_reason_description' => 'Anulación de la operación',
    ], $dataOverrides));

    $saleService = app(SaleService::class);
    $items = collect($items)
        ->map(fn (array $item) => $saleService->normalizeItemDiscountForSunat($item))
        ->all();

    $sunatService = app(SunatService::class);
    $sale = SaleDocument::query()->with(['company', 'client'])->findOrFail($creditNoteSale->id);

    $saleValue = collect($items)->sum(fn (array $item) => (float) ($item['itemValue'] ?? 0));
    $totalIgv = collect($items)->sum(fn (array $item) => (float) ($item['igvAmount'] ?? $item['igv'] ?? 0));
    $total = $saleValue + $totalIgv;

    $data = [
        'ublVersion' => '2.1',
        'docSunatType' => DocSunatType::NOTA_CREDITO->value,
        'operationType' => OperationType::INTERNAL_SALE->value,
        'paymentForm' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => $correlative,
        'dateIssue' => now('America/Lima')->format('Y-m-d H:i:s'),
        'dateExpiration' => now('America/Lima')->format('Y-m-d H:i:s'),
        'affectedSaleDocumentId' => (string) $affected->id,
        'affectedDocSunatType' => DocSunatType::BOLETA->value,
        'affectedSerie' => $affected->serie,
        'affectedCorrelative' => $affected->correlative,
        'noteReasonCode' => '01',
        'noteReasonDescription' => 'Anulación de la operación',
        'items' => $items,
        'totalTaxed' => number_format($saleValue, 2, '.', ''),
        'totalExempted' => '0.00',
        'totalUnaffected' => '0.00',
        'totalExport' => '0.00',
        'totalFree' => '0.00',
        'totalIgv' => number_format($totalIgv, 2, '.', ''),
        'totalIgvFree' => '0.00',
        'icbper' => '0.00',
        'totalTaxes' => number_format($totalIgv, 2, '.', ''),
        'saleValue' => number_format($saleValue, 2, '.', ''),
        'subTotal' => number_format($total, 2, '.', ''),
        'rounding' => '0.00',
        'total' => number_format($total, 2, '.', ''),
        'legends' => [
            ['code' => '1000', 'value' => 'TEST'],
        ],
    ];

    $note = $sunatService->getDocument($data, $sale);

    $builder = new NoteBuilder([
        'template_paths' => [
            resource_path('templates/xml'),
        ],
    ]);

    return $builder->build($note);
}

it('credit note xml passes extended consistency checks (single-line scenario)', function () {
    $items = [
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P001',
            'description' => 'AMPOLLA',
            'unit' => 'NIU',
            'quantity' => '2',
            'unitValue' => '93.14',
            'itemValue' => '139.75',
            'igvBaseAmount' => '139.75',
            'igvPercent' => '18',
            'igvAmount' => '25.16',
            'totalTaxes' => '25.16',
            'discounts' => [],
        ],
    ];

    $xml = buildCreditNoteXmlForConsistencyCheck($items, [], [], '00000010');
    expect(emulateSunatCreditNoteConsistency($xml))->toBe([]);
});

it('credit note xml passes extended consistency checks (multi-line scenario)', function () {
    $items = [
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P001',
            'description' => 'AMPOLLA',
            'unit' => 'NIU',
            'quantity' => '1',
            'unitValue' => '93.14',
            'itemValue' => '82.17',
            'igvBaseAmount' => '82.17',
            'igvPercent' => '18',
            'igvAmount' => '14.79',
            'totalTaxes' => '14.79',
            'discounts' => [],
        ],
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P002',
            'description' => 'JABON DE AZUFRE',
            'unit' => 'NIU',
            'quantity' => '1',
            'unitValue' => '21.19',
            'itemValue' => '18.70',
            'igvBaseAmount' => '18.70',
            'igvPercent' => '18',
            'igvAmount' => '3.37',
            'totalTaxes' => '3.37',
            'discounts' => [],
        ],
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P003',
            'description' => 'MASCARILLA DE ARCILLA VERDE',
            'unit' => 'NIU',
            'quantity' => '1',
            'unitValue' => '29.66',
            'itemValue' => '26.17',
            'igvBaseAmount' => '26.17',
            'igvPercent' => '18',
            'igvAmount' => '4.71',
            'totalTaxes' => '4.71',
            'discounts' => [],
        ],
        [
            'igvAffectationType' => AffecType::GRAVADO->value,
            'code' => 'P004',
            'description' => 'JABON DE CURCUMA',
            'unit' => 'NIU',
            'quantity' => '1',
            'unitValue' => '21.19',
            'itemValue' => '21.19',
            'igvBaseAmount' => '21.19',
            'igvPercent' => '18',
            'igvAmount' => '3.81',
            'totalTaxes' => '3.81',
            'discounts' => [],
        ],
    ];

    $xml = buildCreditNoteXmlForConsistencyCheck($items, [], ['correlative' => '00000188'], '00000011');
    expect(emulateSunatCreditNoteConsistency($xml))->toBe([]);
});
