<?php

namespace App\Livewire\Forms;

use App\Enums\Sunat\DocIdentityType;
use App\Models\Client;
use App\Models\District;
use App\Services\IdentityDiurvanService;
use Livewire\Form;

class ClientForm extends Form
{
    public $name = null;

    public $tradeName = null;

    public $docIdentityType = DocIdentityType::DNI->value;

    public $documentNumber = '';

    public $address = null;

    public $department = null;

    public $province = null;

    public $district = null;

    public $telephone = null;

    public $isActive = true;

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'tradeName' => ['nullable', 'string', 'max:255'],
            'docIdentityType' => ['required'],
            'documentNumber' => $this->documentRules(),
            'address' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'isActive' => ['boolean'],
        ];
    }

    protected function documentRules(): array
    {
        return match ($this->docIdentityType?->value ?? $this->docIdentityType) {
            DocIdentityType::DNI->value => ['required', 'digits:8'],
            DocIdentityType::RUC->value => ['required', 'digits:11'],
            default => ['required', 'max:20'],
        };
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
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

        $data = $response['data'] ?? [];

        if (is_array($data) && array_key_exists('message', $data) && is_array($data['message'])) {
            $data = $data['message'];
        }

        $docIdentityType = $this->docIdentityType instanceof DocIdentityType
            ? $this->docIdentityType->value
            : $this->docIdentityType;

        $nombres = $this->normalizeString($data['nombres'] ?? null);
        $nombre = $this->normalizeString($data['nombre'] ?? null);
        $nombreCompleto = $this->normalizeString($data['nombre_completo'] ?? null);

        $razonSocialCamel = $this->normalizeString($data['razonSocial'] ?? null);
        $razonSocialSnake = $this->normalizeString($data['razon_social'] ?? null);

        if ($docIdentityType === DocIdentityType::RUC->value) {
            // Para RUC la razón social (RegistrationName) debe ir en `tradeName`.
            $this->tradeName = $razonSocialCamel ?? $razonSocialSnake ?? $nombreCompleto ?? $this->tradeName;
            $this->name = null;
        } else {
            $this->name = $nombres ?? $nombre ?? $nombreCompleto ?? $this->name;
            $this->tradeName = $razonSocialCamel ?? $razonSocialSnake ?? $this->tradeName;
        }

        $this->address = $data['direccion'] ?? null;
        $this->department = $data['departamento'] ?? null;
        $this->province = $data['provincia'] ?? null;
        $this->district = $data['distrito'] ?? null;

        $ubigeo = $data['ubigeo'] ?? null;

        if (filled($ubigeo) && (blank($this->department) || blank($this->province) || blank($this->district))) {
            $ubigeo = trim((string) $ubigeo);

            if (preg_match('/^\d{6}$/', $ubigeo) === 1) {
                $district = District::query()
                    ->where('code', $ubigeo)
                    ->with('province.department')
                    ->first();

                if ($district) {
                    $this->district = $this->district ?? $district->description;
                    $this->province = $this->province ?? $district->province?->description;
                    $this->department = $this->department ?? $district->province?->department?->description;
                }
            } elseif (str_contains($ubigeo, '-')) {
                [, $names] = explode('-', $ubigeo, 2);
                $parts = collect(explode('/', (string) $names))
                    ->map(fn ($value) => trim((string) $value))
                    ->filter(fn ($value) => $value !== '')
                    ->values()
                    ->all();

                $this->department = $this->department ?? ($parts[0] ?? null);
                $this->province = $this->province ?? ($parts[1] ?? null);
                $this->district = $this->district ?? ($parts[2] ?? null);
            }
        }

        $this->telephone = $data['telefono'] ?? $data['telephone'] ?? null;

        return $data;
    }

    public function store(): array
    {
        $this->validate();

        $docIdentityType = $this->docIdentityType instanceof DocIdentityType
            ? $this->docIdentityType->value
            : $this->docIdentityType;

        $client = Client::create([
            'name' => $this->normalizeString($this->name),
            'trade_name' => $this->normalizeString($this->tradeName),
            'doc_identity_type' => $docIdentityType,
            'document_number' => $this->documentNumber,
            'address' => $this->normalizeString($this->address),
            'department' => $this->normalizeString($this->department),
            'province' => $this->normalizeString($this->province),
            'district' => $this->normalizeString($this->district),
            'telephone' => $this->normalizeString($this->telephone),
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
