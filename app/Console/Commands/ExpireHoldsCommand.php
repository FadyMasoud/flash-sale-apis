<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExpireHoldsCommand extends Command
{
    protected $signature = 'holds:expire';
    protected $description = 'Expire active holds and restore reserved stock';

    public function handle()
    {
        $expiredHolds = Hold::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredHolds as $hold) {
            DB::transaction(function () use ($hold) {

                $hold->refresh();
                if ($hold->status !== 'active' || !$hold->expires_at->isPast()) {
                    return; // skip double-run
                }

                $product = Product::whereKey($hold->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $product->reserved_stock -= $hold->qty;
                    $product->save();
                    Cache::forget("product:{$product->id}");
                }

                $hold->status = 'expired';
                $hold->save();
            });
        }

        $this->info("Expired holds processed: " . $expiredHolds->count());
    }
}
