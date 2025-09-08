<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ProductSeeder extends Seeder
{
    public function run()
    {
    // Use upsert to avoid duplicate primary key errors and make seeding idempotent
    $rows = [
            [
                'id' => 1,
                'name' => 'SINGLE PREMIUM MAROON FULL BUNGA',
                'base_price' => 250000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/IocpVdT4wIbdTdRs7wOzA1KaTVqK7vqcxqvQGcGJ.jpg',
                'description' => '1 PAPAN RUSTIC PREMIUM (FULL BUNGA) WARNA MAROON',
                'created_at' => '2025-08-27 03:36:38',
                'updated_at' => '2025-08-27 11:20:24',
            ],
            [
                'id' => 2,
                'name' => 'GANDENG DARK GREEN',
                'base_price' => 400000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/2JEO9J4ieCzuomd2PzrseM1cK1LByJOarZQ640AE.png',
                'description' => '2 PAPAN RUSTIC WARNA DARK GREEN',
                'created_at' => '2025-08-27 09:49:15',
                'updated_at' => '2025-08-27 11:19:52',
            ],
            [
                'id' => 3,
                'name' => 'SINGLE PINK',
                'base_price' => 200000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/pH4oezBgp0xJ2hq2ncaZbUqW5SdWI3JkqF3eKGYq.jpg',
                'description' => '1 PAPAN RUSTIC WARNA PINK',
                'created_at' => '2025-08-27 16:54:48',
                'updated_at' => '2025-08-27 11:19:29',
            ],
            [
                'id' => 4,
                'name' => 'SINGLE BIRU LANGIT',
                'base_price' => 200000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/kTZS2ZzpR04yQbAoizU0dtkaGO9E9JADs5hYlneO.jpg',
                'description' => '1 PAPAN RUSTIC WARNA BIRU LANGIT',
                'created_at' => '2025-08-27 16:54:59',
                'updated_at' => '2025-08-27 11:19:16',
            ],
            [
                'id' => 5,
                'name' => 'SINGLE DARK GREEN',
                'base_price' => 400000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/2aJ3o9i1p5jdz4k4bKZwmXSLbTb61aUh6SUhMuBk.jpg',
                'description' => '1 PAPAN RUSTIC WARNA DARK GREEN',
                'created_at' => '2025-08-27 16:55:10',
                'updated_at' => '2025-08-27 11:17:33',
            ],
            [
                'id' => 6,
                'name' => 'GANDENG MAROON',
                'base_price' => 400000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/SRQD1nXCPq216Q89s8Qxyrzez1yw0vKngN6mlE3Q.jpg',
                'description' => '2 PAPAN RUSTIC GANDENG WARNA MAROON',
                'created_at' => '2025-08-27 16:55:04',
                'updated_at' => '2025-08-27 11:18:50',
            ],
            [
                'id' => 7,
                'name' => 'SINGLE MAROON',
                'base_price' => 200000.00,
                'required_papan_color' => null,
                'required_papan_quantity' => 1,
                'default_rack_color' => 'hitam',
                'photo' => 'products/zFi00bkHK1aCwJQMhUL4dK3qk4NfUOcyZ9pv9HNe.jpg',
                'description' => '1 PAPAN RUSTIC WARNA MAROON',
                'created_at' => '2025-08-27 16:55:07',
                'updated_at' => '2025-08-27 11:18:34',
            ],
        ];

        DB::table('products')->upsert(
            $rows,
            ['id'], // unique by id
            [
                'name', 'base_price', 'required_papan_color', 'required_papan_quantity',
                'default_rack_color', 'photo', 'description', 'created_at', 'updated_at'
            ]
        );
    }
}
