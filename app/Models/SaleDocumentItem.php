<?php

namespace App\Models;

use App\Enums\Sunat\AffecType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;

class SaleDocumentItem extends BaseModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'description',
        'unit',
        'quantity',
        'unit_value',
        'unit_price',
        'item_value',
        'igv_affectation_type',
        'igv_base_amount',
        'igv_percent',
        'igv_amount',
        'icbper_factor',
        'icbper_amount',
        'taxes_total',
        'is_active',
        'sale_document_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_value' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'item_value' => 'decimal:2',
        'igv_affectation_type' => AffecType::class,
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
