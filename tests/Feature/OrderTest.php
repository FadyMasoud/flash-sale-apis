<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderTest extends TestCase
{
    use RefreshDatabase;



    #[Test]
    public function webhook_before_order_creation_is_handled()
    {
        $product = Product::factory()->create([
            'stock' => 5,
        ]);

        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'expires_at' => now()->addMinutes(2),
        ]);

        // Webhook arriving before order exists
        $response1 = $this->postJson('/api/payments/webhook', [
            'order_id' => 1, // doesn't exist yet
            'status' => 'success',
            'idempotency_key' => 'abc123',
        ]);

        // Should not crash
        $response1->assertStatus(200);

        // Now create the order
        $orderResponse = $this->postJson('/api/orders', [
            'hold_id' => $hold->id
        ]);

        $order = Order::first();

        // Send webhook again AFTER order exists
        $response2 = $this->postJson('/api/payments/webhook', [
            'order_id' => $order->id,
            'status' => 'success',
            'idempotency_key' => 'abc123',
        ]);

        $order->refresh();

        $this->assertEquals('paid', $order->status);
    }
}
