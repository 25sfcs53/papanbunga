<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Asset;
use App\Models\Component;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Daftar pesanan.
     */
    public function index(): View
    {
        $search = request('search');

        $orders = Order::with(['customer', 'product', 'assets'])
            ->when($search, function ($query, $search) {
                return $query->where('status', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest('delivery_date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', compact('orders', 'search'));
    }



    /**
     * Form create pesanan.
     */
    public function create(): View
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products = Product::where('active', true)->orderBy('name')->get(); // Only active products
        $components = Component::orderBy('name')->get();
        $basePrice = 0;

        return view('orders.create', compact('customers', 'products', 'components', 'basePrice'));
    }



    /**
     * Simpan pesanan baru (baseline: hitung harga final dari diskon).
     */
    public function store(OrderRequest $request): RedirectResponse
    {
    // Debug log to confirm the store route is invoked
    Log::info('OrderController@store invoked', ['ip' => request()->ip(), 'url' => request()->fullUrl()]);

    $validated = $request->validated();

        $product = Product::findOrFail($validated['product_id']);
        $basePrice = (float) $product->base_price;
        $finalPrice = $this->calculateFinalPrice(
            $basePrice,
            $validated['discount_type'] ?? null,
            isset($validated['discount_value']) ? (float) $validated['discount_value'] : 0.0
        );

        // --- Start Asset Allocation Logic ---
        $assetsToAllocate = [];

        // Some products (named/typed 'gandeng') require 2 papan. Use explicit
        // product.required_papan_quantity if present, otherwise infer from name.
        $requiredPapanQty = (int) ($product->required_papan_quantity ?? 0);
        if ($requiredPapanQty < 1) {
            $requiredPapanQty = str_contains(strtolower($product->name), 'gandeng') ? 2 : 1;
        }

        // 1. Find required Papan Asset (match color)
        $papanAsset = Asset::available()->type(Asset::TYPE_PAPAN)
                            ->where('color', $product->required_papan_color)
                            ->first();

        if (!$papanAsset || $papanAsset->quantity_available < $requiredPapanQty) {
            return back()->withInput()->withErrors(['product_id' => 'Stok papan hias warna ' . $product->required_papan_color . ' tidak mencukupi.']);
        }
        $assetsToAllocate[] = [
            'asset' => $papanAsset,
            'quantity' => $requiredPapanQty
        ];

        // 2. Find required Rak Asset (assuming 1 rak per papan)
        $rakAsset = Asset::available()->type(Asset::TYPE_RAK)
                         ->where('color', $product->default_rack_color ?? $product->required_rak_color)
                         ->first();

        if (!$rakAsset || $rakAsset->quantity_available < $requiredPapanQty) {
            return back()->withInput()->withErrors(['product_id' => 'Stok rak warna ' . ($product->default_rack_color ?? $product->required_rak_color) . ' tidak mencukupi.']);
        }
        $assetsToAllocate[] = [
            'asset' => $rakAsset,
            'quantity' => $requiredPapanQty
        ];
        // --- End Asset Allocation Logic ---

        $payload = [
            'customer_id'    => $validated['customer_id'],
            'product_id'     => $validated['product_id'],
            'base_price'     => $basePrice,
            'discount_type'  => $validated['discount_type'] ?? null,
            'discount_value' => $validated['discount_value'] ?? 0.0,
            'final_price'    => $finalPrice,
            'text_content'   => $validated['text_content'] ?? null,
            'shipping_address'=> $validated['shipping_address'] ?? null,
            // Use provided status if present, otherwise default to pending
            // Save status as the lowercase request value (defaults to 'pending')
            'status'         => strtolower($validated['status'] ?? 'pending'),
            'delivery_date'  => $validated['delivery_date'],
        ];

        // Resolve components from text_content (characters and multi-word components) and from quick-add components BEFORE transaction
        $componentsToAttach = [];

        // 1) Parse components from text_content with improved accuracy
        $text = $validated['text_content'] ?? '';
        if (trim($text) !== '') {
            // normalize whitespace
            $textNorm = preg_replace('/\s+/u', ' ', trim($text));

            // First, detect multi-character components (kata_sambung, hiasan) robustly.
            // Sort by length desc to avoid partial matches (e.g., "Topi Toga" vs "Toga").
            $multiCandidates = Component::whereIn('type', [Component::TYPE_KATA_SAMBUNG, Component::TYPE_HIASAN])
                ->get()
                ->filter(fn($c) => mb_strlen($c->name) > 1)
                ->sortByDesc(fn($c) => mb_strlen($c->name));

            foreach ($multiCandidates as $mc) {
                // Use Unicode-aware lookarounds so punctuation/hyphens don't break matching.
                $pattern = '/(?<!\p{L})' . preg_quote($mc->name, '/') . '(?!\p{L})/iu';
                $count = preg_match_all($pattern, $textNorm, $matches);
                if ($count) {
                    $componentsToAttach[$mc->id] = ($componentsToAttach[$mc->id] ?? 0) + $count;
                    // remove occurrences to avoid double-counting characters
                    $textNorm = preg_replace($pattern, ' ', $textNorm);
                }
            }

            // Then count remaining individual characters (excluding spaces)
            $chars = preg_split('//u', preg_replace('/\s+/u', '', $textNorm), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chars as $ch) {
                if (preg_match('/^\p{N}$/u', $ch)) {
                    $type = Component::TYPE_ANGKA;
                } elseif (preg_match('/^\p{Lu}$/u', $ch)) {
                    $type = Component::TYPE_HURUF_BESAR;
                } elseif (preg_match('/^\p{Ll}$/u', $ch)) {
                    $type = Component::TYPE_HURUF_KECIL;
                } else {
                    // anything else treat as simbol/hiasan fallback
                    $type = Component::TYPE_SIMBOL;
                }

                // Try direct lookup; fall back to normalized case if needed
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
        }

        // 2) Quick-add components (from request) with quantities
        if (!empty($validated['components']) && is_array($validated['components'])) {
            foreach ($validated['components'] as $c) {
                $id = (int) ($c['id'] ?? 0);
                $qty = max(1, (int) ($c['quantity'] ?? 1));
                if ($id > 0) {
                    $componentsToAttach[$id] = ($componentsToAttach[$id] ?? 0) + $qty;
                }
            }
        }

        // If status is disewa, validate availability before making DB changes
        if (($validated['status'] ?? '') === 'disewa' && !empty($componentsToAttach)) {
            $shortages = [];
            foreach ($componentsToAttach as $compId => $qty) {
                $comp = Component::find($compId);
                if (!$comp) continue;
                if ($comp->quantity_available < $qty) {
                    $shortages[] = "{$comp->name} (tersedia: {$comp->quantity_available}, dibutuhkan: {$qty})";
                }
            }
            if (!empty($shortages)) {
                return back()->withInput()->withErrors(['components' => 'Stok komponen tidak mencukupi: ' . implode('; ', $shortages)]);
            }

            // checks passed; main transaction below will handle creation, allocation, and consumption
        }

    DB::transaction(function () use ($validated, $product, &$order, $payload, $componentsToAttach) {
            // Use the prepared payload to avoid any variable scoping issues
            $order = Order::create($payload);

            // Attach assets
            $requiredPapanQty = (int) ($product->required_papan_quantity ?? 0);
            if ($requiredPapanQty < 1) {
                $requiredPapanQty = str_contains(strtolower($product->name), 'gandeng') ? 2 : 1;
            }

            $papanAsset = Asset::available()->type(Asset::TYPE_PAPAN)
                            ->where('color', $product->required_papan_color)
                            ->first();
            $rakAsset = Asset::available()->type(Asset::TYPE_RAK)
                         ->where('color', $product->default_rack_color ?? $product->required_rak_color)
                         ->first();

            if ($papanAsset) {
                $order->assets()->attach($papanAsset->id, ['quantity_used' => $requiredPapanQty]);
            }
            if ($rakAsset) {
                $order->assets()->attach($rakAsset->id, ['quantity_used' => $requiredPapanQty]);
            }

            // Attach components and consume stock. Normalize desired components as [id => qty]
            $desiredComponents = [];
            if (!empty($validated['components']) && is_array($validated['components'])) {
                foreach ($validated['components'] as $item) {
                    $id = (int) ($item['id'] ?? 0);
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    if ($id > 0) $desiredComponents[$id] = ($desiredComponents[$id] ?? 0) + $qty;
                }
            } elseif (!empty($componentsToAttach)) {
                // componentsToAttach was computed from text_content earlier and is already in id=>qty form
                $desiredComponents = $componentsToAttach;
            }

            if (!empty($desiredComponents)) {
                // Use the centralized sync method which expects id=>qty
                $this->syncComponentStockAndPivot($order, $desiredComponents);
            }
        });

        return redirect()->route('orders.index')->with('success', 'Pesanan berhasil dibuat.');
    }



    /**
     * Show order details.
     */
    public function show(Order $order): View
    {
        // Load base relations first
        $order->load(['customer', 'product', 'assets']);

        // Force-load components through the relationship to ensure pivot is attached
        try {
            $components = $order->components()->get();
            // replace relation so the view sees a proper collection with pivot
            $order->setRelation('components', $components);
        } catch (\Throwable $e) {
            // fallback: leave whatever is present
        }

        // Also fetch raw pivot rows for debugging to help trace missing attachments
        $rawPivot = DB::table('order_components')->where('order_id', $order->id)->get();

        return view('orders.show', compact('order', 'rawPivot'));
    }

    /**
     * Form edit pesanan.
     */
    public function edit(Order $order): View
    {
        // NOTE: Asset editing is disabled in this new quantity-based system for now.
        // To change assets, the order should be cancelled and recreated.
        $order->load(['customer', 'product', 'assets']);
    $customers = Customer::orderBy('name')->get(['id', 'name']);
    $products = Product::where('active', true)->orderBy('name')->get();
        // Include assets lists for the edit form. We show available assets and
        // also include assets already attached to this order so they remain selectable.
        $currentAssetIds = $order->assets()->pluck('assets.id')->toArray();

    $papanHias = Asset::where('type', Asset::TYPE_PAPAN)
            ->where(function ($q) use ($currentAssetIds) {
                $q->available();
                if (!empty($currentAssetIds)) {
                    $q->orWhereIn('id', $currentAssetIds);
                }
            })->get();

        $rak = Asset::where('type', Asset::TYPE_RAK)
            ->where(function ($q) use ($currentAssetIds) {
                $q->available();
                if (!empty($currentAssetIds)) {
                    $q->orWhereIn('id', $currentAssetIds);
                }
            })->get();

    // Components list for Quick Add modal (same as create form)
    $components = Component::orderBy('name')->get();

    return view('orders.edit', compact('order', 'customers', 'products', 'papanHias', 'rak', 'components'));
    }



    /**
     * Update pesanan (re-calc harga).
     */
    public function update(OrderRequest $request, Order $order): RedirectResponse
    {
        // NOTE: Asset editing is disabled in this new quantity-based system for now.
        // This method only updates non-asset related details.
        $data = $request->validated();

        $product = Product::findOrFail($data['product_id']);
        $basePrice = (float) $product->base_price;
        $finalPrice = $this->calculateFinalPrice(
            $basePrice,
            $data['discount_type'] ?? null,
            isset($data['discount_value']) ? (float) $data['discount_value'] : 0.0
        );
        
        // Changing the product on an existing order is complex due to stock.
        // We prevent it for now.
        if ((int)$data['product_id'] !== $order->product_id) {
             return back()->withInput()->withErrors(['product_id' => 'Mengganti produk pada pesanan yang sudah ada tidak diperbolehkan. Harap batalkan dan buat pesanan baru.']);
        }

        $payload = [
            'customer_id'    => $data['customer_id'],
            'discount_type'  => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? 0.0,
            'final_price'    => $finalPrice,
            'text_content'   => $data['text_content'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'delivery_date'  => $data['delivery_date'],
        ];
        
    // Handle status change if provided (normalize to lowercase for reliable comparisons)
    $newStatus = strtolower($data['status'] ?? null);
    $oldStatus = strtolower((string) $order->status ?? '');
        
        // If changing status to Disewa, ensure components (from text and quick-add) are available
    if ($newStatus === 'disewa' && $oldStatus !== 'disewa') {
            $componentsToAttach = [];
            $text = $data['text_content'] ?? '';

            if (trim($text) !== '') {
                $textNorm = preg_replace('/\s+/u', ' ', trim($text));

                // detect multi-char components first, sorted by length to avoid partial matches
                $multiCandidates = Component::whereIn('type', [Component::TYPE_KATA_SAMBUNG, Component::TYPE_HIASAN])
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
                        $type = Component::TYPE_ANGKA;
                    } elseif (preg_match('/^\p{Lu}$/u', $ch)) {
                        $type = Component::TYPE_HURUF_BESAR;
                    } elseif (preg_match('/^\p{Ll}$/u', $ch)) {
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
            }

            if (!empty($data['components']) && is_array($data['components'])) {
                foreach ($data['components'] as $c) {
                    $id = (int) ($c['id'] ?? 0);
                    $qty = max(1, (int) ($c['quantity'] ?? 1));
                    if ($id > 0) $componentsToAttach[$id] = ($componentsToAttach[$id] ?? 0) + $qty;
                }
            }

            // Compute existing pivot quantities on this order so we can use them
            // as targets when the edit form didn't supply explicit components.
            $existingPivot = [];
            foreach ($order->components as $c) {
                $existingPivot[$c->id] = (int) ($c->pivot->quantity_used ?? 0);
            }

            // If the request didn't include components but this order already has
            // pivot entries, treat those as the desired target quantities so they
            // will be consumed now when moving to 'disewa'.
            if (empty($componentsToAttach) && !empty($existingPivot)) {
                $componentsToAttach = $existingPivot;
            }

            // Compute additional required quantities (delta) so we don't double-consume
            $additionalNeeded = [];
            foreach ($componentsToAttach as $compId => $qty) {
                $already = $existingPivot[$compId] ?? 0;
                $delta = max(0, $qty - $already);
                if ($delta > 0) $additionalNeeded[$compId] = $delta;
            }

            // Diagnostic shortages check uses only the delta
            $shortages = [];
            foreach ($additionalNeeded as $compId => $needed) {
                $comp = Component::find($compId);
                if (!$comp) continue;
                if ($comp->quantity_available < $needed) {
                    $shortages[] = "{$comp->name} (tersedia: {$comp->quantity_available}, dibutuhkan: {$needed})";
                }
            }
            if (!empty($shortages)) {
                return back()->withInput()->withErrors(['components' => 'Stok komponen tidak mencukupi: ' . implode('; ', $shortages)]);
            }

            // If validation passes, handle the status change with inventory management
            DB::transaction(function () use ($order, $componentsToAttach, $additionalNeeded, $newStatus) {
                // Allocate assets if not already allocated
                if ($order->assets()->count() === 0) {
                    $product = $order->product;
                    $requiredPapanQty = (int) ($product->required_papan_quantity ?? 0);
                    if ($requiredPapanQty < 1) {
                        $requiredPapanQty = str_contains(strtolower($product->name), 'gandeng') ? 2 : 1;
                    }

                    $papanAsset = Asset::available()->type(Asset::TYPE_PAPAN)
                        ->where('color', $product->required_papan_color)
                        ->first();
                    $rakAsset = Asset::available()->type(Asset::TYPE_RAK)
                        ->where('color', $product->default_rack_color ?? $product->required_rak_color)
                        ->first();

                    if ($papanAsset) {
                        $order->assets()->attach($papanAsset->id, ['quantity_used' => $requiredPapanQty]);
                        $papanAsset->increment('quantity_rented', $requiredPapanQty);
                    }
                    if ($rakAsset) {
                        $order->assets()->attach($rakAsset->id, ['quantity_used' => $requiredPapanQty]);
                        $rakAsset->increment('quantity_rented', $requiredPapanQty);
                    }
                } else {
                    // Increment rented quantity for existing assets
                    foreach ($order->assets as $asset) {
                        $qty = $asset->pivot->quantity_used ?? 0;
                        if ($qty > 0) $asset->increment('quantity_rented', $qty);
                    }
                }

                // Sync component stock and pivot table based on the desired state from the summary
                if (!empty($componentsToAttach)) {
                    $this->syncComponentStockAndPivot($order, $componentsToAttach);
                }
                
                $order->status = $newStatus;
                $order->save();
            });

            return redirect()->route('orders.index')->with('success', 'Status pesanan berhasil diperbarui.');
        }
        // If transitioning from disewa back to pending, return assets and components
        elseif ($newStatus === 'pending' && $oldStatus === 'disewa') {
            DB::transaction(function () use ($order, $newStatus) {
                // Return assets rented counts
                foreach ($order->assets as $asset) {
                    $asset->decrement('quantity_rented', $asset->pivot->quantity_used);
                }

                // Return components to stock and detach
                $this->returnComponents($order);

                // Update status on order model directly
                $order->status = $newStatus;
                $order->save();
            });
        } elseif ($newStatus === 'selesai' && $oldStatus !== 'selesai') {
            // Handle completion - return assets to inventory
            DB::transaction(function () use ($order, $newStatus) {
                foreach ($order->assets as $asset) {
                    $asset->decrement('quantity_rented', $asset->pivot->quantity_used);
                }
                $order->status = $newStatus;
                $order->save();
            });
        } elseif ($newStatus && $newStatus !== $oldStatus) {
            // Simple status change without inventory implications
            $payload['status'] = $newStatus;
        }

        $order->update($payload);

        return redirect()->route('orders.index')->with('status', 'Detail pesanan berhasil diperbarui.');
    }



    /**
     * Hapus pesanan.
     */
    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            // Use the sync method with an empty array to return all stock
            $this->syncComponentStockAndPivot($order, []);

            // Return asset stock
            foreach ($order->assets as $asset) {
                $asset->decrement('quantity_rented', $asset->pivot->quantity_used);
            }
            $order->assets()->detach();

            $order->delete();
        });

        return redirect()->route('orders.index')->with('success', 'Pesanan berhasil dihapus.');
    }

    /**
     * Tampilkan form konfirmasi penyelesaian (kembalikan aset/komponen).
     */
    public function completeForm(Order $order): View
    {
        $order->load(['assets', 'components']);

        return view('orders.confirm-return', compact('order'));
    }

    /**
     * Proses penyelesaian pesanan: ubah status aset sesuai kondisi dan kembalikan stok komponen.
     */
    public function complete(Request $request, Order $order): RedirectResponse
    {
        // For now, we assume all assets are returned in good condition.
        // A more complex implementation would handle damaged assets.
        DB::transaction(function () use ($order) {
            // Return assets to stock
            foreach ($order->assets as $asset) {
                // Decrement the rented quantity by the amount used in this order
                $asset->decrement('quantity_rented', $asset->pivot->quantity_used);
            }

            // Return components to stock based on pivot quantities
            $this->returnComponents($order);

            // Set order status to Selesai
            $order->status = Order::STATUS_SELESAI;
            $order->save();
        });

        return redirect()->route('orders.index')->with('status', 'Pesanan selesai dan inventaris diperbarui.');
    }

    /**
     * Change order status via quick action (from index list)
     */
    public function changeStatus(Request $request, Order $order)
    {
        $new = $request->input('status');
        $allowed = ['pending', 'disewa', 'selesai'];
        if (!in_array($new, $allowed)) {
            return back()->with('error', 'Status tidak valid.');
        }

        // If switching to disewa, ensure component availability
    if ($new === 'disewa') {
            $componentsToAttach = [];
            // parse text_content and any attached components from order
            $text = $order->text_content ?? '';

            if (trim($text) !== '') {
                $textNorm = preg_replace('/\s+/u', ' ', trim($text));

                // detect multi-word components (longer first) and avoid partial matches
                $multiCandidates = Component::whereIn('type', [Component::TYPE_KATA_SAMBUNG, Component::TYPE_HIASAN])
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
                        $type = Component::TYPE_ANGKA;
                    } elseif (preg_match('/^\p{Lu}$/u', $ch)) {
                        $type = Component::TYPE_HURUF_BESAR;
                    } elseif (preg_match('/^\p{Ll}$/u', $ch)) {
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

                    if ($comp) $componentsToAttach[$comp->id] = ($componentsToAttach[$comp->id] ?? 0) + 1;
                }
            }

            // include existing attached components via pivot counts for reference
            $existingPivot = [];
            foreach ($order->components as $c) {
                $existingPivot[$c->id] = (int) ($c->pivot->quantity_used ?? 0);
            }

            // Compute additional required quantities (delta) so we don't double-check/consume
            $additionalNeeded = [];
            foreach ($componentsToAttach as $compId => $qty) {
                $already = $existingPivot[$compId] ?? 0;
                $delta = max(0, $qty - $already);
                if ($delta > 0) $additionalNeeded[$compId] = $delta;
            }

            // Diagnostic logging when shortages occur
            $shortages = [];
            foreach ($componentsToAttach as $compId => $qty) {
                $comp = Component::find($compId);
                if (!$comp) continue;
                $needed = $additionalNeeded[$compId] ?? 0;
                if ($needed > 0 && $comp->quantity_available < $needed) {
                    $shortages[] = "{$comp->name} (tersedia: {$comp->quantity_available}, dibutuhkan: {$needed})";
                }
            }
            if (!empty($shortages)) {
                $msg = 'Stok komponen tidak mencukupi: ' . implode('; ', $shortages);
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            // perform allocation and consumption for quick-status change
            DB::transaction(function () use ($order, $componentsToAttach, $additionalNeeded) {
                // allocate assets or increment rented counters
                if ($order->assets()->count() === 0) {
                    $product = $order->product;
                    $requiredPapanQty = (int) ($product->required_papan_quantity ?? 0);
                    if ($requiredPapanQty < 1) {
                        $requiredPapanQty = str_contains(strtolower($product->name), 'gandeng') ? 2 : 1;
                    }

                    $papanAsset = Asset::available()->type(Asset::TYPE_PAPAN)
                        ->where('color', $product->required_papan_color)
                        ->first();
                    $rakAsset = Asset::available()->type(Asset::TYPE_RAK)
                        ->where('color', $product->default_rack_color ?? $product->required_rak_color)
                        ->first();

                    if ($papanAsset) {
                        $order->assets()->attach($papanAsset->id, ['quantity_used' => $requiredPapanQty]);
                        $papanAsset->increment('quantity_rented', $requiredPapanQty);
                    }
                    if ($rakAsset) {
                        $order->assets()->attach($rakAsset->id, ['quantity_used' => $requiredPapanQty]);
                        $rakAsset->increment('quantity_rented', $requiredPapanQty);
                    }
                } else {
                    foreach ($order->assets as $asset) {
                        $qty = $asset->pivot->quantity_used ?? 0;
                        if ($qty > 0) $asset->increment('quantity_rented', $qty);
                    }
                }

                // Prepare consume array from additionalNeeded (only new quantities)
                $consumeArr = array_map(function ($id, $qty) {
                    return ['id' => $id, 'quantity_used' => $qty];
                }, array_keys($additionalNeeded), $additionalNeeded);

                if (!empty($consumeArr)) {
                    $this->consumeComponents($order, $consumeArr);
                }

                $order->status = Order::STATUS_DISEWA ?? 'disewa';
                $order->save();
            });
        }

    // If switching from disewa back to pending, return assets and components
    if ($new === 'pending' && strtolower((string) $order->status) === 'disewa') {
            DB::transaction(function () use ($order, $new) {
                // Return assets to stock
                foreach ($order->assets as $asset) {
                    $asset->decrement('quantity_rented', $asset->pivot->quantity_used);
                }

                // Return components to stock and detach
                $this->returnComponents($order);

                $order->status = $new;
                $order->save();
            });

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Inventaris dikembalikan dan status diperbarui.', 'new_status' => $order->status]);
            }

            return redirect()->route('orders.index')->with('status', 'Inventaris dikembalikan dan status diperbarui.');
        }

        // If switching to selesai, decrement assets rented and (optionally) mark return
        if ($new === 'selesai') {
            DB::transaction(function () use ($order) {
                foreach ($order->assets as $asset) {
                    $asset->decrement('quantity_rented', $asset->pivot->quantity_used);
                }
                // Return components to stock
                $this->returnComponents($order);

                $order->status = Order::STATUS_SELESAI;
                $order->save();
            });

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Pesanan diselesaikan dan aset dikembalikan.', 'new_status' => $order->status, 'new_status_display' => ucfirst($order->status)]);
            }

            return redirect()->route('orders.index')->with('status', 'Pesanan diselesaikan dan aset dikembalikan.');
        }

        $order->status = $new;
        $order->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Status pesanan diperbarui.', 'new_status' => $order->status, 'new_status_display' => ucfirst($order->status)]);
        }

        return redirect()->route('orders.index')->with('status', 'Status pesanan diperbarui.');
    }


    /**
     * Hitung final price dari base + diskon.
     */
    protected function calculateFinalPrice(float $basePrice, ?string $discountType, float $discountValue): float
    {
        $final = $basePrice;

        if ($discountType === 'percent') {
            $final = $basePrice - ($basePrice * ($discountValue / 100));
        } elseif ($discountType === 'fixed') {
            $final = $basePrice - $discountValue;
        }

        // Minimal 0
        if ($final < 0) {
            $final = 0;
        }

        // Dua desimal
        return round($final, 2);
    }

    /**
     * Tandai aset sebagai disewa.
     *
     * @param array<int> $assetIds
     * @param Order $order
     * @return void
     */


    /**
     * Kurangi stok komponen sesuai request lalu attach ke order via pivot.
     *
     * $components array bentuknya: [ ['id' => X, 'quantity_used' => Y], ... ]
     *
     * @param Order $order
     * @param array $components
     */
    /**
     * Consume components for an order.
     *
     * If $overwritePivot is true, the provided quantity is treated as the target
     * pivot quantity for this order (the method will compute the delta against
     * any existing pivot and only consume the difference). This is useful when
     * transitioning an order into 'disewa' where pivot entries already exist
     * but may not have been consumed yet.
     *
     * @param Order $order
     * @param array $components array of ['id'=>X, 'quantity_used'=>Y]
     * @param bool $overwritePivot
     */
    protected function consumeComponents(Order $order, array $components, bool $overwritePivot = false): void
    {
        if (empty($components)) {
            return;
        }

        foreach ($components as $c) {
            $component = Component::find($c['id']);
            $qty = (int) ($c['quantity_used'] ?? 0);
            if ($component && $qty > 0) {
                Log::info('Consuming component for order', ['order_id' => $order->id, 'component_id' => $component->id, 'qty' => $qty]);
                // Determine existing pivot quantity for this order (if any)
                $existing = $order->components()->find($component->id);
                $existingQty = $existing ? (int) ($existing->pivot->quantity_used ?? 0) : 0;

                if ($overwritePivot) {
                    // Treat $qty as the target pivot quantity. However, the DB may
                    // already have some consumed amount recorded in component->stok_used
                    // (from previous events). Compute the true needed consumption
                    // relative to what has already been consumed at the component
                    // level (not the pivot on the order), so we catch cases where
                    // the pivot was attached earlier but not consumed.
                    $alreadyConsumed = (int) ($component->stok_used ?? 0);
                    $targetQty = (int) $qty;
                    $needed = max(0, $targetQty - $alreadyConsumed);

                    if ($needed > 0) {
                        // Reduce stock by the needed amount
                        $component->quantity_available = max(0, $component->quantity_available - $needed);

                        if (isset($component->stok_used)) {
                            $component->stok_used = ($component->stok_used ?? 0) + $needed;
                        }

                        $component->save();
                    }

                    // Set pivot to the target quantity (even if needed==0)
                    $order->components()->syncWithoutDetaching([$component->id => ['quantity_used' => $targetQty]]);
                } else {
                    // Default behavior: treat $qty as an incremental consumption
                    $delta = $qty;

                    // Reduce stock by the consumed quantity (do not go negative)
                    $component->quantity_available = max(0, $component->quantity_available - $delta);

                    // update stok_used if present
                    if (isset($component->stok_used)) {
                        $component->stok_used = ($component->stok_used ?? 0) + $delta;
                    }

                    $component->save();

                    // Update pivot: increment existing pivot quantity_used if already attached,
                    // otherwise attach with the consumed quantity. This prevents overwriting
                    // a previously-attached quantity when multiple consumption events occur.
                    // BUGFIX: This logic was flawed and duplicated pivot entries. The pivot should
                    // be set by the calling method (store/update), and this method should only
                    // be responsible for the stock consumption itself.
                    // $newPivotQty = $existingQty + $delta;
                    // $order->components()->syncWithoutDetaching([$component->id => ['quantity_used' => $newPivotQty]]);
                }
            }
        }
    }

    /**
     * Return components to stock based on an order's pivot quantities.
     */
    protected function returnComponents(Order $order): void
    {
        foreach ($order->components as $comp) {
            $qty = (int) ($comp->pivot->quantity_used ?? 0);
            if ($qty <= 0) continue;

            // Restore available quantity
            $comp->quantity_available = ($comp->quantity_available ?? 0) + $qty;

            // Decrement stok_used if present
            if (isset($comp->stok_used)) {
                $comp->stok_used = max(0, ($comp->stok_used ?? 0) - $qty);
            }

            $comp->save();
        }

        // Detach components pivot entries for this order (they will be empty if needed)
        $order->components()->detach();
    }

    /**
     * Kembalikan aset ke status tersedia dan kembalikan komponen ke stok berdasarkan pivot.
     */
    private function syncComponentStockAndPivot(Order $order, array $desiredComponents)
    {
        DB::transaction(function () use ($order, $desiredComponents) {
            $order->load('components');
            $currentComponents = $order->components->mapWithKeys(function ($item) {
                return [$item->id => ['quantity_used' => $item->pivot->quantity_used]];
            });

            $allComponentIds = collect($desiredComponents)->keys()->union($currentComponents->keys());

            foreach ($allComponentIds as $id) {
                $desiredQty = (int) ($desiredComponents[$id] ?? 0);
                $currentQty = (int) ($currentComponents[$id]['quantity_used'] ?? 0);
                $delta = $desiredQty - $currentQty;

                if ($delta === 0) {
                    continue;
                }

                $component = Component::find($id);
                if (!$component) {
                    continue; // Or throw an exception
                }

                // Adjust stock based on the delta
                if ($delta > 0) { // Need more components
                    if ($component->quantity_available < $delta) {
                        throw new \Exception("Stok untuk komponen '{$component->name}' tidak mencukupi. Diminta tambahan: {$delta}, Tersedia: {$component->quantity_available}");
                    }
                    $component->decrement('quantity_available', $delta);
                } else { // Need fewer components, return stock
                    $component->increment('quantity_available', abs($delta));
                }
            }

            // After adjusting stock, sync the pivot table to the desired state
            $order->components()->sync($desiredComponents);
        });
    }

    private function parseComponentsFromSummary(string $summary): array
    {
        $components = [];

        // Split by comma, semicolon, or newline
        $items = preg_split('/[;,\\n]+/', $summary);

        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            // Check if this is a direct component code (like C123)
            if (preg_match('/^C(\d+)$/', $item, $matches)) {
                $components[] = [
                    'id' => (int) $matches[1],
                    'quantity' => 1, // Default to 1, can be adjusted later
                ];
                continue;
            }

            // For multi-word components, try to match them as a phrase
            $phrase = $item;
            $multiWordComponents = Component::where('type', Component::TYPE_HIASAN)
                ->where('name', 'like', "%{$phrase}%")
                ->get();

            foreach ($multiWordComponents as $component) {
                $components[] = [
                    'id' => $component->id,
                    'quantity' => 1, // Default to 1, can be adjusted later
                ];
            }
        }

        return $components;
    }
}
