<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Component;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_cycle_disewa_pending_disewa_does_not_double_consume_components()
    {
        // create admin user and assign role allowed to manage orders
        $user = User::factory()->create();
        $user->assignRole('admin');

        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $component = Component::factory()->create([ 'quantity_available' => 10, 'stok_used' => 0 ]);

        // create order pending and attach component pivot qty=1
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'status' => 'Pending',
            'delivery_date' => Carbon::today()->toDateString(),
        ]);

        $order->components()->attach($component->id, ['quantity_used' => 1]);

        // initial expectations
        $this->assertDatabaseHas('components', ['id' => $component->id, 'stok_used' => 0]);
        $this->assertDatabaseHas('order_components', ['order_id' => $order->id, 'component_id' => $component->id, 'quantity_used' => 1]);

        // 1) Update to disewa via orders.update (edit form flow) - should consume pivot (since pivot exists)
        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'delivery_date' => Carbon::today()->toDateString(),
                // send lowercase status as form inputs normally use
                'status' => 'disewa',
            ])
            ->assertRedirect(route('orders.index'));

        // stok_used should increase by 1 and quantity_available should decrease by 1
        $this->assertDatabaseHas('components', ['id' => $component->id, 'stok_used' => 1]);

        // 2) Update to pending - should return components and detach pivot
        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'delivery_date' => Carbon::today()->toDateString(),
                'status' => 'pending',
            ])
            ->assertRedirect(route('orders.index'));

        // stok_used back to 0 and order_components detached
        $this->assertDatabaseHas('components', ['id' => $component->id, 'stok_used' => 0]);
        $this->assertDatabaseMissing('order_components', ['order_id' => $order->id, 'component_id' => $component->id]);

        // 3) Update back to disewa - should consume pivot (re-create pivot and consume once)
        // Re-attach pivot to simulate the order having components on edit (some flows attach before update)
        $order->components()->attach($component->id, ['quantity_used' => 1]);

        $this->actingAs($user)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'delivery_date' => Carbon::today()->toDateString(),
                'status' => 'disewa',
            ])
            ->assertRedirect(route('orders.index'));

        // stok_used should again be 1 (not 2)
        $this->assertDatabaseHas('components', ['id' => $component->id, 'stok_used' => 1]);
    }
}
