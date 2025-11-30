<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(int $id)
    {
        $product = Cache::remember("product:{$id}", 5, function () use ($id) {
            $product = Product::findOrFail($id); // 404 تلقائي لو مش موجود
            $product->available_stock = $product->available_stock;
            return $product;
        });

        return response()->json([
            'id'              => $product->id,
            'name'            => $product->name,
            'price'           => $product->price,
            'stock'           => $product->stock,
            'available_stock' => $product->available_stock,
        ]);
    }

}
