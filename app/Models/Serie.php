<?php

namespace App\Models;

use App\Enums\Sunat\DocSunatType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;

class Serie extends BaseModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'doc_sunat_type',
        'description',
        'code',
        'correlative',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'doc_sunat_type' => DocSunatType::class,
        'correlative'=> 'integer',
        'code' => 'string',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}