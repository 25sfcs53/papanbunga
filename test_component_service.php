<?php

// Test script untuk ComponentStockService
// Jalankan dengan: php test_component_service.php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel aplikasi
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ComponentStockService;
use App\Models\Order;
use App\Models\Component;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Test ComponentStockService ===\n\n";
    
    $service = new ComponentStockService();
    
    // Test 1: Parse message
    echo "1. Testing parseMessage():\n";
    $testMessage = "Selamat Ulang Tahun Budi! Semoga panjang umur dan bahagia selalu.";
    echo "Message: $testMessage\n";
    
    $componentsUsed = $service->parseMessage($testMessage);
    echo "Components needed:\n";
    foreach ($componentsUsed as $componentId => $quantity) {
        $component = Component::find($componentId);
        if ($component) {
            echo "- {$component->name} ({$component->type}): {$quantity} buah\n";
        }
    }
    echo "\n";
    
    // Test 2: Check current component stock
    echo "2. Current component stock:\n";
    $components = Component::all();
    foreach ($components as $component) {
        echo "- {$component->name}: {$component->quantity_available} available\n";
    }
    echo "\n";
    
    // Test 3: Find an order to test with
    echo "3. Testing with existing order:\n";
    $order = Order::latest()->first();
    
    if ($order) {
        echo "Testing with Order ID: {$order->id}\n";
        $orderMessage = $order->message ?? '';
        echo "Order message: {$orderMessage}\n";

        // Parse the order message (skip if empty)
        if (trim($orderMessage) === '') {
            echo "Order message is empty; skipping parse.\n";
            $orderComponents = [];
        } else {
            $orderComponents = $service->parseMessage($orderMessage);
        }
        echo "Components needed for this order:\n";
        foreach ($orderComponents as $componentId => $quantity) {
            $component = Component::find($componentId);
            if ($component) {
                echo "- {$component->name}: {$quantity} needed (available: {$component->quantity_available})\n";
            }
        }
        
        // Test stock update (but don't actually run it to avoid changing data)
        echo "\nWould update stock on rent, but skipping to preserve data.\n";
        echo "To actually test stock update, uncomment the following line:\n";
        echo "// \$result = \$service->updateStockOnRent({$order->id});\n";
            // Test stock update (this will modify data)
            echo "\nRunning stock update on rent for this order (will modify DB)...\n";
            $result = $service->updateStockOnRent($order->id);

            echo "Stock update result:\n";
            if (isset($result['success']) && $result['success'] === true) {
                echo "- Success: {$result['message']}\n";
                if (!empty($result['components_used']) && is_array($result['components_used'])) {
                    foreach ($result['components_used'] as $used) {
                        // used may contain id and quantity_used or component data
                        $compId = $used['id'] ?? ($used['component_id'] ?? null);
                        $qtyUsed = $used['quantity_used'] ?? ($used['quantity'] ?? null);
                        $comp = $compId ? Component::find($compId) : null;
                        $compName = $comp ? $comp->name : ($used['name'] ?? 'unknown');
                        echo "  - {$compName}: {$qtyUsed} used\n";
                    }
                }
            } else {
                $msg = $result['message'] ?? 'Unknown error';
                echo "- Failed: {$msg}\n";
            }
        
    } else {
        echo "No orders found to test with.\n";
    }
    
    echo "\n=== Test completed successfully ===\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
