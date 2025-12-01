<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
public function __invoke(Request $request)
{
    // Do NOT check exists:orders,id   → webhook may arrive before order creation
    $data = $request->validate([
        'order_id'        => ['required', 'integer'],
        'status'          => ['required', 'in:success,failed'],
        'idempotency_key' => ['required', 'string'],
    ]);

    // Check if this idempotency key already processed
    $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first();

    if ($existing) {
        return response()->json([
            'message' => 'Already processed',
        ], 200);
    }
       

     $order_id_exists_for_different_idempotency_key = Payment::where('order_id', $data['order_id'])->where('idempotency_key', '!=', $data['idempotency_key'])->exists();

    if ($order_id_exists_for_different_idempotency_key) {
        return response()->json([
            'message' => 'Already processed,with different idempotency key(in case insert a idempotency key manually for a same order)',
        ], 200);
    }


    DB::transaction(function () use ($data, $request) {

        // Record the webhook event
        Payment::create([
            'order_id'        => $data['order_id'],
            'idempotency_key' => $data['idempotency_key'],
            'status'          => $data['status'],
            'payload'         => $request->all(),
        ]);

        // Try to fetch order (it MAY NOT exist yet!)
        $order = Order::whereKey($data['order_id'])
            ->lockForUpdate()
            ->first();

        // If order not created yet → stop here
        // On next webhook (with same idempotency key) nothing will break
        if (!$order) {
            return;
        }

        // If already final state → idempotent
        if (in_array($order->status, ['paid', 'cancelled'])) {
            return;
        }

        // Lock product & hold
        $product = Product::whereKey($order->product_id)
            ->lockForUpdate()
            ->first();

        $hold = Hold::whereKey($order->hold_id)
            ->lockForUpdate()
            ->first();

        if ($data['status'] === 'success') {

            // Mark order paid
            $order->status = 'paid';
            $order->save();

            // Convert reserved→sold safely
            $product->reserved_stock = max(0, $product->reserved_stock - $order->qty);
            $product->sold_stock    += $order->qty;
            $product->save();

            // Mark hold used
            $hold->status = 'used';
            $hold->save();

        } else {

            // Mark order cancelled
            $order->status = 'cancelled';
            $order->save();

            // Return reserved stock safely
            $product->reserved_stock = max(0, $product->reserved_stock - $order->qty);
            $product->save();

            // Mark hold cancelled
            $hold->status = 'cancelled';
            $hold->save();
        }

        Cache::forget("product:{$product->id}");
    }, 3);

    return response()->json([
        'message' => 'Processed',
    ], 200);
}
}
