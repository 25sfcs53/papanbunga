<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Component;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Use an existing order with components or create a fresh one
$orderId = 9; // change if needed
$order = Order::with(['assets','components'])->find($orderId);
if (! $order) { echo "Order id={$orderId} not found\n"; exit(1); }

// Ensure the order has at least one component pivot; if not, attach component id 95 with qty 1
if ($order->components->isEmpty()) {
    $comp = Component::first();
    if (! $comp) { echo "No components in DB\n"; exit(1); }
    $order->components()->attach($comp->id, ['quantity_used' => 1]);
    $order->refresh(); $order->load('components');
}

echo "Initial state for order id={$order->id} status={$order->status}\n";
foreach ($order->components as $c) {
    echo sprintf("COMP - id=%d name=%s avail=%s stok_used=%s pivot=%s\n", $c->id, $c->name, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');
}

// Force Disewa state and ensure consumption recorded
DB::transaction(function () use ($order) {
    $order->status = 'disewa';
    $order->save();

    foreach ($order->components as $comp) {
        $qty = (int) ($comp->pivot->quantity_used ?? 0);
        if ($qty > 0) {
            $comp->stok_used = max((int)($comp->stok_used ?? 0), $qty);
            $comp->quantity_available = max(0, (int)($comp->quantity_available ?? 0) - $qty);
            $comp->save();
        }
    }
});

$order->refresh(); $order->load('components');

echo "\nAfter forcing Disewa:\n";
foreach ($order->components as $c) {
    echo sprintf("COMP - id=%d name=%s avail=%s stok_used=%s pivot=%s\n", $c->id, $c->name, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');
}

// Now simulate editing the order: change status to pending via update() flow
$ctl = new OrderController();
// Create a fake request with status pending; the update() expects OrderRequest fields, but only status used here
$req = Request::create('/', 'POST', ['status' => 'pending', 'customer_id' => $order->customer_id, 'product_id' => $order->product_id, 'delivery_date' => $order->delivery_date]);
$res = $ctl->update($req, $order);

$order->refresh(); $order->load('components');

echo "\nAfter update() -> pending:\n";
foreach ($order->components as $c) {
    echo sprintf("COMP - id=%d name=%s avail=%s stok_used=%s pivot=%s\n", $c->id, $c->name, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');
}

// Now simulate editing the order back to disewa via update() with same components in text
$req2 = Request::create('/', 'POST', ['status' => 'disewa', 'customer_id' => $order->customer_id, 'product_id' => $order->product_id, 'delivery_date' => $order->delivery_date]);
$res2 = $ctl->update($req2, $order);

$order->refresh(); $order->load('components');

echo "\nAfter update() -> disewa (re-apply):\n";
foreach ($order->components as $c) {
    echo sprintf("COMP - id=%d name=%s avail=%s stok_used=%s pivot=%s\n", $c->id, $c->name, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');
}

echo "\nDone.\n";
