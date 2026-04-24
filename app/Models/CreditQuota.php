<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditQuota extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'document_type',
        'number',
        'date_expiration',
        'date_paid',
        'total_to_pay',
        'total_paid',
        'is_active',
        'sale_document_id',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'date_expiration' => 'date',
        'date_paid' => 'date',
        'total_to_pay' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
