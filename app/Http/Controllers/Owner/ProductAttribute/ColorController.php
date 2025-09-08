<?php

namespace App\Http\Controllers\Owner\ProductAttribute;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Asset;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'papan');
        $colors = Color::where('type', $type)->orderBy('name')->get();
        return view('inventory.warna.index', compact('colors', 'type'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'papan');
        return view('inventory.warna.create', compact('type'));
    }

    public function store(Request $request)
    {
        // normalize name
        $name = trim($request->input('name'));
        $type = $request->input('type');

        $request->merge(['name' => $name]);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:papan,rak',
        ]);

        // case-insensitive duplicate check within same type
        $exists = Color::whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors(['name' => 'Nama warna sudah ada untuk tipe ini.']);
        }

        Color::create(['name' => $name, 'type' => $type]);
    return redirect()->route('warna.index', ['type' => $type])->with('success', 'Warna ditambahkan.');
    }

    public function edit(Color $color)
    {
        $type = $color->type;
        return view('inventory.warna.edit', compact('color', 'type'));
    }

    public function update(Request $request, Color $color)
    {
        // normalize
        $name = trim($request->input('name'));
        $type = $request->input('type');

        $request->merge(['name' => $name]);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:papan,rak',
        ]);

        // case-insensitive duplicate check within same type, ignoring current id
        $exists = Color::whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->where('type', $type)
            ->where('id', '!=', $color->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors(['name' => 'Nama warna sudah ada untuk tipe ini.']);
        }

        $color->update(['name' => $name, 'type' => $type]);
    return redirect()->route('warna.index', ['type' => $type])->with('success', 'Warna diperbarui.');
    }

    public function toggle(Color $color)
    {
        $color->active = !$color->active;
        $color->save();
        return redirect()->route('warna.index')->with('success', 'Status warna diperbarui.');
    }

    public function destroy(Color $color)
    {
        $name = $color->name;

        // Check references in assets and products (by name)
        $assetCount = Asset::where('color', $name)->count();
        $productCount = Product::where('required_papan_color', $name)
            ->orWhere('default_rack_color', $name)
            ->count();

        if ($assetCount > 0 || $productCount > 0) {
            $parts = [];
            if ($assetCount > 0) $parts[] = "{$assetCount} aset";
            if ($productCount > 0) $parts[] = "{$productCount} produk";
            $msg = 'Warna tidak bisa dihapus karena masih digunakan oleh ' . implode(' dan ', $parts) . '.';
            return redirect()->route('warna.index', ['type' => $color->type])->with('error', $msg);
        }

        $color->delete();
        return redirect()->route('warna.index', ['type' => $color->type])->with('success', 'Warna dihapus.');
    }
}
