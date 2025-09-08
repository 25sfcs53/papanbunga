<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Color;

class AssetSeeder extends Seeder
{
    public function run()
    {
        // Ensure colors exist
        $dark = Color::firstOrCreate(['name' => 'Dark Green'], ['active' => true]);
        $hitam = Color::firstOrCreate(['name' => 'Hitam'], ['active' => true]);

        $rows = [
            [
                'type' => 'papan',
                'color' => $dark->name,
                'quantity_total' => 6,
                'quantity_rented' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'rak',
                'color' => $hitam->name,
                'quantity_total' => 6,
                'quantity_rented' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('assets')->upsert($rows, ['type', 'color'], ['quantity_total', 'quantity_rented', 'updated_at']);
    }
}
