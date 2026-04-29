<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends BaseModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = [
        'sol_user',
        'sol_pass',
    ];

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
        'company_name' => 'string',
        'ruc' => 'string',
        'urbanization' => 'string',
        'address' => 'string',
        'cod_local' => 'string',
        'sol_user' => 'encrypted:string',
        'sol_pass' => 'encrypted:string',
        'cert_path' => 'string',
        'logo_path' => 'string',
        'ubigueo' => 'string',
        'department' => 'string',
        'province' => 'string',
        'district' => 'string',
    ];

    public function saleDocuments(): HasMany
    {
        return $this->hasMany(SaleDocument::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(Serie::class);
    }
}
