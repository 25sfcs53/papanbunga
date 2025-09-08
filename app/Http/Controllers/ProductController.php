<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Tampilkan daftar produk (varian).
     */
    public function index(): View
    {
    $query = request()->query('q');
    $status = request()->query('status');
    $type = request()->query('type');
    $color = request()->query('color');

    $productsQuery = Product::latest();
        if ($query) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('required_papan_color', 'like', "%{$query}%")
                  ->orWhere('default_rack_color', 'like', "%{$query}%");

                // allow searching by status keywords (aktif/nonaktif)
                if (preg_match('/\b(aktif|active|1|true|yes)\b/i', $query)) {
                    $q->orWhere('active', true);
                }
                if (preg_match('/\b(nonaktif|non-aktif|inactive|0|false|no)\b/i', $query)) {
                    $q->orWhere('active', false);
                }
            });
        }

        // Filter by explicit status (aktif/nonaktif)
        if ($status && $status !== 'all') {
            if (in_array(strtolower($status), ['aktif', 'active', '1', 'true', 'yes'], true)) {
                $productsQuery->where('active', true);
            } elseif (in_array(strtolower($status), ['nonaktif', 'non-aktif', 'inactive', '0', 'false', 'no'], true)) {
                $productsQuery->where('active', false);
            }
        }

        // Filter by papan type: 'gandeng' => required_papan_quantity >= 2, 'single' => < 2
        if ($type && $type !== 'all') {
            if (strtolower($type) === 'gandeng') {
                $productsQuery->where(function ($q) {
                    $q->where('required_papan_quantity', '>=', 2)
                      ->orWhere('required_papan_quantity', '2');
                });
            } elseif (strtolower($type) === 'single') {
                $productsQuery->where(function ($q) {
                    $q->whereNull('required_papan_quantity')
                      ->orWhere('required_papan_quantity', '<', 2)
                      ->orWhere('required_papan_quantity', '1');
                });
            }
        }

        // Filter by papan color (stored as string on product.required_papan_color)
        if ($color) {
            $productsQuery->where('required_papan_color', $color);
        }

        $products = $productsQuery->paginate(10)->withQueryString();

        // Provide papan color options for the filter UI
        $papanColors = Color::where('type', 'papan')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('products.index', compact('products', 'papanColors', 'status', 'type', 'color', 'query'));
    }

    /**
     * Form create produk.
     */
    public function create(): View
    {
        $papanColors = Color::where('type', 'papan')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('products.create', compact('papanColors'));
    }

    /**
     * Simpan produk baru.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Upload foto jika ada
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('products.index')->with('status', 'Produk berhasil dibuat.');
    }

    /**
     * Form edit produk.
     */
    public function edit(Product $product): View
    {
        $papanColors = Color::where('type', 'papan')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'papanColors'));
    }

    /**
     * Tampilkan detail produk.
     * Saat ini diarahkan ke form edit untuk menyederhanakan alur.
     */
    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.edit', $product);
    }

    /**
     * Update produk.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        // Upload foto baru jika ada, hapus lama jika perlu
        if ($request->hasFile('photo')) {
            // Hapus file lama
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }
            $data['photo'] = $request->file('photo')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    /**
     * Hapus produk.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', 'Produk berhasil dihapus.');
    }

    /**
     * Toggle active status for a product.
     */
    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['active' => !($product->active ?? true)]);

        return redirect()->route('products.index')->with('status', 'Status produk berhasil diubah.');
    }
}
