<?php

namespace App\Models;

use App\Enums\Sunat\DocIdentityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends BaseModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'trade_name',
        'doc_identity_type',
        'document_number',
        'address',
        'department',
        'province',
        'district',
        'telephone',
        'is_active',
    ];

    protected $casts = [
        'doc_identity_type' => DocIdentityType::class,
        'name' => 'string',
        'trade_name' => 'string',
        'document_number' => 'string',
        'address' => 'string',
        'department' => 'string',
        'province' => 'string',
        'district' => 'string',
        'telephone' => 'string',
        'is_active' => 'boolean',
    ];

    public function saleDocuments(): HasMany
    {
        return $this->hasMany(SaleDocument::class);
    }
}
