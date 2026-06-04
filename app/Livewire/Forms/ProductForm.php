<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProductForm extends Form
{
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

        return $product;
    }

    public function search(string $q): array
    {
        return Product::query()
            ->select(['id', 'name', 'unit', 'sku'])
            ->when($q, fn ($query) => $query->where(fn ($subQuery) => $subQuery->where('name', 'ilike', "%{$q}%")
                ->orWhere('unit', 'ilike', "%{$q}%")
                ->orWhere('sku', 'ilike', "%{$q}%")
            )
            )->limit(20)->get()->map(fn ($p) => [
                'value' => (string) $p->id,
                'label' => $p->name.' '.$p->unit.' '.$p->sku,
            ])->toArray();
    }
}
