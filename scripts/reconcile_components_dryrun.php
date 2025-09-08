<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Component;

$rows = [];
$totalDiff = 0;
$count = 0;

$components = Component::orderBy('id')->get();
foreach ($components as $comp) {
    $pivotSum = (int) DB::table('order_components')->where('component_id', $comp->id)->sum('quantity_used');
    $stokUsed = (int) ($comp->stok_used ?? 0);
    $avail = (int) ($comp->quantity_available ?? 0);
    // Derive total as currently known
    $total = $avail + $stokUsed;
    // Desired stok_used comes from pivot sums
    $desiredStokUsed = $pivotSum;
    $desiredAvail = max(0, $total - $desiredStokUsed);

    if ($desiredStokUsed !== $stokUsed || $desiredAvail !== $avail) {
        $count++;
        $diff = $desiredStokUsed - $stokUsed;
        $totalDiff += abs($diff);
        $rows[] = [
            'id' => $comp->id,
            'name' => (string) ($comp->name ?? ''),
            'pivot_sum' => $pivotSum,
            'stok_used' => $stokUsed,
            'quantity_available' => $avail,
            'desired_stok_used' => $desiredStokUsed,
            'desired_quantity_available' => $desiredAvail,
            'sql' => sprintf("UPDATE components SET stok_used = %d, quantity_available = %d WHERE id = %d;", $desiredStokUsed, $desiredAvail, $comp->id)
        ];
    }
}

if (empty($rows)) {
    echo "No discrepancies found. All components are consistent with pivot sums.\n";
    exit(0);
}

echo "DRY-RUN RECONCILE: components with discrepancies (count={$count}). Total absolute stok_used delta={$totalDiff}.\n";
echo str_repeat('=',80) . "\n";
foreach ($rows as $r) {
    echo sprintf("id=%d name=%s pivot_sum=%d stok_used=%d avail=%d => desired stok_used=%d avail=%d\n", $r['id'], $r['name'], $r['pivot_sum'], $r['stok_used'], $r['quantity_available'], $r['desired_stok_used'], $r['desired_quantity_available']);
    echo "SQL: " . $r['sql'] . "\n\n";
}

echo str_repeat('=',80) . "\n";
echo "This is a dry-run only. To apply these updates, run the script 'reconcile_components_apply.php' which will execute the shown UPDATE statements within a transaction.\n";
