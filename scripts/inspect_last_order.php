<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create kernel and boot
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$order = Order::latest()->first();
if (! $order) {
    echo "No orders found\n";
    exit(0);
}

echo "Order id={$order->id}, status={$order->status}\n";
foreach ($order->components as $c) {
    echo "Component: {$c->name} (id={$c->id}) qty_used={$c->pivot->quantity_used} available={$c->quantity_available}\n";
}

foreach ($order->assets as $a) {
    echo "Asset: {$a->name} (id={$a->id}) qty_used={$a->pivot->quantity_used}\n";
}

echo "Done\n";
