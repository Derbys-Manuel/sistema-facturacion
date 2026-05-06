<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IdentityDiurvanService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.diurvan.url'), '/');
        $this->apiKey = (string) config('services.diurvan.key');
    }

    public function searchDni(string $dni): array
    {
        return $this->request($dni);
    }

    public function searchRuc(string $ruc): array
    {
        return $this->request($ruc);
    }

    private function request(string $document): array
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            return [
                'success' => false,
                'message' => 'Falta configurar el servicio de identidad (URL o API key).',
                'status' => 500,
                'data' => null,
            ];
        }

        // Soportamos dos formatos:
        // - Diurvan: POST {baseUrl}/dniruc {documento: "..."} con Bearer token
        // - Decolecta (si se configura baseUrl): GET {baseUrl}/ruc?numero=... con Bearer token
        if (str_contains($this->baseUrl, 'api.decolecta.com')) {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($this->apiKey)
                ->get($this->baseUrl.'/ruc', [
                    'numero' => $document,
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $response->json('message') ?? 'No se pudo consultar la API.',
                    'status' => $response->status(),
                    'data' => null,
                ];
            }

            return [
                'success' => true,
                'message' => null,
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->asJson()
            ->withToken($this->apiKey)
            ->post($this->baseUrl.'/dniruc', [
                'documento' => $document,
            ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'No se pudo consultar la API.',
                'status' => $response->status(),
                'data' => null,
            ];
        }

        $jsonSuccess = (bool) $response->json('success', true);

        if (! $jsonSuccess) {
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'No se pudo consultar la API.',
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        }

        return [
            'success' => true,
            'message' => null,
            'status' => $response->status(),
            'data' => $response->json(),
        ];
    }
}
