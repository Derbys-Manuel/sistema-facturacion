<?php

namespace App\Services;

use App\Enums\Sunat\DocIdentityType;
use App\Models\Company as ModelsCompany;
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
    // public function send($data)
    // {
    //     // $company = $data['company'];

    //     $sunat = new SunatService;
    //     // $this->setTotales($data);
    //     $sunat->setLegends($data);
    //     $see = $sunat->getSee();
    //     $invoice = $sunat->getInvoice($data);
    //     $result = $see->send($invoice);

    //     $response['xml'] = $see->getFactory()->getLastXml();
    //     $response['hash'] = (new XmlUtils)->getHashSign($response['xml']);
    //     $response['sunatResponse'] = $sunat->sunatResponse($result);

    //     return $response;
    // }

    public function send(array $data): array
    {
        try {
            $sunat = new SunatService;
            $sunat->setLegends($data);
            $see = $sunat->getSee();
            $invoice = $sunat->getInvoice($data);
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


    // public function getSee($company)
    // {
    //     $certPath = storage_path('app/private/certificado-prueba.pem');
    //     $see = new See();
    //     $see->setCertificate(file_get_contents($certPath));        
    //     $see->setService($company['production'] ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);
    //     $see->setClaveSOL($company['ruc'], $company['sol_user'], $company['sol_pass']);

    //     return $see;
    // }
    
    public function getSee($company = null)
    {
        $certPath = storage_path(config('company.cert_path'));
        $see = new See();
        $see->setCertificate(file_get_contents($certPath));        
        $see->setService(config('company.production') ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);
        $see->setClaveSOL(config('company.ruc'), config('company.sol_user'), config('company.sol_pass'));

        return $see;
    }

    public function getInvoice($data)
    {

        return (new Invoice())
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoOperacion($data['operationType'] ?? null) // Venta - Catalog. 51
            ->setTipoDoc($data['docSunatType'] ?? null) // Factura - Catalog. 01 
            ->setSerie($data['serie'] ?? "B001")
            ->setCorrelativo($data['correlative'] ?? "0001")
            ->setFechaEmision(new DateTime($data['dateIssue'] ?? null)) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda($data['currency'] ?? null) // Sol - Catalog. 02
            ->setCompany($this->getCompany())
            ->setClient($this->getClient($data['client'] ?? null))

            //Mto Operaciones
            ->setMtoOperGravadas($data['totalTaxed'] ?? null)
            ->setMtoOperExoneradas($data['totalExempted'] ?? null)
            ->setMtoOperInafectas($data['totalUnaffected'] ?? null)
            ->setMtoOperExportacion($data['totalExport'] ?? null)
            ->setMtoOperGratuitas($data['totalFree'] ?? null)

            //Impuestos
            ->setMtoIGV($data['totalIgv'])
            ->setMtoIGVGratuitas($data['totalIgvFree'])
            ->setIcbper($data['icbper'])
            ->setTotalImpuestos($data['totalTaxes'])

            //Totales
            ->setValorVenta($data['saleValue'])
            ->setSubTotal($data['subTotal'])
            ->setRedondeo($data['rounding'])
            ->setMtoImpVenta($data['total'])

            //Productos
            ->setDetails($this->getDetails($data['items']))

            //Leyendas
            ->setLegends($this->getLegends($data['legends']));
    }

    // public function getCompany($company)
    // {
    //     return (new Company())
    //         ->setRuc($company['ruc'] ?? null)
    //         ->setRazonSocial($company['companyName'] ?? null)
    //         ->setNombreComercial($company['companyName'] ?? null)
    //         ->setAddress($this->getAddress($company['address']) ?? null);
    // }
    public function getCompany($company = null)
    {
        return (new Company())
            ->setRuc(config('company.ruc') ?? null)
            ->setRazonSocial(config('company.company_name') ?? null)
            ->setNombreComercial(null)
            ->setAddress($this->getAddress() ?? null);
    }


    public function getClient($client = null)
    {
        return (new Client())
            ->setTipoDoc($client['type'] ?? DocIdentityType::DNI->value) // DNI - Catalog. 06
            ->setNumDoc($client['number'] ?? "00000000")
            ->setRznSocial($client['name'] ?? "CLIENTE-VARIOS");
    }

    public function getAddress($address = null)
    {
        // return (new Address())
        //     ->setUbigueo(config('company.ubigueo') ?? null)
        //     ->setDepartamento($address['department'] ?? null)
        //     ->setProvincia($address['province'] ?? null)
        //     ->setDistrito($address['district'] ?? null)
        //     ->setUrbanizacion($address['urbanization'] ?? null)
        //     ->setDireccion($address['direction'] ?? null)
        //     ->setCodLocal($address['codLocal'] ?? null); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.
        return (new Address())
            ->setUbigueo(config('company.ubigueo') ?? null)
            ->setDepartamento(config('company.department') ?? null)
            ->setProvincia(config('company.province') ?? null)
            ->setDistrito(config('company.district') ?? null)
            ->setUrbanizacion(config('company.urbanization') ?? null)
            ->setDireccion(config('company.address') ?? null)
            ->setCodLocal(config('company.cod_local') ?? null); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.

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
