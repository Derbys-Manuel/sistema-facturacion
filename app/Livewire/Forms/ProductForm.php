<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProductForm extends Form
{
    private const SEARCH_CACHE_KEY = 'products.search-list';

    #[Validate('nullable|string')]
    public ?string $id = null;

    #[Validate('string|required')]
    public string $name = '';

    #[Validate('string|required')]
    public ?string $unit = 'NIU';

    #[Validate('nullable|numeric|min:0')]
    public int|float|null $price = null;

    #[Validate('nullable|string')]
    public ?string $sku = null;

    public function store(): array
    {
        $this->validate();

        $product = Product::create([
            'name' => $this->name,
            'unit' => $this->unit,
            'sku' => $this->sku,
            'price' => $this->price,
            'is_active' => true,
        ]);

        $this->clearSearchCache();

        return $product->toArray();
    }

    public function getRecord(string $id): Product
    {
        return Product::findOrFail($id);
    }

    public function update(): Product
    {
        $this->validate();

        $product = Product::findOrFail($this->id);

        $product->update([
            'name' => $this->name,
            'unit' => $this->unit,
            'sku' => $this->sku,
            'price' => $this->price,
        ]);

        $this->clearSearchCache();

        return $product;
    }

    public function search(string $q): array
    {
        $q = trim($q);

        if (mb_strlen($q) < 2) {
            return [];
        }

        $products = Cache::rememberForever(self::SEARCH_CACHE_KEY, function () {
            return Product::query()
                ->select(['id', 'name', 'unit', 'sku', 'price'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray();
        });

        $needle = $this->normalizeSearchText($q);

        return collect($products)
            ->filter(function (array $product) use ($needle) {
                $name = $this->normalizeSearchText($product['name'] ?? '');
                $sku = $this->normalizeSearchText($product['sku'] ?? '');

                return Str::contains($name, $needle)
                    || Str::contains($sku, $needle);
            })
            ->take(10)
            ->values()
            ->toArray();
    }

    private function clearSearchCache(): void
    {
        Cache::forget(self::SEARCH_CACHE_KEY);
    }

    private function normalizeSearchText(?string $value): string
    {
        return Str::of($value ?? '')
            ->ascii()
            ->lower()
            ->trim()
            ->toString();
    }
}