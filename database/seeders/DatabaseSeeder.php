<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Ensure roles exist (if you use a Role seeder or package, you can run it first)
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@role.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('admin123'),
            ]
        );

        // Create owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@role.com'],
            [
                'name' => 'Owner User',
                'password' => bcrypt('owner123'),
            ]
        );

        // Assign roles if assignRole method exists (idempotent)
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }

        if (method_exists($owner, 'assignRole')) {
            $owner->assignRole('owner');
        }

    // Seed default colors
    $this->call(ColorSeeder::class);
    // Seed products
    $this->call(ProductSeeder::class);
    // Seed assets
    $this->call(AssetSeeder::class);
    // Seed components stock data
    $this->call(\Database\Seeders\ComponentsSeeder::class);
    }
}
