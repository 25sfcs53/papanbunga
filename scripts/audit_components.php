<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Component;
use Illuminate\Support\Facades\DB;

echo "Starting components audit...\n\n";

$components = Component::orderBy('type')->orderBy('name')->get();
$errors = [];
foreach ($components as $c) {
    $pivotSum = DB::table('order_components')->where('component_id', $c->id)->sum('quantity_used');
    $stokUsed = (int) ($c->stok_used ?? 0);
    if ($pivotSum !== $stokUsed) {
        $errors[] = [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'pivot_sum' => (int)$pivotSum,
            'stok_used' => $stokUsed,
            'avail' => (int)($c->quantity_available ?? 0),
        ];
    }
}

if (empty($errors)) {
    echo "No discrepancies found: all component pivot sums match stok_used.\n";
} else {
    echo "Found discrepancies (pivot sum != stok_used):\n";
    foreach ($errors as $e) {
        echo sprintf("- id=%d name='%s' type=%s pivot_sum=%d stok_used=%d avail=%d\n", $e['id'], $e['name'], $e['type'], $e['pivot_sum'], $e['stok_used'], $e['avail']);
    }
}

// Also totals per type
$types = Component::select('type')->distinct()->pluck('type');
echo "\nType-level totals:\n";
foreach ($types as $t) {
    $pivotSumType = DB::table('order_components')
        ->join('components', 'order_components.component_id', '=', 'components.id')
        ->where('components.type', $t)
        ->sum('order_components.quantity_used');
    $stokUsedType = Component::where('type', $t)->sum('stok_used');
    echo sprintf("- type=%s pivot_sum=%d stok_used=%d\n", $t, $pivotSumType, $stokUsedType);
}

echo "\nAudit complete.\n";
