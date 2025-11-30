<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Hold;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HoldController extends Controller
{
public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty'        => ['required', 'integer', 'min:1'],
        ]);

        $hold = null;

        DB::transaction(function () use (&$hold, $data) {
            $product = Product::whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $available = $product->available_stock;

            if ($available < $data['qty']) {
                throw ValidationException::withMessages([
                    'qty' => ['Not enough stock available'],
                ]);
            }

            $hold = Hold::create([
                'product_id' => $product->id,
                'qty'        => $data['qty'],
                'status'     => 'active',
                'expires_at' => now()->addMinutes(2),
            ]);

            $product->reserved_stock += $data['qty'];
            $product->save();

            Cache::forget("product:{$product->id}");
        }, 3);

        return response()->json([
            'hold_id'    => $hold->id,
            'expires_at' => $hold->expires_at->toIso8601String(),
        ], 201);
    }

}

