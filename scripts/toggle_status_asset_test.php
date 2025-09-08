<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;

echo "Starting toggle_status_asset_test.php\n\n";

$order = Order::where('status', 'disewa')->with(['assets', 'components'])->first();
if (! $order) {
    echo "No order found with status 'disewa'. Exiting.\n";
    exit(0);
}

echo "Order id={$order->id} found.\n\nAssets BEFORE:\n";
foreach ($order->assets as $a) {
    echo sprintf(" - id=%d name='%s' quantity_rented=%d pivot_qty=%d\n", $a->id, $a->name, $a->quantity_rented ?? 0, $a->pivot->quantity_used ?? 0);
}

echo "\nComponents BEFORE:\n";
foreach ($order->components as $c) {
    echo sprintf(" - id=%d name='%s' stok_used=%d pivot_qty=%d\n", $c->id, $c->name, $c->stok_used ?? 0, $c->pivot->quantity_used ?? 0);
}

// Call controller changeStatus to set to pending
$request = Request::create('/', 'POST', ['status' => 'pending']);
$controller = new OrderController();
$response = $controller->changeStatus($request, $order);

echo "\nController response: ";
if (is_object($response)) {
    echo get_class($response) . PHP_EOL;
} else {
    echo gettype($response) . PHP_EOL;
}

// Refresh models
$order->refresh();
$order->load(['assets','components']);

echo "\nOrder status after: {$order->status}\n\nAssets AFTER:\n";
foreach ($order->assets as $a) {
    echo sprintf(" - id=%d name='%s' quantity_rented=%d pivot_qty=%s\n", $a->id, $a->name, $a->quantity_rented ?? 0, isset($a->pivot) ? ($a->pivot->quantity_used ?? 'N/A') : 'detached');
}

echo "\nComponents AFTER:\n";
foreach ($order->components as $c) {
    echo sprintf(" - id=%d name='%s' stok_used=%d pivot_qty=%s\n", $c->id, $c->name, $c->stok_used ?? 0, isset($c->pivot) ? ($c->pivot->quantity_used ?? 'N/A') : 'detached');
}

echo "\nTest complete.\n";
