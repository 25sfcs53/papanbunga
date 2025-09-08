<?php

namespace App\Http\Controllers;

use App\Services\ComponentStockService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderStockController extends Controller
{
    protected $componentStockService;

    public function __construct(ComponentStockService $componentStockService)
    {
        $this->componentStockService = $componentStockService;
    }

    /**
     * Update order status and handle component stock changes
     */
    public function updateOrderStatus(Request $request, int $orderId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,disewa,selesai'
        ]);

        try {
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Update order status
            $order->status = $newStatus;
            $order->save();

            $result = ['order_updated' => true, 'stock_updated' => false, 'message' => 'Status order berhasil diupdate'];

            // Handle stock changes when status changes to 'disewa'
            if ($newStatus === 'disewa' && $oldStatus !== 'disewa') {
                $stockResult = $this->componentStockService->updateStockOnRent($orderId);
                
                if (!$stockResult['success']) {
                    // Rollback order status if stock update fails
                    $order->status = $oldStatus;
                    $order->save();
                    
                    return response()->json([
                        'success' => false,
                        'message' => $stockResult['message']
                    ], 400);
                }
                
                $result['stock_updated'] = true;
                $result['components_used'] = $stockResult['components_used'];
                $result['message'] = 'Status order dan stok komponen berhasil diupdate';
            }

            // Handle stock restoration when status changes from 'disewa' to something else
            if ($oldStatus === 'disewa' && $newStatus !== 'disewa') {
                $restoreResult = $this->componentStockService->restoreStockOnReturn($orderId);
                
                if ($restoreResult['success']) {
                    $result['stock_restored'] = true;
                    $result['message'] = 'Status order diupdate dan stok komponen dikembalikan';
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse message and preview component usage without updating stock
     */
    public function previewComponentUsage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        try {
            $componentsUsage = $this->componentStockService->parseMessage($request->message);
            
            $preview = [];
            foreach ($componentsUsage as $componentId => $quantity) {
                $component = \App\Models\Component::find($componentId);
                if ($component) {
                    $preview[] = [
                        'id' => $componentId,
                        'name' => $component->name,
                        'type' => $component->type,
                        'quantity_needed' => $quantity,
                        'available_stock' => $component->quantity_available,
                        'sufficient_stock' => $component->quantity_available >= $quantity
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => $request->message,
                'components_preview' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual stock update for specific order (for testing)
     */
    public function updateStockManual(int $orderId): JsonResponse
    {
        try {
            $result = $this->componentStockService->updateStockOnRent($orderId);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'components_used' => $result['components_used'] ?? []
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
