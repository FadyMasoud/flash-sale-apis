<?php

namespace Tests\Feature;

use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

#[Test]
    public function payment_webhook_is_idempotent()
    {
        $product = Product::factory()->create([
            'stock' => 5,
        ]);

        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'expires_at' => now()->addMinutes(2),
        ]);

        $order = Order::factory()->create([
            'product_id' => $product->id,
            'hold_id' => $hold->id,
            'qty' => 1,
            'amount' => 100,
            'status' => 'pending_payment',
        ]);

        $payload = [
            'order_id' => $order->id,
            'status' => 'success',
            'idempotency_key' => 'pay-123'
        ];

        // send twice
        $res1 = $this->postJson('/api/payments/webhook', $payload);
        $res2 = $this->postJson('/api/payments/webhook', $payload);

        $order->refresh();
        $product->refresh();

        $this->assertEquals('paid', $order->status);
        $this->assertEquals(1, $product->sold_stock);
        $this->assertEquals(0, $product->reserved_stock);

        $this->assertEquals(200, $res2->status());
        $this->assertEquals('Already processed', $res2->json('message'));
    }
}
