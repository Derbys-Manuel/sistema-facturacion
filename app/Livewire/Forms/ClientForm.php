<?php

namespace App\Livewire\Forms;

use App\Enums\Sunat\DocIdentityType;
use App\Models\Client;
use Flux\Flux;
use Livewire\Form;

class ClientForm extends Form
{
    public $name = '';

    public $tradeName = '';

    public $docIdentityType = DocIdentityType::DNI->value;

    public $documentNumber = '';

    public $isActive = true;

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'tradeName' => ['nullable', 'string', 'max:255'],
            'docIdentityType' => ['required'],
            'isActive' => ['boolean'],
            'documentNumber' => $this->documentRules(),
        ];
    }

    protected function documentRules(): array
    {
        return match ($this->docIdentityType?->value ?? $this->docIdentityType) {
            DocIdentityType::DNI->value => ['required', 'digits:8'],   // DNI
            DocIdentityType::RUC->value => ['required', 'digits:11'],  // RUC
            default => ['required', 'max:20'],
        };
    }

    public function store()
    {
        try {
            $this->validate();
            Client::create([
                'name' => $this->name,
                'trade_name' => $this->tradeName,
                'doc_identity_type' => $this->docIdentityType,
                'document_number' => $this->documentNumber,
            ]);
            Flux::toast(
                heading: 'Aviso',
                text: 'Cliente Guardado con exito',
                variant: 'success',
                duration: 1000);
        } catch (\Throwable $th) {
            Flux::toast(
                heading: 'Aviso',
                text: 'Error al guardado client',
                variant: 'error',
                duration: 1000);
        }
    }

    public function search($q)
    {
        return Client::query()
            ->when(
                filled($q),
                fn ($query) => $query->where(fn ($subQuery) => $subQuery->where('name', 'like', "%{$q}%")
                    ->orWhere('trade_name', 'ilike', "%{$q}%")
                    ->orWhere('document_number', 'ilike', "%{$q}%")
                )
            )
            ->limit(20)
            ->get()
            ->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => ($client->name ?: $client->trade_name).' - '.$client->document_number,
            ])
            ->toArray();
    }
}
