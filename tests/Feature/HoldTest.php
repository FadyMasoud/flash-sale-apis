<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HoldTest extends TestCase
{
    use RefreshDatabase;


    #[Test]
    public function parallel_holds_do_not_oversell()
    {
        $product = Product::factory()->create([
            'stock' => 10,
            'reserved_stock' => 0,
            'sold_stock' => 0,
        ]);

        // run 20 parallel requests
        $responses = collect(range(1, 20))->map(function () use ($product) {
            return $this->postJson('/api/holds', [
                'product_id' => $product->id,
                'qty' => 1,
            ]);
        });

        // count successful holds
        $successCount = $responses->filter(fn($r) => $r->status() === 201)->count();

        $this->assertEquals(10, $successCount, "Should only allow 10 holds");

        $product->refresh();

        $this->assertEquals(10, $product->reserved_stock);
        $this->assertEquals(0, $product->sold_stock);
    }
}
