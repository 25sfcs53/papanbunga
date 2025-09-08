<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Seeder idempoten untuk menyetel role pada kolom users.role
     * dan memastikan minimal ada user admin & owner.
     */
    public function run(): void
    {
        // Buat/ambil admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@role.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('admin123'),
            ]
        );

        // Buat/ambil owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@role.com'],
            [
                'name' => 'Owner User',
                'password' => bcrypt('owner123'),
            ]
        );

        // Set role dengan helper assignRole() (metode ada di App\Models\User)
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        } else {
            $admin->role = 'admin';
            $admin->save();
        }

        if (method_exists($owner, 'assignRole')) {
            $owner->assignRole('owner');
        } else {
            $owner->role = 'owner';
            $owner->save();
        }
    }
}
