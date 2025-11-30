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
        $data = $request->validate([
            'order_id'        => ['required', 'exists:orders,id'],
            'status'          => ['required', 'in:success,failed'],
            'idempotency_key' => ['required', 'string'],
        ]);

        $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first();

        if ($existing) {
            return response()->json([
                'message' => 'Already processed',
            ], 200);
        }

        $order_id_exists_with_other_idempotency = Payment::where('order_id', $data['order_id'])->where('idempotency_key', '!=', $data['idempotency_key'])->exists();

        if ($order_id_exists_with_other_idempotency) {
            return response()->json([
                'message' => 'Already processed with other idempotency key(this in case entre a diffrent idempotency key for same order manually)',
            ], 200);
        }

        DB::transaction(function () use ($data, $request) {
            Payment::create([
                'order_id'        => $data['order_id'],
                'idempotency_key' => $data['idempotency_key'],
                'status'          => $data['status'],
                'payload'         => $request->all(),
            ]);

            $order = Order::whereKey($data['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::whereKey($order->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $hold = Hold::whereKey($order->hold_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($order->status, ['paid', 'cancelled'])) {
                return;
            }

            if ($data['status'] === 'success') {
                $order->status = 'paid';
                $order->save();

                $product->reserved_stock -= $order->qty;
                $product->sold_stock     += $order->qty;
                $product->save();

                $hold->status = 'used';
                $hold->save();
            } else {
                $order->status = 'cancelled';
                $order->save();

                $product->reserved_stock -= $order->qty;
                $product->save();

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
