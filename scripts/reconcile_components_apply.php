<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Component;

$apply = in_array('--apply', $argv);
if (! $apply) {
    echo "This script will apply the reconcile updates. To proceed, re-run with --apply\n";
    exit(0);
}

$components = Component::orderBy('id')->get();
$updates = [];
foreach ($components as $comp) {
    $pivotSum = (int) DB::table('order_components')->where('component_id', $comp->id)->sum('quantity_used');
    $stokUsed = (int) ($comp->stok_used ?? 0);
    $avail = (int) ($comp->quantity_available ?? 0);
    $total = $avail + $stokUsed;
    $desiredStokUsed = $pivotSum;
    $desiredAvail = max(0, $total - $desiredStokUsed);
    if ($desiredStokUsed !== $stokUsed || $desiredAvail !== $avail) {
        $updates[] = ['id' => $comp->id, 'stok_used' => $desiredStokUsed, 'quantity_available' => $desiredAvail];
    }
}

if (empty($updates)) {
    echo "No updates required.\n";
    exit(0);
}

DB::transaction(function () use ($updates) {
    foreach ($updates as $u) {
        DB::table('components')->where('id', $u['id'])->update(['stok_used' => $u['stok_used'], 'quantity_available' => $u['quantity_available']]);
        echo sprintf("Updated component id=%d -> stok_used=%d quantity_available=%d\n", $u['id'], $u['stok_used'], $u['quantity_available']);
    }
});

echo "Reconcile applied within a transaction.\n";
