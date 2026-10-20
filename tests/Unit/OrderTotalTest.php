<?php

namespace Tests\Unit;

use App\Services\OrderService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderTotalTest extends TestCase
{
    public static function itemsProvider(): array
    {
        return [
            'single item' => [[['quantity' => 2, 'price' => 10.5]], 21.0],
            'multiple items' => [[['quantity' => 1, 'price' => 99.99], ['quantity' => 3, 'price' => '0.10']], 100.29],
            'float rounding' => [[['quantity' => 3, 'price' => 0.1]], 0.3],
            'empty' => [[], 0.0],
        ];
    }

    #[DataProvider('itemsProvider')]
    public function test_it_calculates_the_order_total(array $items, float $expected): void
    {
        $this->assertSame($expected, (new OrderService())->calculateTotal($items));
    }
}
