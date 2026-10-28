<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Ethiopian Yirgacheffe Beans 1kg', 'code' => 'COF-YIR-1KG', 'price_per_unit' => 1450.00, 'tax_percentage' => 5, 'stock_on_hand' => 40],
            ['name' => 'House Blend Beans 500g', 'code' => 'COF-HOU-500', 'price_per_unit' => 650.00, 'tax_percentage' => 5, 'stock_on_hand' => 60],
            ['name' => 'Cold Brew Bottle 250ml', 'code' => 'BEV-COL-250', 'price_per_unit' => 220.00, 'tax_percentage' => 12, 'stock_on_hand' => 2],
            ['name' => 'Oat Milk 1L', 'code' => 'DRY-OAT-1L', 'price_per_unit' => 180.00, 'tax_percentage' => 5, 'stock_on_hand' => 25],
            ['name' => 'Ceramic Mug 350ml', 'code' => 'MER-MUG-350', 'price_per_unit' => 450.00, 'tax_percentage' => 18, 'stock_on_hand' => 3],
            ['name' => 'Reusable Cup 400ml', 'code' => 'MER-CUP-400', 'price_per_unit' => 620.00, 'tax_percentage' => 18, 'stock_on_hand' => 0],
            ['name' => 'Espresso Machine Mini', 'code' => 'EQ-ESP-MINI', 'price_per_unit' => 18900.00, 'tax_percentage' => 18, 'stock_on_hand' => 6],
            ['name' => 'Paper Filter Pack 100', 'code' => 'SUP-FIL-100', 'price_per_unit' => 250.00, 'tax_percentage' => 12, 'stock_on_hand' => 80],
            ['name' => 'Vanilla Syrup 750ml', 'code' => 'SYR-VAN-750', 'price_per_unit' => 480.00, 'tax_percentage' => 12, 'stock_on_hand' => 4],
            ['name' => 'Almond Croissant', 'code' => 'BAK-ALM-001', 'price_per_unit' => 140.00, 'tax_percentage' => 5, 'stock_on_hand' => 15],
            ['name' => 'Sourdough Loaf', 'code' => 'BAK-SOU-001', 'price_per_unit' => 260.00, 'tax_percentage' => 5, 'stock_on_hand' => 8],
            ['name' => 'Stainless Tamper', 'code' => 'EQ-TAM-001', 'price_per_unit' => 1200.00, 'tax_percentage' => 18, 'stock_on_hand' => 1],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['code' => $product['code']], $product);
        }
    }
}
