<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComponentRequest;
use App\Models\Component;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComponentController extends Controller
{
    /**
     * Tampilkan daftar komponen.
     */
    public function index(): View
    {
        $q = request()->query('q');
        $typeFilter = request()->query('type');
        $statusFilter = request()->query('status');

    // Order alphabetically by name (case-insensitive) for consistent A→Z listing
        // Order by type first, then alphabetically by name (case-insensitive)
        $componentsQuery = Component::orderBy('type')->orderByRaw('LOWER(name) ASC');

        if ($q) {
            $componentsQuery->where('type', 'like', "%{$q}%");
        }

        // Type mapping: user-facing types to DB values
        if ($typeFilter) {
            switch ($typeFilter) {
                case 'huruf_besar':
                    // uppercase letters stored as single capital letters; we'll filter by regexp-like approach
                    $componentsQuery->where('type', 'huruf')->whereRaw("LENGTH(name)=1 AND BINARY name = UPPER(name)");
                    break;
                case 'huruf_kecil':
                    $componentsQuery->where('type', 'huruf')->whereRaw("LENGTH(name)=1 AND BINARY name = LOWER(name)");
                    break;
                case 'angka':
                    $componentsQuery->where('type', 'huruf')->whereRaw("name REGEXP '^[0-9]+$'");
                    break;
                case 'simbol':
                    $componentsQuery->where('type', 'hiasan')->whereRaw("LENGTH(name)=1 AND name REGEXP '[^A-Za-z0-9]'");
                    break;
                case 'hiasan':
                    $componentsQuery->where('type', 'hiasan');
                    break;
                case 'kata_sambung':
                    $componentsQuery->where('type', 'hiasan')->whereRaw("LENGTH(name) > 1");
                    break;
                default:
                    // no filter
                    break;
            }
        }

        // Status filter: aman/menipis/habis
        if ($statusFilter) {
            $componentsQuery->where(function ($sub) use ($statusFilter) {
                $sub->where(function ($row) use ($statusFilter) {
                    $totalExpr = "COALESCE(stok_total, quantity_available)";
                    $usedExpr = "COALESCE(stok_used, 0)";
                    $availableExpr = "($totalExpr - $usedExpr)";
                    if ($statusFilter === 'habis') {
                        $row->whereRaw("{$availableExpr} <= 0");
                    } elseif ($statusFilter === 'menipis') {
                        $row->whereRaw("{$availableExpr} > 0 AND ({$availableExpr} / NULLIF({$totalExpr},0)) <= 0.2");
                    } elseif ($statusFilter === 'aman') {
                        $row->whereRaw("({$availableExpr} / NULLIF({$totalExpr},1)) > 0.2");
                    }
                });
            });
        }

    $components = $componentsQuery->paginate(20)->withQueryString();

        return view('inventory.components.index', compact('components'));
    }

    /**
     * Form create komponen.
     */
    public function create(): View
    {
        $meta = $this->meta();

        return view('inventory.components.create', compact('meta'));
    }

    /**
     * Simpan komponen baru.
     */
    public function store(ComponentRequest $request): RedirectResponse
    {
        Component::create($request->validated());

        return redirect()->route('components.index')->with('status', 'Komponen berhasil dibuat.');
    }

    /**
     * Form edit komponen.
     */
    public function edit(Component $component): View
    {
        $meta = $this->meta();

        return view('inventory.components.edit', compact('component', 'meta'));
    }

    /**
     * Update komponen.
     */
    public function update(ComponentRequest $request, Component $component): RedirectResponse
    {
        $component->update($request->validated());

        return redirect()->route('components.index')->with('status', 'Komponen berhasil diperbarui.');
    }

    /**
     * Hapus komponen.
     */
    public function destroy(Component $component): RedirectResponse
    {
        // Cegah penghapusan jika komponen pernah dipakai di pesanan
        if ($component->orders()->exists()) {
            return redirect()->route('components.index')->with('error', 'Komponen tidak bisa dihapus karena sudah dipakai pada satu atau lebih pesanan.');
        }

        $component->delete();

        return redirect()->route('components.index')->with('status', 'Komponen berhasil dihapus.');
    }

    /**
     * Metadata pilihan type untuk form.
     *
     * @return array<string, string>
     */
    protected function meta(): array
    {
        return [
            'huruf' => 'Huruf/Angka',
            'hiasan' => 'Hiasan/Symbol',
        ];
    }
}
