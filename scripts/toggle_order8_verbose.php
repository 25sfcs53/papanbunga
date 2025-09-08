<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;

$orderId = 8;
$order = Order::with(['assets','components'])->find($orderId);
if (! $order) { echo "Order id={$orderId} not found\n"; exit(1); }

echo "Order id={$order->id} status BEFORE={$order->status}\n\n";
foreach ($order->assets as $a) {
    echo sprintf("ASSET BEFORE - id=%d name=%s quantity_rented=%s pivot_qty=%s\n", $a->id, $a->name, $a->quantity_rented ?? 'N/A', $a->pivot->quantity_used ?? 'N/A');
}
foreach ($order->components as $c) {
    echo sprintf("COMP BEFORE - id=%d name=%s stok_used=%s pivot_qty=%s\n", $c->id, $c->name, $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');
}

$req = Request::create('/', 'POST', ['status' => 'pending']);
$ctl = new OrderController();
$res = $ctl->changeStatus($req, $order);

$order->refresh(); $order->load(['assets','components']);

echo "\nOrder id={$order->id} status AFTER={$order->status}\n\n";
foreach ($order->assets as $a) {
    echo sprintf("ASSET AFTER  - id=%d name=%s quantity_rented=%s pivot_qty=%s\n", $a->id, $a->name, $a->quantity_rented ?? 'N/A', isset($a->pivot)? $a->pivot->quantity_used : 'detached');
}
foreach ($order->components as $c) {
    echo sprintf("COMP AFTER  - id=%d name=%s stok_used=%s pivot_qty=%s\n", $c->id, $c->name, $c->stok_used ?? 'N/A', isset($c->pivot)? $c->pivot->quantity_used : 'detached');
}

echo "\nDone.\n";
