<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IdentityDiurvanService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.diurvan.url'), '/');
        $this->apiKey = config('services.diurvan.key');
    }

    public function searchDni(string $dni): array
    {
        return $this->request('/dni/' . $dni);
    }

    public function searchRuc(string $ruc): array
    {
        return $this->request('/ruc/' . $ruc);
    }

    private function request(string $endpoint): array
    {
        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->get($this->baseUrl . $endpoint);

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
}