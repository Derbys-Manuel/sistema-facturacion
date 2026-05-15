<?php

namespace App\Services;

use App\Enums\Sunat\DocIdentityType;
use Greenter\Model\Sale\Charge;
use App\Models\SaleDocument;
use App\Models\Client as ClientModels;
use App\Models\Company as CompanyModels;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Luecano\NumeroALetras\NumeroALetras;
use Greenter\Report\XmlUtils;

class SunatService
{
    public function send(array $data, SaleDocument $sale): array
    {
        try {
            $sunat = new SunatService;
            $sunat->setLegends($data);
            $see = $sunat->getSee($sale->company);
            $invoice = $sunat->getInvoice($data, $sale);
            $result = $see->send($invoice);
            $xml = $see->getFactory()->getLastXml();
            $hash = (new XmlUtils)->getHashSign($xml);
            return [
                'success' => true,
                'xml' => $xml,
                'hash' => $hash,
                'pdfUrl' => route('sale.pdf', $sale->id),
                'sunatResponse' => $sunat->sunatResponse($result),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'xml' => null,
                'hash' => null,
                'sunatResponse' => [
                    'success' => false,
                    'error' => [
                        'code' => 'CONNECTION_ERROR',
                        'message' => $e->getMessage(),
                    ],
                ],
                'error' => $e->getMessage(),
            ];
        }
    }
    public function getSee(CompanyModels $company)
    {
        $certPath = storage_path($company->cert_path);
        $see = new See();
        $see->setCertificate(file_get_contents($certPath));        
        $see->setService($company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);
        $see->setClaveSOL($company->ruc, $company->sol_user, $company->sol_pass);
        return $see;
    }

    public function getInvoice($data, SaleDocument $sale)
    {
        $invoice = (new Invoice())
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoOperacion($data['operationType'] ?? null) // Venta - Catalog. 51
            ->setTipoDoc($data['docSunatType'] ?? null) // Factura - Catalog. 01 
            ->setSerie($data['serie'] ?? null)
            ->setCorrelativo($data['correlative'] ?? null)
            ->setFechaEmision(new DateTime($data['dateIssue'] ?? null)) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda($data['currency'] ?? null) // Sol - Catalog. 02
            ->setCompany($this->getCompany($sale->company))
            ->setClient($this->getClient($sale->client ?? null))
            ->setMtoOperGravadas($data['totalTaxed'] ?? null)
            ->setMtoOperExoneradas($data['totalExempted'] ?? null)
            ->setMtoOperInafectas($data['totalUnaffected'] ?? null)
            ->setMtoOperExportacion($data['totalExport'] ?? null)
            ->setMtoOperGratuitas($data['totalFree'] ?? null)
            ->setMtoIGV($data['totalIgv'])
            ->setMtoIGVGratuitas($data['totalIgvFree'])
            ->setIcbper($data['icbper'])
            ->setTotalImpuestos($data['totalTaxes'])
            ->setValorVenta($data['saleValue'])
            ->setSubTotal($data['subTotal'])
            ->setRedondeo($data['rounding'])
            ->setMtoImpVenta($data['total'])
            ->setDetails($this->getDetails($data['items']))
            ->setObservacion($data['additionalInfo'] ?? null)
            ->setLegends($this->getLegends($data['legends']));
             $documentDiscounts = $data['discounts'] ?? [];
            if (!empty($documentDiscounts)) {
                $invoice->setDescuentos($this->mapDiscountsToCharges($documentDiscounts));
            }
        return $invoice;
    }
    public function getNote($data, SaleDocument $sale){
        return(new Note())
        ->setUblVersion($data['ublVersion'] ?? '2.1')
        ->setTipoDoc($data['docSunatType'] ?? null) // Factura - Catalog. 01 
        ->setSerie($data['serie'] ?? null)
        ->setCorrelativo($data['correlative'] ?? null)
        ->setFechaEmision(new DateTime($data['dateIssue'] ?? null)) // Zona horaria: Lima
        ->setTipDocAfectado($data['docAfectType'] ?? null)
        ->setNumDocfectado($data['docNumAfect'] ?? null)
        ->setCodMotivo($data['reasonCode'] ?? null)
        ->setTipoMoneda($data['currency'] ?? null) // Sol - Catalog. 02
        ->setCompany($this->getCompany($sale->company))
        ->setClient($this->getClient($sale->client ?? null))
        ->setMtoOperGravadas($data['totalTaxed'] ?? null)
        ->setMtoOperExoneradas($data['totalExempted'] ?? null)
        ->setMtoOperInafectas($data['totalUnaffected'] ?? null)
        ->setMtoOperExportacion($data['totalExport'] ?? null)
        ->setMtoOperGratuitas($data['totalFree'] ?? null)
        ->setMtoIGV($data['totalIgv'])
        ->setMtoIGVGratuitas($data['totalIgvFree'])
        ->setIcbper($data['icbper'])
        ->setTotalImpuestos($data['totalTaxes'])
        ->setValorVenta($data['saleValue'])
        ->setSubTotal($data['subTotal'])
        ->setRedondeo($data['rounding'])
        ->setMtoImpVenta($data['total'])
        ->setDetails($this->getDetails($data['items']))
        ->setLegends($this->getLegends($data['legends']));
    }
    public function getCompany(CompanyModels $company)
    {
        return (new Company())
            ->setRuc($company->ruc ?? null)
            ->setRazonSocial($company->company_name ?? null)
            ->setNombreComercial($company->company_name ?? null)
            ->setAddress($this->getAddress($company) ?? null);
    }
    public function getClient(?ClientModels $client = null): Client
    {
        $address = (new Address())
        ->setDireccion(
            $client?->address
            ?? null
        );
        if ($client === null) {
            return (new Client())
                ->setTipoDoc(DocIdentityType::DNI->value)
                ->setNumDoc('00000000')
                ->setRznSocial('CLIENTE-VARIOS');
        }
        return (new Client())
            ->setTipoDoc($client->doc_identity_type->value ?? DocIdentityType::DNI->value)
            ->setNumDoc($client->document_number ?? '00000000')
            ->setRznSocial($client->name ?? $client->trade_name ?? 'CLIENTE-VARIOS')
            ->setAddress($address)
            ->setTelephone($client->telephone ?? null);
    }


    public function getAddress(CompanyModels $company)
    {
        return (new Address())
            ->setUbigueo($company->ubigueo ?? null)
            ->setDepartamento($company->department ?? null)
            ->setProvincia($company->province ?? null)
            ->setDistrito($company->district ?? null)
            ->setUrbanizacion($company->urbanization ?? null)
            ->setDireccion($company->address ?? null)
            ->setCodLocal($company->cod_local ?? null); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.
    }
    public function getDetails(array $details): array
    {
        $greenDetails = [];
        foreach ($details as $detail) {
            $discountAmount = (float) data_get($detail, 'discounts.0.discountAmount', 0);
            $precioUnitarioOperacion = $discountAmount > 0
                ? (float) ($detail['unitPriceWithDiscount'] ?? $detail['unitPrice'] ?? 0)
                : (float) ($detail['unitPrice'] ?? 0);
            $saleDetail = (new SaleDetail())
                ->setCodProducto((string) ($detail['code'] ?? '00000'))
                ->setUnidad((string) ($detail['unit'] ?? 'NIU'))
                ->setCantidad((float) ($detail['quantity'] ?? 1))
                ->setDescripcion((string) ($detail['description'] ?? 'PRODUCTO'))
                ->setMtoValorUnitario((float) ($detail['unitValue'] ?? 0))
                ->setMtoValorVenta((float) ($detail['itemValue'] ?? $detail['saleValue'] ?? 0))
                ->setMtoPrecioUnitario($precioUnitarioOperacion)
                ->setMtoBaseIgv((float) ($detail['igvBaseAmount'] ?? 0))
                ->setPorcentajeIgv((float) ($detail['igvPercent'] ?? 18))
                ->setIgv((float) ($detail['igvAmount'] ?? $detail['igv'] ?? 0))
                ->setTipAfeIgv((string) ($detail['igvAffectationType'] ?? '10'))
                ->setFactorIcbper((float) ($detail['icbperFactor'] ?? 0))
                ->setIcbper((float) ($detail['icbperAmount'] ?? 0))
                ->setTotalImpuestos((float) ($detail['taxesTotal'] ?? $detail['totalTaxes'] ?? 0));
            $itemDiscounts = $detail['discounts'] ?? [];
            if (! empty($itemDiscounts) && $discountAmount > 0) {
                $saleDetail->setDescuentos(
                    $this->mapDiscountsToCharges($itemDiscounts)
                );
            }
            $greenDetails[] = $saleDetail;
        }

        return $greenDetails;
    }
    public function mapDiscountToCharge(array $discount): Charge
    {
        $baseAmount = ($discount['baseAmount'] ?? 0);
        $factorPorcentage = ($discount['factorPorcentage'] ?? 0);
        $discountAmount = ($discount['discountAmount'] ?? 0);
        $factor = $factorPorcentage;
        return (new Charge())
            ->setCodTipo((string) ($discount['type'] ?? '00'))
            ->setMontoBase($baseAmount)
            ->setFactor($factor)
            ->setMonto($discountAmount);
    }
    public function mapDiscountsToCharges(array $discounts): array
    {
        $charges = [];
        foreach ($discounts as $discount) {
            $charges[] = $this->mapDiscountToCharge($discount);
        }
        return $charges;
    }

    public function getLegends($legends)
    {
        $green_legends = [];
        foreach ($legends as $legend) {
            $green_legends[] = (new Legend())
                ->setCode($legend['code'] ?? null) // Monto en letras - Catalog. 52
                ->setValue($legend['value'] ?? null);
        }
        return $green_legends;
    }
    public function sunatResponse($result)
    {
        $response['success'] = $result->isSuccess();
        // Verificamos que la conexión con SUNAT fue exitosa.
        if (!$response['success']) {
            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];
            return $response;
        }
        $response['cdrZip'] = base64_encode($result->getCdrZip());
        $cdr = $result->getCdrResponse();
        $response['cdrResponse'] = [
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes()
        ];
        return $response;
    }

    public function getHtmlReport(Invoice $invoice, ?CompanyModels $company = null, ?string $hash = null): string
    {
        $report = new HtmlReport(resource_path('templates'));
        $resolver = new DefaultTemplateResolver();
        $report->setTemplate($resolver->getTemplate($invoice));
        return $report->render($invoice, $this->reportParams($company, $hash, $invoice->getObservacion()));
    }
    public function generatePdfReport(Invoice $invoice, ?CompanyModels $company = null, ?string $hash = null): string
    {
        $htmlReport = new HtmlReport(resource_path('templates'));
        $resolver = new DefaultTemplateResolver();
        $htmlReport->setTemplate($resolver->getTemplate($invoice));
        $report = new PdfReport($htmlReport);
        $report->setOptions( [
            'no-outline',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
        ]);
        $report->setBinPath(env('WKHTML_PDF_PATH'));
        $pdf = $report->render($invoice, $this->reportParams($company, $hash, $invoice->getObservacion()));

        if ($pdf === null) {
            throw new \RuntimeException('No se pudo generar el PDF. Verifique `WKHTML_PDF_PATH` y que wkhtmltopdf este instalado.');
        }

        return $pdf;
        // Storage::put('invoices/' . $invoice->getName() . '.pdf', $pdf);
    }

    private function reportParams(
        ?CompanyModels $company = null,
        ?string $hash = null,
        ?string $additionalInfo = null,
    ): array {
        $logo = '';
        if ($company && filled($company->logo_path)) {
            $logoPath = storage_path($company->logo_path);
            if (file_exists($logoPath)) {
                $logo = file_get_contents($logoPath);
            }
        }
        return [
            'system' => [
                'logo' => $logo,
                // 'hash' => $hash,
            ],
            'user' => [
                // 'header' => 'Telf: <b>-</b>',
                'extras' => array_filter([
                    ['name' => 'CONDICION DE PAGO', 'value' => 'Contado'],

                    filled($additionalInfo)
                        ? ['name' => 'OBSERVACIÓN', 'value' => $additionalInfo]
                        : null,
                ]),
                // 'footer' => '<p>Nro Resolucion: <b>3232323</b></p>',
            ],
        ];
    }
    public function setLegends(&$data)
    {
        $formatter = new NumeroALetras;
        $data['legends'] = [
            [
                'code' => '1000',
                'value' => $formatter->toInvoice($data['total'], 2, 'SOLES'),
            ],
        ];
    }
}
