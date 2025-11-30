<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $common = [
        'stock'          => 100,
        'reserved_stock' => 0,
        'sold_stock'     => 0,
    ];

    $products = [
        1 => ['name' => 'Adidas Duramo RC2', 'price' => 2500.00],
        2 => ['name' => 'Adidas Galaxy 7',  'price' => 3100.00],
        3 => ['name' => 'Adidas NMD R1',   'price' => 4000.00],
    ];

    foreach ($products as $id => $data) {
        Product::updateOrCreate(
            ['id' => $id],
            array_merge($data, $common)
        );
    }
    }
}
