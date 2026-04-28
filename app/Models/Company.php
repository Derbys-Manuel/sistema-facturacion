<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_name',
        'ruc',
        'urbanization',
        'address',
        'cod_local',
        'sol_user',
        'sol_pass',
        'cert_path',
        'logo_path',
        'production',
        'ubigueo',
        'department',
        'province',
        'district',
    ];

    protected $casts = [
        'production' => 'boolean',
    ];

    public function saleDocuments(): HasMany
    {
        return $this->hasMany(SaleDocument::class);
    }
}
