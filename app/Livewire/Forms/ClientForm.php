<?php

namespace App\Livewire\Forms;

use App\Enums\Sunat\DocIdentityType;
use App\Models\Client;
use Livewire\Form;
use App\Services\IdentityDiurvanService;

class ClientForm extends Form
{
    public $name = null;

    public $tradeName = null;

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

    public function consultDocument(IdentityDiurvanService $identityService)
    {
        if (strlen($this->documentNumber) === 8) {
            $response = $identityService->searchDni($this->documentNumber);
        } elseif (strlen($this->documentNumber) === 11) {
            $response = $identityService->searchRuc($this->documentNumber);
        } else {
            return;
        }

        if (! $response['success']) {
            return;
        }

        $data = $response['data'];

        // Ajusta estos campos según la respuesta real de la API.
        $this->name = $data['nombres'] ?? $data['nombre'] ?? null;
        $this->tradeName = $data['razonSocial'] ?? $data['razon_social'] ?? null;
        $this->address = $data['direccion'] ?? null;
        return $data;
    }

    public function store(): array
    {
        $this->validate();

        $docIdentityType = $this->docIdentityType instanceof DocIdentityType
            ? $this->docIdentityType->value
            : $this->docIdentityType;

        $client = Client::create([
            'name' => $this->name,
            'trade_name' => $this->tradeName,
            'doc_identity_type' => $docIdentityType,
            'document_number' => $this->documentNumber,
            'is_active' => (bool) $this->isActive,
        ]);
        return $client->toArray();
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
    public function searchWithoutDni($q)
    {
        return Client::query()
            ->where('doc_identity_type', DocIdentityType::RUC->value)
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
