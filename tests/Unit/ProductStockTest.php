<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductStockTest extends TestCase
{
    public function test_stock_is_only_low_when_it_is_strictly_below_the_threshold(): void
    {
        $product = new Product(['stock_on_hand' => 4]);

        $this->assertTrue($product->isLowStock(5));
        $this->assertFalse($product->isLowStock(4));
        $this->assertFalse($product->isLowStock(3));
    }
}
