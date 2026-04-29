<?php

namespace App\Services;

use App\Enums\Sunat\DocIdentityType;
use App\Livewire\Forms\SaleForm;
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
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;
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
            return [
                'success' => true,
                'xml' => $xml,
                'hash' => (new XmlUtils)->getHashSign($xml),
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
        return (new Invoice())
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
    public function getClient(ClientModels $client = null)
    {
        return (new Client())
            ->setTipoDoc($client->doc_identity_type->value ?? DocIdentityType::DNI->value) // DNI - Catalog. 06
            ->setNumDoc($client->document_number ?? "00000000")
            ->setRznSocial($client->name ?? $client->trade_name ?? "CLIENTE-VARIOS");
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
    public function getDetails($details)
    {
        $green_details = [];
        foreach ($details as $detail) {
            $green_details[] = (new SaleDetail())
                ->setCodProducto($detail['code'] ?? null)
                ->setUnidad($detail['unit'] ?? 'NIU')
                ->setCantidad((float) ($detail['quantity'] ?? 0))
                ->setDescripcion($detail['description'] ?? '')
                ->setMtoValorUnitario((float) ($detail['unitValue'] ?? 0))
                ->setMtoValorVenta((float) ($detail['itemValue'] ?? 0))
                ->setMtoPrecioUnitario((float) ($detail['unitPrice'] ?? 0))
                ->setMtoBaseIgv((float) ($detail['igvBaseAmount'] ?? 0))
                ->setPorcentajeIgv((float) ($detail['igvPercent'] ?? 0))
                ->setIgv((float) ($detail['igvAmount'] ?? 0))
                ->setTipAfeIgv($detail['igvAffectationType'] ?? null)
                ->setFactorIcbper((float) ($detail['icbperFactor'] ?? 0))
                ->setIcbper((float) ($detail['icbperAmount'] ?? 0))
                ->setTotalImpuestos((float) ($detail['taxesTotal'] ?? 0));
        }
        return $green_details;
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

    public function getHtmlReport($invoice){
        $report = new HtmlReport();
        $resolver = new DefaultTemplateResolver();
        $report->setTemplate($resolver->getTemplate($invoice));
        $params = [
            'system' => [
                // 'logo' => Storage::get($company->logo_path), // Logo de Empresa
                'hash' => 'qqnr2dN4p/HmaEA/CJuVGo7dv5g=', // Valor Resumen 
            ],
            'user' => [
                'header'     => 'Telf: <b>(01) 123375</b>', // Texto que se ubica debajo de la dirección de empresa
                'extras'     => [
                    // Leyendas adicionales
                    ['name' => 'CONDICION DE PAGO', 'value' => 'Efectivo'     ],
                    ['name' => 'VENDEDOR'         , 'value' => 'GITHUB SELLER'],
                ],
                'footer' => '<p>Nro Resolucion: <b>3232323</b></p>'
            ]
        ];
        return $report->render($invoice, $params);
    }
    public function generatePdfReport($invoice)
    {
        $htmlReport = new HtmlReport();
        $resolver = new DefaultTemplateResolver();
        $htmlReport->setTemplate($resolver->getTemplate($invoice));
        $report = new PdfReport($htmlReport);
        // Options: Ver mas en https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        $report->setOptions( [
            'no-outline',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
        ]);
        $report->setBinPath(env('WKHTML_PDF_PATH'));
        $params = [
            'system' => [
                // 'logo' => Storage::get($company->logo_path), // Logo de Empresa
                'hash' => 'qqnr2dN4p/HmaEA/CJuVGo7dv5g=', // Valor Resumen 
            ],
            'user' => [
                'header'     => 'Telf: <b>(01) 123375</b>', // Texto que se ubica debajo de la dirección de empresa
                'extras'     => [
                    // Leyendas adicionales
                    ['name' => 'CONDICION DE PAGO', 'value' => 'Efectivo'     ],
                    ['name' => 'VENDEDOR'         , 'value' => 'GITHUB SELLER'],
                ],
                'footer' => '<p>Nro Resolucion: <b>3232323</b></p>'
            ]
        ];
        $pdf = $report->render($invoice, $params);
        Storage::put('invoices/' . $invoice->getName() . '.pdf', $pdf);
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
