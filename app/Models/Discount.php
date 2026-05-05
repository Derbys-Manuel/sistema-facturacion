<?php

namespace App\Models;

use App\Enums\Sunat\DiscountType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends BaseModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'base_amount',
        'factor_porcentage',
        'discount_amount',
        'sale_document_id',
        'sale_document_item_id',
    ];

    protected $casts = [
        'type' => DiscountType::class,
        'base_amount' => 'decimal:2',
        'factor_porcentage' => 'decimal:5',
        'discount_amount' => 'decimal:2',
    ];

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'sale_document_id');
    }

    public function saleDocumentItem(): BelongsTo
    {
        return $this->belongsTo(SaleDocumentItem::class, 'sale_document_item_id');
    }
}