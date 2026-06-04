<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

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

        $cacheKey = 'identity-document:'.hash('sha256', $this->baseUrl.'|'.$document);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = str_contains($this->baseUrl, 'api.decolecta.com')
                ? $this->client()->get($this->baseUrl.'/ruc', ['numero' => $document])
                : $this->client()->asJson()->post($this->baseUrl.'/dniruc', ['documento' => $document]);
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'message' => 'No se pudo conectar con la API de identidad.',
                'status' => 503,
                'data' => null,
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'No se pudo consultar la API.',
                'status' => $response->status(),
                'data' => null,
            ];
        }

        $result = [
            'success' => (bool) $response->json('success', true),
            'message' => $response->json('message'),
            'status' => $response->status(),
            'data' => $response->json(),
        ];

        if (! $result['success']) {
            return $result;
        }

        Cache::put($cacheKey, $result, now()->addHours(12));

        return $result;
    }

    private function client(): PendingRequest
    {
        return Http::connectTimeout(3)
            ->timeout(10)
            ->retry(
                times: 2,
                sleepMilliseconds: 250,
                when: fn (Throwable $exception): bool => $exception instanceof ConnectionException,
                throw: true,
            )
            ->acceptJson()
            ->withToken($this->apiKey);
    }
}
