<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the application so we can use Eloquent models
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Customer;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

echo "Starting smoke script...\n";

$product = Product::where('active', true)->first();
$customer = Customer::first();
if (!$product || !$customer) {
    echo "Missing product or customer\n";
    exit(1);
}

$text = "Happy Graduation SEPTIANA NUR FADILAH, S.P selamat sukses & berkah ilmunya we proud of you from adek syakilla & shafiyya Kupu-kupu Topi Toga";

// Determine required papan qty
$requiredPapanQty = (int) ($product->required_papan_quantity ?? 0);
if ($requiredPapanQty < 1) {
    $requiredPapanQty = Str::contains(Str::lower($product->name), 'gandeng') ? 2 : 1;
}

$papan = Asset::available()->type(Asset::TYPE_PAPAN)->where('color', $product->required_papan_color)->first();
$rak = Asset::available()->type(Asset::TYPE_RAK)->where('color', $product->default_rack_color ?? $product->required_rak_color)->first();

echo "Product: {$product->id} - {$product->name}\n";
echo "Required papan qty: {$requiredPapanQty}\n";
echo "Papan asset: " . ($papan ? $papan->id : 'none') . " (avail: " . ($papan ? $papan->quantity_available : 'N/A') . ")\n";
echo "Rak asset: " . ($rak ? $rak->id : 'none') . " (avail: " . ($rak ? $rak->quantity_available : 'N/A') . ")\n";

// Parse components
$componentsToAttach = [];
$textNorm = preg_replace('/\s+/u', ' ', trim($text));

$multiCandidates = Component::whereIn('type', [Component::TYPE_KATA_SAMBUNG, Component::TYPE_HIASAN])
    ->get()
    ->filter(function ($c) { return mb_strlen($c->name) > 1; })
    ->sortByDesc(function ($c) { return mb_strlen($c->name); });

foreach ($multiCandidates as $mc) {
    $pattern = '/(?<!\\p{L})' . preg_quote($mc->name, '/') . '(?!\\p{L})/iu';
    $count = preg_match_all($pattern, $textNorm, $m);
    if ($count) {
        $componentsToAttach[$mc->id] = ($componentsToAttach[$mc->id] ?? 0) + $count;
        $textNorm = preg_replace($pattern, ' ', $textNorm);
    }
}

$chars = preg_split('//u', preg_replace('/\s+/u', '', $textNorm), -1, PREG_SPLIT_NO_EMPTY);
foreach ($chars as $ch) {
    if (preg_match('/^\\p{N}$/u', $ch)) {
        $type = Component::TYPE_ANGKA;
    } elseif (preg_match('/^\\p{Lu}$/u', $ch)) {
        $type = Component::TYPE_HURUF_BESAR;
    } elseif (preg_match('/^\\p{Ll}$/u', $ch)) {
        $type = Component::TYPE_HURUF_KECIL;
    } else {
        $type = Component::TYPE_SIMBOL;
    }

    $comp = Component::where('type', $type)->where('name', $ch)->first();
    if (!$comp) {
        if ($type === Component::TYPE_HURUF_BESAR) {
            $comp = Component::where('type', $type)->where('name', mb_strtoupper($ch))->first();
        } elseif ($type === Component::TYPE_HURUF_KECIL) {
            $comp = Component::where('type', $type)->where('name', mb_strtolower($ch))->first();
        }
    }

    if ($comp) {
        $componentsToAttach[$comp->id] = ($componentsToAttach[$comp->id] ?? 0) + 1;
    }
}

echo "\nComponents required by parsing:\n";
foreach ($componentsToAttach as $id => $q) {
    $c = Component::find($id);
    echo "- {$c->name} (id={$c->id}) => required={$q}, avail={$c->quantity_available}\n";
}

// Now simulate create+consume inside transaction but roll back to avoid persisting
DB::beginTransaction();
try {
    $order = Order::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'base_price' => $product->base_price,
        'final_price' => $product->base_price,
        'status' => 'disewa',
        'delivery_date' => date('Y-m-d'),
        'text_content' => $text,
    ]);

    if ($papan) {
        $order->assets()->attach($papan->id, ['quantity_used' => $requiredPapanQty]);
        $papan->increment('quantity_rented', $requiredPapanQty);
        echo "Attached papan id={$papan->id}, inc rented by {$requiredPapanQty}\n";
    }
    if ($rak) {
        $order->assets()->attach($rak->id, ['quantity_used' => $requiredPapanQty]);
        $rak->increment('quantity_rented', $requiredPapanQty);
        echo "Attached rak id={$rak->id}, inc rented by {$requiredPapanQty}\n";
    }

    foreach ($componentsToAttach as $cid => $qty) {
        $comp = Component::find($cid);
        if ($comp) {
            echo "Consuming {$comp->name} x{$qty} (before avail={$comp->quantity_available})\n";
            $comp->quantity_available = max(0, $comp->quantity_available - $qty);
            $comp->stok_used = ($comp->stok_used ?? 0) + $qty;
            $comp->save();
            $order->components()->syncWithoutDetaching([$comp->id => ['quantity_used' => $qty]]);
            echo "  after avail={$comp->quantity_available}\n";
        }
    }

    echo "Simulation complete, rolling back to avoid DB changes.\n";
    DB::rollBack();
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during simulation: " . $e->getMessage() . "\n";
}

echo "Smoke script finished.\n";

