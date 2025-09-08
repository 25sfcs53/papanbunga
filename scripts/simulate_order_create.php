<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\OrderController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Component;
use Illuminate\Http\Request;

// Setup test data: create customer, product, components (in the current DB)
$customer = Customer::factory()->create();
$product = Product::factory()->create(['active' => true, 'base_price' => 100000]);
$compA = Component::factory()->create(['name' => 'A', 'type' => 'huruf_besar', 'quantity_available' => 10]);
$compB = Component::factory()->create(['name' => 'B', 'type' => 'huruf_besar', 'quantity_available' => 10]);
$compC = Component::factory()->create(['name' => 'C', 'type' => 'huruf_besar', 'quantity_available' => 10]);
$compHeart = Component::factory()->create(['name' => '❤️', 'type' => 'hiasan', 'quantity_available' => 5]);

$payload = [
    'customer_id' => $customer->id,
    'product_id' => $product->id,
    'text_content' => "ABC ❤️",
    'delivery_date' => now()->addDays(3)->format('Y-m-d'),
    'shipping_address' => 'Jl Test',
    'status' => 'pending',
    'discount_type' => null,
    'discount_value' => 0,
    'summary' => 'test',
];

// We will instantiate controller and call store() with a Request built from payload
// Replicate core of store() without FormRequest so we can run it from CLI
$product = Product::find($payload['product_id']);
$basePrice = (float) $product->base_price;
$finalPrice = $basePrice; // skip discount calculus for simulation

// Parse components from text similar to controller (multi-candidates then chars)
$componentsToAttach = [];
$text = $payload['text_content'] ?? '';
if (trim($text) !== '') {
    $textNorm = preg_replace('/\s+/u', ' ', trim($text));

    $multiCandidates = Component::whereIn('type', ['kata_sambung','hiasan'])
        ->get()
        ->filter(fn($c) => mb_strlen($c->name) > 1)
        ->sortByDesc(fn($c) => mb_strlen($c->name));

    foreach ($multiCandidates as $mc) {
        $pattern = '/(?<!\p{L})' . preg_quote($mc->name, '/') . '(?!\p{L})/iu';
        $count = preg_match_all($pattern, $textNorm, $matches);
        if ($count) {
            $componentsToAttach[$mc->id] = ($componentsToAttach[$mc->id] ?? 0) + $count;
            $textNorm = preg_replace($pattern, ' ', $textNorm);
        }
    }

    $chars = preg_split('//u', preg_replace('/\s+/u', '', $textNorm), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($chars as $ch) {
        if (preg_match('/^\p{N}$/u', $ch)) {
            $type = 'angka';
        } elseif (preg_match('/^\p{Lu}$/u', $ch)) {
            $type = 'huruf_besar';
        } elseif (preg_match('/^\p{Ll}$/u', $ch)) {
            $type = 'huruf_kecil';
        } else {
            $type = 'simbol';
        }

        $comp = Component::where('type', $type)->where('name', $ch)->first();
        if (!$comp) {
            if ($type === 'huruf_besar') $comp = Component::where('type', $type)->where('name', mb_strtoupper($ch))->first();
            if ($type === 'huruf_kecil') $comp = Component::where('type', $type)->where('name', mb_strtolower($ch))->first();
        }
        if ($comp) $componentsToAttach[$comp->id] = ($componentsToAttach[$comp->id] ?? 0) + 1;
    }
}

// Create order
$order = \App\Models\Order::create([
    'customer_id' => $payload['customer_id'],
    'product_id' => $payload['product_id'],
    'base_price' => $basePrice,
    'discount_type' => null,
    'discount_value' => 0,
    'final_price' => $finalPrice,
    'text_content' => $payload['text_content'],
    'shipping_address' => $payload['shipping_address'],
    'status' => $payload['status'],
    'delivery_date' => $payload['delivery_date'],
]);

// Normalize desired components and call controller helper
$desired = [];
foreach ($componentsToAttach as $id => $qty) {
    $desired[$id] = $qty;
}

// Call the controller helper to sync stock and pivot
// Inline syncComponentStockAndPivot logic here because method is private
$order->load('components');
$currentComponents = $order->components->mapWithKeys(function ($item) {
    return [$item->id => ['quantity_used' => $item->pivot->quantity_used]];
});

$allIds = collect(array_keys($desired))->union($currentComponents->keys());
foreach ($allIds as $id) {
    $desiredQty = (int) ($desired[$id] ?? 0);
    $currentQty = (int) ($currentComponents[$id]['quantity_used'] ?? 0);
    $delta = $desiredQty - $currentQty;
    if ($delta === 0) continue;
    $component = Component::find($id);
    if (!$component) continue;
    if ($delta > 0) {
        if ($component->quantity_available < $delta) {
            echo "Insufficient stock for {$component->name}: need {$delta}, available {$component->quantity_available}\n";
            continue;
        }
        $component->decrement('quantity_available', $delta);
    } else {
        $component->increment('quantity_available', abs($delta));
    }
}

// Sync pivot to desired state
$syncArr = [];
foreach ($desired as $id => $qty) {
    $syncArr[$id] = ['quantity_used' => $qty];
}
$order->components()->sync($syncArr);

// Reload order
$order = \App\Models\Order::with('components')->find($order->id);
if (!$order) { echo "No order created.\n"; exit(1); }

echo "Order id={$order->id} status={$order->status}\n";
foreach ($order->components as $c) {
    echo "Component: {$c->name} id={$c->id} qty_used={$c->pivot->quantity_used} available={$c->quantity_available}\n";
}

echo "Done\n";
