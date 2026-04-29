<?php

namespace App\Models;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends BaseModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'unit',
        'sku',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'name' => 'string',
        'unit' => 'string',
        'sku' => 'string',
        'is_active' => 'boolean',
    ];
}
