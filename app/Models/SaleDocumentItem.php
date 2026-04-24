<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDocumentItem extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_value' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'item_value' => 'decimal:2',
        'igv_affectation_type' => 'integer',
        'igv_base_amount' => 'decimal:2',
        'igv_percent' => 'decimal:2',
        'igv_amount' => 'decimal:2',
        'icbper_factor' => 'decimal:6',
        'icbper_amount' => 'decimal:2',
        'taxes_total' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class);
    }
}
