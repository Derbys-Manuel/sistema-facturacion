<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleDocument extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'document_type' => DocumentType::class,
        'doc_sunat_type' => DocSunatType::class,
        'operation_type' => OperationType::class,
        'payment_form' => PaymentForm::class,
        'cdr' => 'array',
        'legends' => 'array',
        'date_issue' => 'datetime',
        'date_expiration' => 'datetime',
        'sunat_state' => 'boolean',
        'total_taxed' => 'decimal:2',
        'total_exempted' => 'decimal:2',
        'total_unaffected' => 'decimal:2',
        'total_export' => 'decimal:2',
        'total_free' => 'decimal:2',
        'total_igv' => 'decimal:2',
        'total_igv_free' => 'decimal:2',
        'icbper' => 'decimal:2',
        'total_taxes' => 'decimal:2',
        'sale_value' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'total_sale' => 'decimal:2',
        'rounding' => 'decimal:2',
        'total' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleDocumentItem::class);
    }

    public function creditQuotas(): HasMany
    {
        return $this->hasMany(CreditQuota::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
