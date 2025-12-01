<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;





class ExpiryTest extends TestCase
{
    use RefreshDatabase;



    #[Test]
    public function expired_holds_restore_stock()
    {
        $product = Product::factory()->create([
            'stock' => 10,
            'reserved_stock' => 2,
        ]);

        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => 2,
            'status' => 'active',
            'expires_at' => now()->subMinutes(3), // already expired
        ]);

        // simulate scheduler
        $this->artisan('holds:expire');

        $product->refresh();
        $hold->refresh();

        $this->assertEquals('expired', $hold->status);
        $this->assertEquals(0, $product->reserved_stock);
        $this->assertEquals(10, $product->available_stock);
    }
}
