<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Models\Product;
use Flux\Flux;


class ProductForm extends Form
{
    #[Validate('nullable|string')]
    public ?string $id = null;    
    #[Validate('string|required')]
    public string $name='';
    #[Validate('string|required')]
    public ?string $unit = 'NIU';
    #[Validate('nullable|numeric')]
    public ?int $price = null;
    #[Validate('nullable|numeric')]
    public ?string $sku = null;

    public function store(){
        $data = $this->validate();
        try {
            Product::create($data);
            Flux::toast(
                heading: 'Aviso',
                text: 'Producto Guardado con exito',
                variant: 'success',
                duration: 1000);
           
        } catch (\Throwable $th) {
            Flux::toast(
                heading: 'Aviso',
                text: 'Error al guardar producto',
                variant: 'error',
                duration: 1000);   
        }
    }
    public function getRecord(string $id){
        $record = Product::findOrFail($id);
        return $record;
    }
    public function update(): void
    {
        $data = $this->validate();
        try {
            $data->update([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'price' => $data['price'],
            ]);
            Flux::toast(
                heading: 'Aviso',
                text: 'Producto actualizado con éxito',
                variant: 'success',
                duration: 1000
            );
        } catch (\Throwable $th) {
            Flux::toast(
                heading: 'Aviso',
                text: 'Error al actualizar producto',
                variant: 'error',
                duration: 1000
            );
        }
    }
    public function search($q)
    {
        return Product::query()
            ->when($q,fn ($query) =>
                    $query->where(fn ($subQuery) =>
                        $subQuery->where('name', 'ilike', "%{$q}%")
                                ->orWhere('unit', 'ilike', "%{$q}%")
                                ->orWhere('sku', 'ilike', "%{$q}%")
                    )
            )->limit(20)->get()->map(fn($p)=>[
                'value' => (string) $p->id,
                'label' => $p->name.' '. $p->unit .' '. $p->sku,
            ])->toArray();
    }
}
