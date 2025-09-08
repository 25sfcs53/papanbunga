<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Customer;
use App\Models\Component;
use App\Models\Order;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

echo "Starting status-change smoke...\n";

$product = Product::where('active', true)->first();
$customer = Customer::first();
if (!$product || !$customer) {
    echo "Missing product or customer\n";
    exit(1);
}

$text = "Happy Graduation SEPTIANA NUR FADILAH, S.P selamat sukses & berkah ilmunya we proud of you from adek syakilla & shafiyya Kupu-kupu Topi Toga";

DB::beginTransaction();
try {
    // Create a pending order with same text
    $order = Order::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'base_price' => $product->base_price,
        'final_price' => $product->base_price,
        'status' => 'pending',
        'delivery_date' => date('Y-m-d'),
        'text_content' => $text,
    ]);

    echo "Created order id={$order->id} status=pending\n";

    // Attach a subset of components as if user pre-added via quick-add (simulate existing pivot)
    // We'll attach 3 of 'a' (if exists) and 2 of 'y' to ensure delta behaviour
    $a = Component::where('type', Component::TYPE_HURUF_KECIL)->where('name', 'a')->first();
    $y = Component::where('type', Component::TYPE_HURUF_KECIL)->where('name', 'y')->first();

    $attach = [];
    if ($a) { $attach[$a->id] = ['quantity_used' => 3]; }
    if ($y) { $attach[$y->id] = ['quantity_used' => 2]; }

    if (!empty($attach)) {
        $order->components()->syncWithoutDetaching($attach);
        echo "Attached pre-pivot: ";
        foreach ($attach as $id => $p) {
            $c = Component::find($id);
            echo "{$c->name}(id={$id})={$p['quantity_used']} ";
        }
        echo "\n";
    }

    // Now call controller changeStatus
    $controller = new OrderController();
    $req = Request::create('/orders/' . $order->id . '/status', 'POST', ['status' => 'disewa']);

    echo "Invoking changeStatus to 'disewa'...\n";
    $resp = $controller->changeStatus($req, $order);

    if (is_object($resp)) {
        echo "Controller returned object of class " . get_class($resp) . "\n";
    } else {
        echo "Controller returned: ";
        var_export($resp);
        echo "\n";
    }

    // Show resulting pivot and component samples
    $order->load('components');
    echo "Resulting pivot for order id={$order->id}:\n";
    foreach ($order->components as $c) {
        echo "- {$c->name} pivot={$c->pivot->quantity_used}\n";
    }

    DB::rollBack(); // rollback to avoid lasting changes
    echo "Rolled back transaction.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
