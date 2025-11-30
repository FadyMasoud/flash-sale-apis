<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'stock', 'reserved_stock', 'sold_stock',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function holds()
    {
        return $this->hasMany(Hold::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->stock - $this->reserved_stock - $this->sold_stock;
    }
}
