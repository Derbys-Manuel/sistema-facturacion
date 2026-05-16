<?php

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
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Greenter\Xml\Builder\NoteBuilder;

function bd(string|int|float $value): BigDecimal
{
    return BigDecimal::of((string) $value);
}

function money(BigDecimal $value): string
{
    return (string) $value->toScale(2, RoundingMode::HALF_UP);
}

/**
 * Emula las validaciones básicas de SUNAT que originan las observaciones:
 * - 4288: LineExtensionAmount debe cuadrar con (PrecioUnitario * Cantidad) - Descuentos (AllowanceCharge false)
 * - 4028: PayableAmount de la NC <= total del comprobante afectado
 *
 * Devuelve un array de "observaciones" (strings), vacío = sin observaciones.
 */
function emulateSunatCreditNoteObservations(string $xml, string $affectedTotal): array
{
    $dom = new DOMDocument();
    $dom->loadXML($xml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

    $notes = [];

    $payableNode = $xpath->query('//cac:LegalMonetaryTotal/cbc:PayableAmount')->item(0);
    if ($payableNode instanceof DOMElement) {
        $payable = bd(trim($payableNode->nodeValue))->toScale(2, RoundingMode::HALF_UP);
        $affected = bd($affectedTotal)->toScale(2, RoundingMode::HALF_UP);
        if ($payable->isGreaterThan($affected)) {
            $notes[] = '4028';
        }
    }

    /** @var DOMElement $line */
    foreach ($xpath->query('//cac:CreditNoteLine') as $line) {
        $lineIdNode = $xpath->query('./cbc:ID', $line)->item(0);
        $lineId = $lineIdNode instanceof DOMElement ? trim($lineIdNode->nodeValue) : '?';

        $qtyNode = $xpath->query('./cbc:CreditedQuantity', $line)->item(0);
        $qty = $qtyNode instanceof DOMElement ? bd(trim($qtyNode->nodeValue)) : bd('0');

        $unitValueNode = $xpath->query('./cac:Price/cbc:PriceAmount', $line)->item(0);
        $unitValue = $unitValueNode instanceof DOMElement ? bd(trim($unitValueNode->nodeValue)) : bd('0');

        $lineExtNode = $xpath->query('./cbc:LineExtensionAmount', $line)->item(0);
        $lineExt = $lineExtNode instanceof DOMElement ? bd(trim($lineExtNode->nodeValue)) : bd('0');

        $discountTotal = bd('0');
        foreach ($xpath->query('./cac:AllowanceCharge[cbc:ChargeIndicator="false"]/cbc:Amount', $line) as $amountNode) {
            if ($amountNode instanceof DOMElement) {
                $discountTotal = $discountTotal->plus(bd(trim($amountNode->nodeValue)));
            }
        }

        $base = $unitValue->multipliedBy($qty)->toScale(2, RoundingMode::HALF_UP);
        $expected = $base->minus($discountTotal)->toScale(2, RoundingMode::HALF_UP);
        $actual = $lineExt->toScale(2, RoundingMode::HALF_UP);

        if (! $expected->isEqualTo($actual)) {
            $notes[] = "4288:L{$lineId} expected=".money($expected).' actual='.money($actual);
        }
    }

    return $notes;
}

it('emulates SUNAT for a credit note (1 line) and gets no observations (4028/4288)', function () {
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

    $affected = SaleDocument::create([
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
    ]);

    $creditNoteSale = SaleDocument::create([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => '00000001',
        'total_taxed' => 139.75,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 25.16,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 25.16,
        'sale_value' => 139.75,
        'sub_total' => 164.91,
        'total_sale' => 164.91,
        'rounding' => 0,
        'total' => 164.91,
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
    ]);

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

    $saleService = app(SaleService::class);
    $items = collect($items)
        ->map(fn (array $item) => $saleService->normalizeItemDiscountForSunat($item))
        ->all();

    $sunatService = app(SunatService::class);
    $sale = SaleDocument::query()->with(['company', 'client'])->findOrFail($creditNoteSale->id);

    $data = [
        'ublVersion' => '2.1',
        'docSunatType' => DocSunatType::NOTA_CREDITO->value,
        'operationType' => OperationType::INTERNAL_SALE->value,
        'paymentForm' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => '00000001',
        'dateIssue' => now('America/Lima')->format('Y-m-d H:i:s'),
        'dateExpiration' => now('America/Lima')->format('Y-m-d H:i:s'),
        'affectedSaleDocumentId' => (string) $affected->id,
        'affectedDocSunatType' => DocSunatType::BOLETA->value,
        'affectedSerie' => $affected->serie,
        'affectedCorrelative' => $affected->correlative,
        'noteReasonCode' => '01',
        'noteReasonDescription' => 'Anulación de la operación',
        'items' => $items,
        'totalTaxed' => '139.75',
        'totalExempted' => '0.00',
        'totalUnaffected' => '0.00',
        'totalExport' => '0.00',
        'totalFree' => '0.00',
        'totalIgv' => '25.16',
        'totalIgvFree' => '0.00',
        'icbper' => '0.00',
        'totalTaxes' => '25.16',
        'saleValue' => '139.75',
        'subTotal' => '164.91',
        'rounding' => '0.00',
        'total' => '164.91',
        'legends' => [
            ['code' => '1000', 'value' => 'CIENTO SESENTA Y CUATRO CON 91/100 SOLES'],
        ],
    ];

    $note = $sunatService->getDocument($data, $sale);

    $builder = new NoteBuilder([
        'template_paths' => [
            resource_path('templates/xml'),
        ],
    ]);
    $xml = $builder->build($note);

    $observations = emulateSunatCreditNoteObservations($xml, '189.90');

    expect($observations)->toBe([]);
});

it('emulates SUNAT for a credit note (multiple lines) and gets no observations (4028/4288)', function () {
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

    $affected = SaleDocument::create([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::BOLETA->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'B001',
        'correlative' => '00000188',
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
    ]);

    $creditNoteSale = SaleDocument::create([
        'document_type' => DocumentType::SALE->value,
        'ubl_version' => '2.1',
        'doc_sunat_type' => DocSunatType::NOTA_CREDITO->value,
        'operation_type' => OperationType::INTERNAL_SALE->value,
        'payment_form' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => '00000002',
        'total_taxed' => 148.23,
        'total_exempted' => 0,
        'total_unaffected' => 0,
        'total_export' => 0,
        'total_free' => 0,
        'total_igv' => 26.68,
        'total_igv_free' => 0,
        'icbper' => 0,
        'total_taxes' => 26.68,
        'sale_value' => 148.23,
        'sub_total' => 174.91,
        'total_sale' => 174.91,
        'rounding' => 0,
        'total' => 174.91,
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
    ]);

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

    $saleService = app(SaleService::class);
    $items = collect($items)
        ->map(fn (array $item) => $saleService->normalizeItemDiscountForSunat($item))
        ->all();

    $sunatService = app(SunatService::class);
    $sale = SaleDocument::query()->with(['company', 'client'])->findOrFail($creditNoteSale->id);

    $data = [
        'ublVersion' => '2.1',
        'docSunatType' => DocSunatType::NOTA_CREDITO->value,
        'operationType' => OperationType::INTERNAL_SALE->value,
        'paymentForm' => PaymentForm::CASH->value,
        'currency' => 'PEN',
        'serie' => 'BC01',
        'correlative' => '00000002',
        'dateIssue' => now('America/Lima')->format('Y-m-d H:i:s'),
        'dateExpiration' => now('America/Lima')->format('Y-m-d H:i:s'),
        'affectedSaleDocumentId' => (string) $affected->id,
        'affectedDocSunatType' => DocSunatType::BOLETA->value,
        'affectedSerie' => $affected->serie,
        'affectedCorrelative' => $affected->correlative,
        'noteReasonCode' => '01',
        'noteReasonDescription' => 'Anulación de la operación',
        'items' => $items,
        'totalTaxed' => '148.23',
        'totalExempted' => '0.00',
        'totalUnaffected' => '0.00',
        'totalExport' => '0.00',
        'totalFree' => '0.00',
        'totalIgv' => '26.68',
        'totalIgvFree' => '0.00',
        'icbper' => '0.00',
        'totalTaxes' => '26.68',
        'saleValue' => '148.23',
        'subTotal' => '174.91',
        'rounding' => '0.00',
        'total' => '174.91',
        'legends' => [
            ['code' => '1000', 'value' => 'CIENTO SETENTA Y CUATRO CON 91/100 SOLES'],
        ],
    ];

    $note = $sunatService->getDocument($data, $sale);

    $builder = new NoteBuilder([
        'template_paths' => [
            resource_path('templates/xml'),
        ],
    ]);
    $xml = $builder->build($note);

    $observations = emulateSunatCreditNoteObservations($xml, '189.90');

    expect($observations)->toBe([]);
});
