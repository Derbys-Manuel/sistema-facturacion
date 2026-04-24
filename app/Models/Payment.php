<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'document_type',
        'date',
        'amount',
        'note',
        'sale_document_id',
        'payment_method_id',
        'credit_quota_id',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creditQuota(): BelongsTo
    {
        return $this->belongsTo(CreditQuota::class);
    }
}
