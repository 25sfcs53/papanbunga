<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$orderId = 8;
$order = Order::with(['assets','components'])->find($orderId);
if (! $order) { echo "Order id={$orderId} not found\n"; exit(1); }

echo "Setting order id={$order->id} to disewa state...\n";
DB::transaction(function () use ($order) {
    $order->status = 'disewa';
    $order->save();

    foreach ($order->assets as $asset) {
        // ensure quantity_rented at least matches pivot
        $need = (int) ($asset->pivot->quantity_used ?? 0);
        if ($need > 0) {
            $asset->quantity_rented = max( (int)($asset->quantity_rented ?? 0), $need );
            $asset->save();
        }
    }

    foreach ($order->components as $comp) {
        $qty = (int) ($comp->pivot->quantity_used ?? 0);
        if ($qty > 0) {
            $comp->stok_used = max((int)($comp->stok_used ?? 0), $qty);
            $comp->quantity_available = max(0, (int)($comp->quantity_available ?? 0) - $qty);
            $comp->save();
        }
    }
});

$order->refresh(); $order->load(['assets','components']);

echo "\nBEFORE TOGGLE (should be disewa): status={$order->status}\n";
foreach ($order->assets as $a) echo sprintf("ASSET - id=%d rented=%s pivot=%s\n", $a->id, $a->quantity_rented ?? 'N/A', $a->pivot->quantity_used ?? 'N/A');
foreach ($order->components as $c) echo sprintf("COMP  - id=%d stok_used=%s pivot=%s\n", $c->id, $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');

$req = Request::create('/', 'POST', ['status' => 'pending']);
$ctl = new OrderController();
$res = $ctl->changeStatus($req, $order);

$order->refresh(); $order->load(['assets','components']);

echo "\nAFTER TOGGLE to pending: status={$order->status}\n";
foreach ($order->assets as $a) echo sprintf("ASSET - id=%d rented=%s pivot=%s\n", $a->id, $a->quantity_rented ?? 'N/A', isset($a->pivot)? $a->pivot->quantity_used : 'detached');
foreach ($order->components as $c) echo sprintf("COMP  - id=%d stok_used=%s pivot=%s\n", $c->id, $c->stok_used ?? 'N/A', isset($c->pivot)? $c->pivot->quantity_used : 'detached');

echo "\nDone.\n";
