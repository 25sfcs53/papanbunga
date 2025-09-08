<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $rakColors = [
            'Hitam',
            'Putih',
        ];
        $papanColors = [
            'Dark Green',
            'Maroon',
            'Pink',
            'Biru Langit',
        ];

        foreach ($rakColors as $name) {
            Color::firstOrCreate(
                ['name' => $name, 'type' => 'rak'],
                ['active' => true]
            );
        }
        foreach ($papanColors as $name) {
            Color::firstOrCreate(
                ['name' => $name, 'type' => 'papan'],
                ['active' => true]
            );
        }
    }
}
