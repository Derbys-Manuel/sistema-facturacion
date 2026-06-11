<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class CompanyCache
{
    private const LIST_KEY = 'companies:selector';

    private const COMPANY_KEY_VERSION = 'v2';

    public function selectorOptions(): array
    {
        return Cache::rememberForever(self::LIST_KEY, function (): array {
            return Company::query()
                ->select(['id', 'company_name', 'ruc'])
                ->get()
                ->map(fn (Company $company): array => [
                    'id' => (string) $company->id,
                    'label' => (string) $company->company_name,
                    'description' => (string) $company->ruc,
                    'icon' => 'building-office',
                ])
                ->all();
        });
    }

    public function findOrFail(string $id): Company
    {
        $attributes = Cache::rememberForever(
            $this->companyKey($id),
            fn (): array => Company::query()->findOrFail($id)->getAttributes(),
        );

        return (new Company)->newFromBuilder($attributes);
    }

    public function forget(?string $id = null): void
    {
        Cache::forget(self::LIST_KEY);

        if (filled($id)) {
            Cache::forget($this->companyKey($id));
        }
    }

    private function companyKey(string $id): string
    {
        return 'companies:'.self::COMPANY_KEY_VERSION.":{$id}";
    }
}
