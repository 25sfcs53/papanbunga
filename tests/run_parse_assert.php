<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ComponentStockService;
use App\Models\Component;
use Illuminate\Support\Facades\DB;

echo "Run parse assert for: 'Selamat Sukses Ucapannya'\n";

// Simple migration reset for test isolation (be careful on prod)
// We'll use DB transactions where possible
DB::beginTransaction();
try {
    // Seed minimal components
    Component::create(['name' => 'Selamat', 'type' => Component::TYPE_KATA_SAMBUNG, 'quantity_available' => 100]);
    Component::create(['name' => 'Sukses', 'type' => Component::TYPE_KATA_SAMBUNG, 'quantity_available' => 100]);
    $letters = ['S','e','l','a','m','t','u','c','p','n'];
    foreach ($letters as $ltr) {
        $type = (preg_match('/^\p{Lu}$/u', $ltr) ? Component::TYPE_HURUF_BESAR : Component::TYPE_HURUF_KECIL);
        Component::create(['name' => $ltr, 'type' => $type, 'quantity_available' => 100]);
    }

    $service = new ComponentStockService();
    $message = 'Selamat Sukses Ucapannya';
    $result = $service->parseMessage($message);

    // Basic assertions
    $selamat = Component::where('name', 'Selamat')->first();
    $sukses = Component::where('name', 'Sukses')->first();

    if (!isset($result[$selamat->id]) || $result[$selamat->id] !== 1) {
        throw new \Exception("Assertion failed: 'Selamat' not matched as expected");
    }

    if (!isset($result[$sukses->id]) || $result[$sukses->id] !== 1) {
        throw new \Exception("Assertion failed: 'Sukses' not matched as expected");
    }

    // Ucapannya: expect at least one U and some a's
    $U = Component::where('name', 'U')->first();
    $a = Component::where('name', 'a')->first();
    if (!$U || !isset($result[$U->id]) || $result[$U->id] < 1) {
        throw new \Exception("Assertion failed: uppercase U not matched");
    }
    if (!$a || !isset($result[$a->id]) || $result[$a->id] < 1) {
        throw new \Exception("Assertion failed: lowercase a not matched");
    }

    echo "All assertions passed. parseMessage works for the phrase.\n";
    DB::rollBack(); // revert seeded data
    exit(0);

} catch (\Exception $e) {
    DB::rollBack();
    echo "Test failed: " . $e->getMessage() . "\n";
    exit(2);
}
