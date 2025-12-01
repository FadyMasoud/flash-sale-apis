<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => 'Test Product',
            'price' => 100,
            'stock' => 10,
            'reserved_stock' => 0,
            'sold_stock' => 0,
        ];
    }
}
