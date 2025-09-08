<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Component;

$orderId = 7;
$order = Order::with('components')->find($orderId);
if (!$order) {
    echo "Order {$orderId} not found\n";
    exit(1);
}

echo "Order id={$order->id}, status={$order->status}, components attached: " . $order->components->count() . "\n\n";

echo "All attached components (pivot quantity_used, type, avail, stok_used):\n";
foreach ($order->components as $c) {
    echo sprintf("- id=%d name='%s' type=%s pivot=%d avail=%d stok_used=%d\n",
        $c->id, $c->name, $c->type, (int)($c->pivot->quantity_used ?? 0), (int)($c->quantity_available ?? 0), (int)($c->stok_used ?? 0)
    );
}

echo "\nFiltered: components with type 'huruf_kecil':\n";
foreach ($order->components->where('type','huruf_kecil') as $c) {
    echo sprintf("- id=%d name='%s' pivot=%d avail=%d stok_used=%d\n",
        $c->id, $c->name, (int)($c->pivot->quantity_used ?? 0), (int)($c->quantity_available ?? 0), (int)($c->stok_used ?? 0)
    );
}

// Also print total stok_used sum for huruf_kecil and total pivot sum
$totalPivot = $order->components->where('type','huruf_kecil')->sum(function($c){ return (int)($c->pivot->quantity_used ?? 0); });
$totalStokUsed = Component::where('type','huruf_kecil')->sum('stok_used');

echo "\nTotals for type huruf_kecil:\n";
echo "- sum pivot (for this order) = {$totalPivot}\n";
echo "- sum stok_used (all components in DB) = {$totalStokUsed}\n";
