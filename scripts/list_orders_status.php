<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$counts = Order::select('status', DB::raw('count(*) as cnt'))->groupBy('status')->pluck('cnt','status');
echo "Order counts by status:\n";
foreach ($counts as $status => $cnt) {
    echo " - {$status}: {$cnt}\n";
}

$disewa = Order::where('status','disewa')->with('assets')->take(5)->get();
if ($disewa->isEmpty()) {
    echo "\nNo orders with status 'disewa' found.\n";
} else {
    echo "\nSample 'disewa' orders:\n";
    foreach ($disewa as $o) {
        echo "Order id={$o->id} delivery_date={$o->delivery_date} assets_count=" . $o->assets->count() . "\n";
        foreach ($o->assets as $a) {
            echo sprintf("  - asset id=%d name=%s qty_used=%s qty_rented=%s\n", $a->id, $a->name, $a->pivot->quantity_used ?? 'N/A', $a->quantity_rented ?? 'N/A');
        }
    }
}
