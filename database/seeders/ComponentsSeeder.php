<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Component;

class ComponentsSeeder extends Seeder
{
    public function run(): void
    {
        // BAGIAN 1: HURUF BESAR
        // Raised stock levels for more reliable testing
        $capitalGroups = [
            ['letters' => ['A'], 'stok' => 120],
            ['letters' => ['I','N','S','T','E','R'], 'stok' => 100],
            ['letters' => ['O','U','K','L','M','P'], 'stok' => 90],
            ['letters' => ['D','G','H','B','C','Y'], 'stok' => 80],
            ['letters' => ['F','W','V'], 'stok' => 70],
            ['letters' => ['J'], 'stok' => 60],
            ['letters' => ['Q','X','Z'], 'stok' => 50],
        ];
        foreach ($capitalGroups as $grp) {
            foreach ($grp['letters'] as $letter) {
                Component::updateOrCreate([
                    'name' => strtoupper($letter),
                    'type' => 'huruf_besar',
                ], [
                    'stok_total' => $grp['stok'],
                    'stok_used' => 0,
                    'quantity_available' => $grp['stok'],
                ]);
            }
        }

        // BAGIAN 2: HURUF KECIL
        // Increased lowercase stocks for testing (common letters higher)
        $lowerGroups = [
            ['letters' => ['a','i','n','s','t','e','r','o','u'], 'stok' => 90],
            ['letters' => ['l','m','p','d','g','h','b','c','y','f','w'], 'stok' => 70],
            ['letters' => ['v','j','k','q','x','z'], 'stok' => 60],
        ];
        foreach ($lowerGroups as $grp) {
            foreach ($grp['letters'] as $letter) {
                Component::updateOrCreate([
                    'name' => $letter,
                    'type' => 'huruf_kecil',
                ], [
                    'stok_total' => $grp['stok'],
                    'stok_used' => 0,
                    'quantity_available' => $grp['stok'],
                ]);
            }
        }

        // BAGIAN 3: ANGKA
    $digits20 = ['0','1','2'];
        foreach ($digits20 as $d) {
            Component::updateOrCreate([
                'name' => $d,
                'type' => 'angka',
            ], [
                'stok_total' => 20,
                'stok_used' => 0,
                'quantity_available' => 20,
            ]);
        }
        for ($d = 3; $d <= 9; $d++) {
            Component::updateOrCreate([
                'name' => (string)$d,
                'type' => 'angka',
            ], [
                'stok_total' => 15,
                'stok_used' => 0,
                'quantity_available' => 15,
            ]);
        }

        // BAGIAN 4: SIMBOL & HIASAN
        // Symbols & decorative items: raise to comfortable testing levels
        $symbols = [
            ['name' => '&', 'type' => 'hiasan', 'stok' => 80],
            ['name' => '.', 'type' => 'hiasan', 'stok' => 80],
            ['name' => '-', 'type' => 'hiasan', 'stok' => 70],
            ['name' => ',', 'type' => 'hiasan', 'stok' => 70],
            ['name' => '!', 'type' => 'hiasan', 'stok' => 60],
            ['name' => '?', 'type' => 'hiasan', 'stok' => 60],
            ['name' => '"', 'type' => 'hiasan', 'stok' => 60],
            ['name' => 'Kupu-kupu', 'type' => 'hiasan', 'stok' => 80],
            ['name' => 'Bintang', 'type' => 'hiasan', 'stok' => 80],
            ['name' => 'Hati', 'type' => 'hiasan', 'stok' => 70],
            ['name' => 'Cincin', 'type' => 'hiasan', 'stok' => 60],
            ['name' => 'Pita', 'type' => 'hiasan', 'stok' => 60],
            ['name' => 'Topi Toga', 'type' => 'hiasan', 'stok' => 70],
        ];
        foreach ($symbols as $s) {
            // decide simbol vs hiasan per name: single non-alnum => simbol, otherwise keep provided type
            $seedType = $s['type'];
            if (mb_strlen($s['name']) === 1 && preg_match('/[^A-Za-z0-9]/u', $s['name'])) {
                $seedType = 'simbol';
            }
            Component::updateOrCreate([
                'name' => $s['name'],
                'type' => $seedType,
            ], [
                'stok_total' => $s['stok'],
                'stok_used' => 0,
                'quantity_available' => $s['stok'],
            ]);
        }

        // BAGIAN 5: KATA SAMBUNG UCAPAN
        // Phrase stocks increased for testing
        $phrases = [
            ['name' => 'Selamat', 'stok' => 60],
            ['name' => 'Sukses', 'stok' => 60],
            ['name' => 'Happy', 'stok' => 50],
            ['name' => 'Wedding', 'stok' => 50],
            ['name' => 'Congratulation', 'stok' => 50],
            ['name' => 'Grand Opening', 'stok' => 50],
            ['name' => 'from', 'stok' => 40],
            ['name' => 'Graduation', 'stok' => 40],
            ['name' => 'unofficially', 'stok' => 20],
        ];
        foreach ($phrases as $p) {
            Component::updateOrCreate([
                'name' => $p['name'],
                'type' => 'kata_sambung',
            ], [
                'stok_total' => $p['stok'],
                'stok_used' => 0,
                'quantity_available' => $p['stok'],
            ]);
        }
    }
}
