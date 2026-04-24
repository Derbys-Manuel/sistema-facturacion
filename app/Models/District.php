<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
