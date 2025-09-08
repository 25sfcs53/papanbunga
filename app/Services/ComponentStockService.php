<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComponentStockService
{
    /**
     * Parse message/ucapan to extract component usage
     * 
     * @param string $message
     * @return array [component_id => quantity_needed]
     */
    public function parseMessage(string $message): array
    {
        $componentsUsage = [];
        
        if (trim($message) === '') {
            return $componentsUsage;
        }

        // Normalize whitespace
        $messageNorm = preg_replace('/\s+/u', ' ', trim($message));
        
        // Step 1: Extract multi-word components (kata_sambung, hiasan)
        // Sort by length descending to avoid partial matches
        $multiWordComponents = Component::whereIn('type', ['kata_sambung', 'hiasan'])
            ->get()
            ->filter(fn($c) => mb_strlen($c->name) > 1)
            ->sortByDesc(fn($c) => mb_strlen($c->name));

        foreach ($multiWordComponents as $component) {
            // Use word boundaries to match complete words/phrases
            $pattern = '/(?<!\p{L})' . preg_quote($component->name, '/') . '(?!\p{L})/iu';
            $matchCount = preg_match_all($pattern, $messageNorm, $matches);
            
            if ($matchCount > 0) {
                $componentsUsage[$component->id] = ($componentsUsage[$component->id] ?? 0) + $matchCount;
                // Remove matched text to avoid double-counting characters
                $messageNorm = preg_replace($pattern, ' ', $messageNorm);
                
                Log::info("ComponentStockService: Found multi-word component", [
                    'component' => $component->name,
                    'type' => $component->type,
                    'count' => $matchCount
                ]);
            }
        }

        // Step 2: Parse remaining individual characters (excluding spaces)
        $remainingChars = preg_split('//u', preg_replace('/\s+/u', '', $messageNorm), -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($remainingChars as $char) {
            $componentType = $this->determineCharacterType($char);
            
            if ($componentType) {
                $component = $this->findComponentByTypeAndName($componentType, $char);
                
                if ($component) {
                    $componentsUsage[$component->id] = ($componentsUsage[$component->id] ?? 0) + 1;
                    
                    Log::info("ComponentStockService: Found character component", [
                        'char' => $char,
                        'component' => $component->name,
                        'type' => $component->type
                    ]);
                }
            }
        }

        return $componentsUsage;
    }

    /**
     * Determine character type based on Unicode properties
     * 
     * @param string $char
     * @return string|null
     */
    private function determineCharacterType(string $char): ?string
    {
        if (preg_match('/^\p{N}$/u', $char)) {
            return 'angka';
        } elseif (preg_match('/^\p{Lu}$/u', $char)) {
            return 'huruf_besar';
        } elseif (preg_match('/^\p{Ll}$/u', $char)) {
            return 'huruf_kecil';
        } elseif (preg_match('/^[\p{P}\p{S}]$/u', $char)) {
            return 'simbol';
        }
        
        return null;
    }

    /**
     * Find component by type and name with case normalization
     * 
     * @param string $type
     * @param string $name
     * @return Component|null
     */
    private function findComponentByTypeAndName(string $type, string $name): ?Component
    {
        // Try exact match first
        $component = Component::where('type', $type)->where('name', $name)->first();
        
        if (!$component) {
            // Try normalized case matching
            if ($type === 'huruf_besar') {
                $component = Component::where('type', $type)->where('name', mb_strtoupper($name))->first();
            } elseif ($type === 'huruf_kecil') {
                $component = Component::where('type', $type)->where('name', mb_strtolower($name))->first();
            }
        }
        
        return $component;
    }

    /**
     * Update component stock when order status changes to 'disewa'
     * 
     * @param int $orderId
     * @return array ['success' => bool, 'message' => string, 'components_used' => array]
     */
    public function updateStockOnRent(int $orderId): array
    {
        try {
            $order = Order::find($orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order tidak ditemukan',
                    'components_used' => []
                ];
            }

            // Check if order status is 'disewa'
            if (strtolower($order->status) !== 'disewa') {
                return [
                    'success' => false,
                    'message' => 'Order status bukan disewa, stok tidak berubah',
                    'components_used' => []
                ];
            }

            // Use text_content field (adjust field name as needed)
            $message = $order->text_content ?? '';
            
            if (trim($message) === '') {
                return [
                    'success' => true,
                    'message' => 'Tidak ada ucapan untuk diproses',
                    'components_used' => []
                ];
            }

            // Parse message to get component usage
            $componentsUsage = $this->parseMessage($message);
            
            if (empty($componentsUsage)) {
                return [
                    'success' => true,
                    'message' => 'Tidak ada komponen yang diperlukan dari ucapan',
                    'components_used' => []
                ];
            }

            // Validate stock availability before making changes
            $stockShortages = $this->validateStockAvailability($componentsUsage);
            
            if (!empty($stockShortages)) {
                return [
                    'success' => false,
                    'message' => 'Stok tidak mencukupi: ' . implode('; ', $stockShortages),
                    'components_used' => []
                ];
            }

            // Perform stock update and pivot recording in transaction
            $result = DB::transaction(function () use ($order, $componentsUsage) {
                $componentsUsed = [];
                
                foreach ($componentsUsage as $componentId => $quantityNeeded) {
                    $component = Component::lockForUpdate()->find($componentId);
                    
                    if (!$component) {
                        throw new \Exception("Component ID {$componentId} tidak ditemukan");
                    }
                    
                    // Double-check stock availability (race condition protection)
                    if ($component->quantity_available < $quantityNeeded) {
                        throw new \Exception("Stok {$component->name} tidak mencukupi (tersedia: {$component->quantity_available}, dibutuhkan: {$quantityNeeded})");
                    }
                    
                    // Update component stock
                    $component->quantity_available -= $quantityNeeded;
                    $component->stok_used += $quantityNeeded;
                    $component->save();
                    
                    // Record in pivot table (update if exists, create if not)
                    $existingPivot = DB::table('order_components')
                        ->where('order_id', $order->id)
                        ->where('component_id', $componentId)
                        ->first();
                    
                    if ($existingPivot) {
                        DB::table('order_components')
                            ->where('order_id', $order->id)
                            ->where('component_id', $componentId)
                            ->update([
                                'quantity_used' => $existingPivot->quantity_used + $quantityNeeded,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('order_components')->insert([
                            'order_id' => $order->id,
                            'component_id' => $componentId,
                            'quantity_used' => $quantityNeeded,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    
                    $componentsUsed[] = [
                        'id' => $componentId,
                        'name' => $component->name,
                        'type' => $component->type,
                        'quantity_used' => $quantityNeeded,
                        'remaining_stock' => $component->quantity_available
                    ];
                    
                    Log::info("ComponentStockService: Updated stock for component", [
                        'order_id' => $order->id,
                        'component_id' => $componentId,
                        'component_name' => $component->name,
                        'quantity_used' => $quantityNeeded,
                        'remaining_stock' => $component->quantity_available
                    ]);
                }
                
                return $componentsUsed;
            });

            return [
                'success' => true,
                'message' => 'Stok komponen berhasil diperbarui',
                'components_used' => $result
            ];

        } catch (\Exception $e) {
            Log::error("ComponentStockService: Error updating stock", [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'components_used' => []
            ];
        }
    }

    /**
     * Validate stock availability for components
     * 
     * @param array $componentsUsage [component_id => quantity_needed]
     * @return array Array of shortage messages
     */
    private function validateStockAvailability(array $componentsUsage): array
    {
        $shortages = [];
        
        foreach ($componentsUsage as $componentId => $quantityNeeded) {
            $component = Component::find($componentId);
            
            if (!$component) {
                $shortages[] = "Component ID {$componentId} tidak ditemukan";
                continue;
            }
            
            if ($component->quantity_available < $quantityNeeded) {
                $shortages[] = "{$component->name} (tersedia: {$component->quantity_available}, dibutuhkan: {$quantityNeeded})";
            }
        }
        
        return $shortages;
    }

    /**
     * Restore component stock when order status changes back from 'disewa'
     * (Optional method for reversing stock changes)
     * 
     * @param int $orderId
     * @return array
     */
    public function restoreStockOnReturn(int $orderId): array
    {
        try {
            $order = Order::find($orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ];
            }

            // Get components used for this order
            $pivotEntries = DB::table('order_components')
                ->where('order_id', $orderId)
                ->get();

            if ($pivotEntries->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'Tidak ada komponen untuk dikembalikan'
                ];
            }

            DB::transaction(function () use ($pivotEntries) {
                foreach ($pivotEntries as $pivot) {
                    $component = Component::lockForUpdate()->find($pivot->component_id);
                    
                    if ($component) {
                        $component->quantity_available += $pivot->quantity_used;
                        $component->stok_used -= $pivot->quantity_used;
                        $component->save();
                        
                        Log::info("ComponentStockService: Restored stock for component", [
                            'component_id' => $component->id,
                            'component_name' => $component->name,
                            'quantity_restored' => $pivot->quantity_used,
                            'new_available' => $component->quantity_available
                        ]);
                    }
                }
                
                // Remove pivot entries
                DB::table('order_components')->where('order_id', $pivotEntries->first()->order_id)->delete();
            });

            return [
                'success' => true,
                'message' => 'Stok komponen berhasil dikembalikan'
            ];

        } catch (\Exception $e) {
            Log::error("ComponentStockService: Error restoring stock", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }
}
