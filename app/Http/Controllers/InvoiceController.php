<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Services\SunatService;
use Greenter\Report\XmlUtils;

class InvoiceController extends Controller
{
    public function send(InvoiceRequest $request)
    {
        $data = $request->validated();
        $company = $data['company'];

        $sunat = new SunatService;
        // $this->setTotales($data);
        $sunat->setLegends($data);

        $see = $sunat->getSee($company);

        $invoice = $sunat->getInvoice($data);

        $result = $see->send($invoice);

        $response['xml'] = $see->getFactory()->getLastXml();
        $response['hash'] = (new XmlUtils)->getHashSign($response['xml']);
        $response['sunatResponse'] = $sunat->sunatResponse($result);

        return $response;
    }

    public function xml(InvoiceRequest $request)
    {
        $data = $request->validated();
        $company = $data['company'];

        $sunat = new SunatService;
        // $sunat->setTotales($data);
        $sunat->setLegends($data);

        $see = $sunat->getSee($company);
        $invoice = $sunat->getInvoice($data);

        $response['xml'] = $see->getXmlSigned($invoice);
        $response['hash'] = (new XmlUtils)->getHashSign($response['xml']);

        return $response;
    }

    public function pdf(InvoiceRequest $request)
    {
        $data = $request->validated();
        $company = $data['company'];

        $sunat = new SunatService;
        // $sunat->setTotales($data);
        $sunat->setLegends($data);

        $see = $sunat->getSee($company);
        $invoice = $sunat->getInvoice($data);

        return $sunat->getHtmlReport($invoice);
    }
}
