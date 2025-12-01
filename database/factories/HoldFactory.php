<?php

namespace Database\Factories;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class HoldFactory extends Factory
{
    protected $model = Hold::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'qty' => 1,
            'status' => 'active',
            'expires_at' => now()->addMinutes(2),
            'order_id' => null,
        ];
    }
}
