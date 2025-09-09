<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // buat file sqlite kosong jika perlu via env/test config (developer perlu menjalankan migrate)
    }

    public function test_owner_can_create_update_and_delete_component()
    {
        $owner = User::factory()->create(['email' => 'owner@role.com']);
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->post(route('components.store'), [
                'name' => 'Huruf A',
                'type' => 'huruf_besar',
                'color' => 'white',
                'quantity_available' => 10,
            ])
            ->assertRedirect(route('components.index'));

        $this->assertDatabaseHas('components', ['name' => 'Huruf A']);

        $component = Component::first();

        // update
        $this->actingAs($owner)
            ->put(route('components.update', $component), [
                'name' => 'Huruf A+',
                'type' => 'huruf_besar',
                'color' => 'white',
                'quantity_available' => 15,
            ])
            ->assertRedirect(route('components.index'));

        $this->assertDatabaseHas('components', ['name' => 'Huruf A+']);

        // delete
        $this->actingAs($owner)
            ->delete(route('components.destroy', $component))
            ->assertRedirect(route('components.index'));

        $this->assertDatabaseCount('components', 0);
    }

    public function test_cannot_delete_component_if_used_in_order()
    {
        $owner = User::factory()->create(['email' => 'owner2@role.com']);
        $owner->assignRole('owner');

        // buat komponen dan order yang menggunakannya
        $component = Component::factory()->create(['quantity_available' => 5]);

        $order = \App\Models\Order::factory()->create();
        $order->components()->attach($component->id, ['quantity_used' => 2]);

        $this->actingAs($owner)
            ->delete(route('components.destroy', $component))
            ->assertRedirect(route('components.index'));

        $this->assertDatabaseHas('components', ['id' => $component->id]);
    }
}
