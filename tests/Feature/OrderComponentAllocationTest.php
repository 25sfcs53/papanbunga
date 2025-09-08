<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Component;

class OrderComponentAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_components_allocated_based_on_text_chars()
    {
        // Arrange: create customer, product, and components for letters A B C and a "hiasan"
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['active' => true, 'base_price' => 100000]);

        // Ensure components exist for characters 'A', 'B', 'C' and a decoration '❤️' (hiasan)
        $compA = Component::factory()->create(['name' => 'A', 'type' => 'huruf_besar', 'quantity_available' => 10]);
        $compB = Component::factory()->create(['name' => 'B', 'type' => 'huruf_besar', 'quantity_available' => 10]);
        $compC = Component::factory()->create(['name' => 'C', 'type' => 'huruf_besar', 'quantity_available' => 10]);
        $compHeart = Component::factory()->create(['name' => '❤️', 'type' => 'hiasan', 'quantity_available' => 5]);

        // The text 'ABC ❤️' should allocate A:1, B:1, C:1, hiasan:1
        $payload = [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'text_content' => "ABC ❤️",
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'shipping_address' => 'Jl Test',
            'status' => 'pending',
            'discount_type' => null,
            'discount_value' => 0,
            'summary' => 'test',
        ];

        // Act: post to store route
        $response = $this->post(route('orders.store'), $payload);

        // Assert: redirect and order exists
        $response->assertRedirect(route('orders.index'));

        $order = \App\Models\Order::first();
        $this->assertNotNull($order);

        // Build a simple map name => quantity_used for deterministic assertions
        $attached = $order->components->mapWithKeys(function ($c) {
            return [$c->name => (int) $c->pivot->quantity_used];
        })->toArray();

        $this->assertCount(4, $attached, 'Expected 4 different components attached to order. Got: ' . implode(', ', array_keys($attached)));

        foreach (['A','B','C','❤️'] as $name) {
            $this->assertArrayHasKey($name, $attached, "Expected component '{$name}' to be attached. Got: " . implode(', ', array_keys($attached)));
            $this->assertEquals(1, $attached[$name], "Component {$name} expected quantity 1, got {$attached[$name]}");
        }
    }
}
