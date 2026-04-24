<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
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
