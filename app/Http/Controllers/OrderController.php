<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
// public function store(Request $request)
// {
//     $data = $request->validate([
//         'hold_id' => ['required', 'exists:holds,id'],
//     ]);

//     /** 
//      * @var \App\Models\Order|null $order 
//      */
//     $order = null;

//     DB::transaction(function () use (&$order, $data) {

//         $hold = Hold::whereKey($data['hold_id'])
//             ->lockForUpdate()
//             ->firstOrFail();

//         if ($hold->status !== 'active' || $hold->expires_at->isPast()) {

//             if ($hold->status === 'active' && $hold->expires_at->isPast()) {
//                 $product = Product::whereKey($hold->product_id)->lockForUpdate()->first();
//                 $product->reserved_stock -= $hold->qty;
//                 $product->save();
//                 Cache::forget("product:{$product->id}");

//                 $hold->status = 'expired';
//                 $hold->save();
//             }

//             abort(422, "Hold is not valid or has expired.");
//             // return response()->json([ 'error' => 'Hold is not valid or has expired.'], 200);
//         }

//         if (!is_null($hold->order_id)) {
//             abort(422, "Hold already used for an order.");
//         }

//         $product = Product::whereKey($hold->product_id)
//             ->lockForUpdate()
//             ->firstOrFail();

//         $amount = $hold->qty * $product->price;

//         $order = Order::create([
//             'product_id' => $product->id,
//             'hold_id'    => $hold->id,
//             'qty'        => $hold->qty,
//             'amount'     => $amount,
//             'status'     => 'pending_payment',
//         ]);

//         $hold->status = 'used';
//         $hold->order_id = $order->id;
//         $hold->save();
//     }, 3);

    

//     return response()->json([
//         'order_id' => $order->id,
//         'status'   => $order->status,
//         'qty'      => $order->qty,
//         'amount'   => $order->amount,
//     ], 201);
// }

public function store(Request $request)
{
    // Validate request
    $data = $request->validate([
        'hold_id' => ['required', 'exists:holds,id'],
    ]);

    $order = null;
    $errorMessage = null;
    
    DB::transaction(function () use (&$order, &$errorMessage, $data) {

        // Lock hold row
        $hold = Hold::whereKey($data['hold_id'])
            ->lockForUpdate()
            ->first();

        if (!$hold) {
            $errorMessage = "Hold not found.";
            return;
        }

        // Hold expired or invalid
        if ($hold->status !== 'active' || $hold->expires_at->isPast()) {

            // if it just expired, release reserved stock
            if ($hold->status === 'active' && $hold->expires_at->isPast()) {

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
            }

            $errorMessage = "Hold is not valid or has expired.";
            return;
        }

        // Hold already used
        if (!is_null($hold->order_id)) {
            $errorMessage = "Hold already used for an order.";
            return;
        }

        // Lock product
        $product = Product::whereKey($hold->product_id)
            ->lockForUpdate()
            ->first();

        if (!$product) {
            $errorMessage = "Product not found.";
            return;
        }

        // Create order
        $amount = $hold->qty * $product->price;

        $order = Order::create([
            'product_id' => $product->id,
            'hold_id'    => $hold->id,
            'qty'        => $hold->qty,
            'amount'     => $amount,
            'status'     => 'pending_payment',
        ]);

        // Update hold (used)
        $hold->status = 'used';
        $hold->order_id = $order->id;
        $hold->save();

    }, 3);

    // Return any error
    if ($errorMessage !== null) {
        return response()->json(['error' => $errorMessage], 422);
    }

    // Success
    return response()->json([
        'order_id' => $order->id,
        'status'   => $order->status,
        'qty'      => $order->qty,
        'amount'   => $order->amount,
    ], 201);
}



}
