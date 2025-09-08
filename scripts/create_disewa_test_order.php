<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Asset;
use App\Models\Component;
use Illuminate\Support\Str;

echo "Creating test order with status 'disewa'...\n";

$customer = Customer::first();
if (! $customer) {
    $customer = Customer::create(['name' => 'Test Customer', 'email' => 'test@example.com']);
    echo "Created test customer id={$customer->id}\n";
}

$product = Product::where('active', true)->first() ?? Product::first();
if (! $product) {
    echo "No product found. Aborting.\n"; exit(1);
}

$asset = Asset::available()->first();
if (! $asset) {
    echo "No available asset found. Aborting.\n"; exit(1);
}

$component = Component::where('quantity_available', '>', 0)->first();
if (! $component) {
    echo "No available component found. Aborting.\n"; exit(1);
}

// Create order
$order = Order::create([
    'customer_id' => $customer->id,
    'product_id' => $product->id,
    'base_price' => (float) ($product->base_price ?? 0),
    'discount_type' => null,
    'discount_value' => 0,
    'final_price' => (float) ($product->base_price ?? 0),
    'text_content' => 'Test Order',
    'shipping_address' => 'Test address',
    'status' => 'disewa',
    'delivery_date' => now()->toDateString(),
]);

// Attach one unit of asset
$order->assets()->attach($asset->id, ['quantity_used' => 1]);
$asset->increment('quantity_rented', 1);

// Attach one unit of component and adjust stock
$order->components()->attach($component->id, ['quantity_used' => 1]);
$component->quantity_available = max(0, ($component->quantity_available ?? 0) - 1);
$component->stok_used = ($component->stok_used ?? 0) + 1;
$component->save();

echo "Created order id={$order->id} with asset id={$asset->id} and component id={$component->id}\n";
