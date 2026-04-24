<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pack extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PackItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pack_items')
            ->withPivot(['id', 'quantity', 'price', 'is_active'])
            ->withTimestamps();
    }
}
