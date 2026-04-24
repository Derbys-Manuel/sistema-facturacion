<?php

namespace App\Models;

use App\Enums\Sunat\DocIdentityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'last_name',
        'trade_name',
        'address',
        'email',
        'telephone',
        'doc_identity_type',
        'document_number',
        'is_active',
        'department_id',
        'province_id',
        'district_id',
    ];

    protected $casts = [
        'doc_identity_type' => DocIdentityType::class,
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function saleDocuments(): HasMany
    {
        return $this->hasMany(SaleDocument::class);
    }
}
