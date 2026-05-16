<?php

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Charge;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Xml\Builder\NoteBuilder;

it('renders credit note xml with line allowance charges from custom templates', function () {
    $company = (new Company())
        ->setRuc('20123456789')
        ->setRazonSocial('Test Company SAC')
        ->setNombreComercial('Test Company SAC')
        ->setAddress(
            (new Address())
                ->setUbigueo('150101')
                ->setDepartamento('LIMA')
                ->setProvincia('LIMA')
                ->setDistrito('LIMA')
                ->setUrbanizacion('-')
                ->setDireccion('AV. TEST 123')
                ->setCodLocal('0000')
        );

    $client = (new Client())
        ->setTipoDoc('1')
        ->setNumDoc('12345678')
        ->setRznSocial('Juan Perez');

    $discount = (new Charge())
        ->setCodTipo('00')
        ->setFactor(0.32131)
        ->setMonto(59.85)
        ->setMontoBase(186.27);

    $detail = (new SaleDetail())
        ->setUnidad('NIU')
        ->setCantidad(2)
        ->setDescripcion('Producto')
        ->setMtoValorUnitario(93.14)
        ->setMtoValorVenta(126.42)
        ->setMtoPrecioUnitario(74.59)
        ->setMtoBaseIgv(126.42)
        ->setPorcentajeIgv(18)
        ->setIgv(22.76)
        ->setTipAfeIgv('10')
        ->setTotalImpuestos(22.76)
        ->setDescuentos([$discount]);

    $note = (new Note())
        ->setUblVersion('2.1')
        ->setTipoDoc('07')
        ->setSerie('BC01')
        ->setCorrelativo('00000001')
        ->setFechaEmision(new DateTime('2026-05-16 12:00:00'))
        ->setTipDocAfectado('03')
        ->setNumDocfectado('B001-00000203')
        ->setCodMotivo('01')
        ->setDesMotivo('Anulación de la operación')
        ->setTipoMoneda('PEN')
        ->setCompany($company)
        ->setClient($client)
        ->setMtoOperGravadas(160.93)
        ->setMtoIGV(28.97)
        ->setTotalImpuestos(28.97)
        ->setValorVenta(160.93)
        ->setSubTotal(189.90)
        ->setMtoImpVenta(189.90)
        ->setDetails([$detail])
        ->setLegends([
            (new Legend())->setCode('1000')->setValue('CIENTO OCHENTA Y NUEVE CON 90/100 SOLES'),
        ]);

    $builder = new NoteBuilder([
        'template_paths' => [
            resource_path('templates/xml'),
        ],
    ]);

    $xml = $builder->build($note);

    expect($xml)->toContain('<cac:AllowanceCharge>');
    expect($xml)->toContain('<cbc:ChargeIndicator>false</cbc:ChargeIndicator>');
    expect($xml)->toContain('<cbc:BaseAmount currencyID="PEN">186.27</cbc:BaseAmount>');
});
