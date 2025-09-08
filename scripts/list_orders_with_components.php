<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orders = Order::whereHas('components')->with('components')->take(10)->get();
if ($orders->isEmpty()) { echo "No orders with components found\n"; exit(0); }
foreach ($orders as $o) {
    echo "Order id={$o->id} status={$o->status}\n";
    foreach ($o->components as $c) {
        echo sprintf("  - comp id=%d name=%s pivot=%s avail=%s stok_used=%s\n", $c->id, $c->name, $c->pivot->quantity_used ?? 'N/A', $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A');
    }
}
