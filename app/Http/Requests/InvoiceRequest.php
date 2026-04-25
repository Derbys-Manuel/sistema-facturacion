<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ublVersion' => 'nullable|string',
            'tipoDoc' => 'required|string',
            'tipoOperacion' => 'required|string',
            'serie' => 'required|string',
            'correlativo' => 'required|string',
            'fechaEmision' => 'required|string',
            'tipoMoneda' => 'required|string',

            'formaPago' => 'required|array',
            'formaPago.moneda' => 'required|string',
            'formaPago.tipo' => 'required|string',

            'company' => 'required|array',
            'company.ruc' => 'required',
            'company.razonSocial' => 'required|string',
            'company.nombreComercial' => 'nullable|string',
            'company.sol_user' => 'required|string',
            'company.sol_pass' => 'required|string',
            'company.production' => 'required|boolean',
            'company.address' => 'required|array',
            'company.address.ubigueo' => 'required|string',
            'company.address.departamento' => 'required|string',
            'company.address.provincia' => 'required|string',
            'company.address.distrito' => 'required|string',
            'company.address.urbanizacion' => 'nullable|string',
            'company.address.direccion' => 'required|string',
            'company.address.codLocal' => 'required|string',

            'client' => 'required|array',
            'client.tipoDoc' => 'required|string',
            'client.numDoc' => 'required',
            'client.rznSocial' => 'required|string',

            'details' => 'required|array|min:1',
            'details.*' => 'required|array',
            'details.*.tipAfeIgv' => 'required',
            'details.*.codProducto' => 'required|string',
            'details.*.unidad' => 'required|string',
            'details.*.descripcion' => 'required|string',
            'details.*.cantidad' => 'required|numeric|min:1',
            'details.*.mtoValorUnitario' => 'required|numeric',
            'details.*.mtoValorVenta' => 'required|numeric',
            'details.*.mtoBaseIgv' => 'required|numeric',
            'details.*.porcentajeIgv' => 'required|numeric',
            'details.*.igv' => 'required|numeric',
            'details.*.totalImpuestos' => 'required|numeric',
            'details.*.mtoPrecioUnitario' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'company.required' => 'La empresa es obligatoria',
            'company.array' => 'La empresa debe ser un objeto válido',

            'company.ruc.required' => 'El RUC de la empresa es obligatorio',
            'company.razonSocial.required' => 'La razón social es obligatoria',
            'company.sol_user.required' => 'El usuario SOL es obligatorio',
            'company.sol_pass.required' => 'La clave SOL es obligatoria',
            'company.production.required' => 'El indicador production es obligatorio',
            'company.production.boolean' => 'production debe ser true o false',

            'company.address.required' => 'La dirección de la empresa es obligatoria',
            'company.address.array' => 'La dirección debe ser un objeto válido',

            'client.required' => 'El cliente es obligatorio',
            'client.array' => 'El cliente debe ser un objeto válido',
            'client.tipoDoc.required' => 'El tipo de documento del cliente es obligatorio',
            'client.numDoc.required' => 'El número de documento del cliente es obligatorio',
            'client.rznSocial.required' => 'La razón social del cliente es obligatoria',

            'details.required' => 'Los detalles son obligatorios',
            'details.array' => 'Los detalles deben ser un arreglo',
            'details.min' => 'Debe existir al menos un detalle',

            'details.*.required' => 'Cada item de detalle debe ser un objeto válido',
            'details.*.array' => 'Cada item de detalle debe ser un objeto',
            'details.*.descripcion.required' => 'La descripción del item es obligatoria',
            'details.*.cantidad.required' => 'La cantidad del item es obligatoria',
            'details.*.mtoPrecioUnitario.required' => 'El precio unitario del item es obligatorio',
        ];
    }
}
