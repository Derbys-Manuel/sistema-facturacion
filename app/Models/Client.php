<?php

namespace App\Models;

use App\Enums\Sunat\DocIdentityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
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
        'is_active',
    ];
    protected $casts = [
        'doc_identity_type' => DocIdentityType::class,
        'is_active' => 'boolean',
    ];
    public function saleDocuments(): HasMany
    {
        return $this->hasMany(SaleDocument::class);
    }
}
