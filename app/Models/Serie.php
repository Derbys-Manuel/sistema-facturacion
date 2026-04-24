<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'document_type' => DocumentType::class,
        'is_active' => 'boolean',
    ];
}
