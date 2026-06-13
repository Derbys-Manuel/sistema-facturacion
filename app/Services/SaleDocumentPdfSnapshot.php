<?php

namespace App\Services;

use App\Models\Client;
use App\Models\SaleDocument;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use JsonException;

class SaleDocumentPdfSnapshot
{
    public function __construct(private CompanyCache $companyCache) {}

    public function store(SaleDocument $sale, array $data): string
    {
        $company = $this->companyCache->findOrFail((string) $sale->company_id);
        $client = $sale->relationLoaded('client')
            ? $sale->client
            : (filled($sale->client_id) ? Client::query()->find($sale->client_id) : null);

        return $this->write($sale, $data, $company->getAttributes(), $client?->getAttributes());
    }

    public function storeFromDatabase(string $saleId): string
    {
        if ($this->exists($saleId)) {
            return $this->pathFor($saleId);
        }

        $sale = SaleDocument::query()
            ->with(['items.discounts', 'discounts', 'client'])
            ->findOrFail($saleId);

        $data = $sale->toArray();
        $rawDateIssue = $sale->getRawOriginal('date_issue');

        if ($rawDateIssue instanceof DateTimeInterface || (is_string($rawDateIssue) && $rawDateIssue !== '')) {
            $localDateIssue = $rawDateIssue instanceof DateTimeInterface
                ? $rawDateIssue->format('Y-m-d H:i:s')
                : str_replace('T', ' ', substr($rawDateIssue, 0, 19));

            $data['dateIssue'] = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $localDateIssue,
                'America/Lima',
            )->toIso8601String();
        }

        return $this->store($sale, $data);
    }

    private function write(SaleDocument $sale, array $data, array $company, ?array $client): string
    {
        $path = $this->pathFor((string) $sale->id);

        Storage::disk('local')->put($path, json_encode([
            'version' => 1,
            'sale' => $sale->getAttributes(),
            'data' => collect($data)->except(['company', 'client'])->all(),
            'company' => $company,
            'client' => $client,
        ], JSON_THROW_ON_ERROR));

        return $path;
    }

    public function pathFor(string $saleId): string
    {
        return "sale-document-snapshots/{$saleId}.json";
    }

    public function exists(string $saleId): bool
    {
        return Storage::disk('local')->exists($this->pathFor($saleId));
    }

    public function getForSale(string $saleId): array
    {
        return $this->get($this->pathFor($saleId));
    }

    /**
     * @return array{version: int, sale: array, data: array, company: array, client: ?array}
     *
     * @throws JsonException
     */
    public function get(string $path): array
    {
        return json_decode(
            Storage::disk('local')->get($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
