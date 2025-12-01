<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Hold;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        $product = Product::factory()->create();
        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'status' => 'used',
        ]);

        return [
            'product_id' => $product->id,
            'hold_id' => $hold->id,
            'qty' => 1,
            'amount' => $product->price,
            'status' => 'pending_payment',
        ];
    }
}
