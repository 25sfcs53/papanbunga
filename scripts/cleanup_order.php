<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$argv = $_SERVER['argv'] ?? [];
$orderId = isset($argv[1]) ? (int) $argv[1] : 0;
if ($orderId <= 0) {
    echo "Usage: php scripts/cleanup_order.php <order_id>\n";
    exit(1);
}

echo "Cleaning up order id={$orderId}...\n";

$order = Order::with(['assets', 'components'])->find($orderId);
if (!$order) {
    echo "Order not found.\n";
    exit(1);
}

DB::beginTransaction();
try {
    // Reverse assets: decrement quantity_rented by pivot quantity_used
    foreach ($order->assets as $asset) {
        $qty = (int) ($asset->pivot->quantity_used ?? 0);
        if ($qty > 0) {
            $asset->decrement('quantity_rented', $qty);
            echo "Reverted asset id={$asset->id} quantity_rented by {$qty}\n";
        }
    }

    // Reverse components: for each pivot, add back quantity_available and reduce stok_used
    foreach ($order->components as $comp) {
        $qty = (int) ($comp->pivot->quantity_used ?? 0);
        if ($qty > 0) {
            $comp->quantity_available = $comp->quantity_available + $qty;
            if (isset($comp->stok_used)) {
                $comp->stok_used = max(0, ($comp->stok_used ?? 0) - $qty);
            }
            $comp->save();
            echo "Reverted component id={$comp->id} by {$qty} (avail now={$comp->quantity_available})\n";
        }
    }

    // Detach pivot records and assets
    $order->assets()->detach();
    $order->components()->detach();

    // Finally delete the order
    $order->delete();

    DB::commit();
    echo "Cleanup complete — order deleted.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Cleanup failed: " . $e->getMessage() . "\n";
    exit(1);
}
