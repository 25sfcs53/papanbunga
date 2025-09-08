<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();

        return [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'base_price' => $product->base_price,
            'discount_type' => null,
            'discount_value' => 0,
            'final_price' => $product->base_price,
            'text_content' => null,
            'status' => \App\Models\Order::STATUS_PENDING,
            'delivery_date' => now()->addDays(3)->toDateString(),
        ];
    }
}
