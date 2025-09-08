<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Component;
use App\Models\Order;

$compId = $argv[1] ?? 121;
$comp = Component::find($compId);
if (! $comp) { echo "Component {$compId} not found\n"; exit(1); }

$orders = Order::whereHas('components', function($q) use ($compId) { $q->where('component_id', $compId); })->get();
if ($orders->isEmpty()) { echo "No orders found with component {$compId}\n"; exit(0); }
foreach ($orders as $o) {
    echo "Found order id={$o->id} status={$o->status}\n";
}
