<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    /**
     * Tampilkan daftar aset.
     */
    public function index(Request $request): View
    {
    // start with a base query; we'll order by type and color (alphabetical)
    $query = Asset::query();

        $q = $request->query('q');
        $type = $request->query('type');
        $status = $request->query('status');
        $color = $request->query('color');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('color', 'like', "%{$q}%")
                    ->orWhere('type', 'like', "%{$q}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($color !== null && $color !== '') {
            $query->where('color', $color);
        }

        if ($status) {
            if ($status === 'tersedia') {
                $query->whereRaw('(COALESCE(quantity_total,0) - COALESCE(quantity_rented,0) - COALESCE(quantity_repair,0)) > 0');
            } elseif ($status === 'disewa') {
                $query->where('quantity_rented', '>', 0);
            } elseif ($status === 'perbaikan') {
                $query->where('quantity_repair', '>', 0);
            }
        }

    // Apply alphabetical ordering by type then color before pagination
    $query->orderBy('type')->orderByRaw("COALESCE(color, '') ASC");

    // compute totals for the filtered set (per type)
        // Use clones but remove ordering/limits so aggregate queries don't include ORDER BY (MySQL rejects mixes)
        $papanTotalsQuery = clone $query;
        if (method_exists($papanTotalsQuery, 'getQuery')) {
            $papanTotalsQuery->getQuery()->orders = null;
            $papanTotalsQuery->getQuery()->limit = null;
        }
        $papanTotals = $papanTotalsQuery
            ->where('type', 'papan')
            ->selectRaw('COALESCE(SUM(quantity_total),0) as total, COALESCE(SUM(quantity_rented),0) as rented, COALESCE(SUM(quantity_repair),0) as repair')
            ->first();

        $rakTotalsQuery = clone $query;
        if (method_exists($rakTotalsQuery, 'getQuery')) {
            $rakTotalsQuery->getQuery()->orders = null;
            $rakTotalsQuery->getQuery()->limit = null;
        }
        $rakTotals = $rakTotalsQuery
            ->where('type', 'rak')
            ->selectRaw('COALESCE(SUM(quantity_total),0) as total, COALESCE(SUM(quantity_rented),0) as rented, COALESCE(SUM(quantity_repair),0) as repair')
            ->first();

        $assets = $query->paginate(12)->appends($request->query());

        return view('inventory.assets.index', compact('assets', 'papanTotals', 'rakTotals'));
    }

    /**
     * Form create aset.
     */
    public function create(): View
    {
        $meta = $this->meta();

        return view('inventory.assets.create', compact('meta'));
    }

    /**
     * Simpan aset baru.
     */
    public function store(AssetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // If batch items provided, process each
        if (!empty($validated['items']) && is_array($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $this->storeSingle($item);
            }
        } else {
            $this->storeSingle($validated);
        }

        return redirect()->route('assets.index')->with('status', 'Aset berhasil dibuat.');
    }

    /**
     * Store or increment a single asset record (expects array with keys type,color,quantity_total)
     */
    protected function storeSingle(array $data): void
    {
        $qty = isset($data['quantity_total']) ? (int)$data['quantity_total'] : 0;

        // Match existing asset by type and color only (size column was removed)
        $existing = Asset::where('type', $data['type'])
            ->where('color', $data['color'] ?? null)
            ->first();

        if ($existing) {
            $existing->quantity_total += $qty;
            $existing->save();
        } else {
            // ensure only allowed fields are passed to create
            $create = [
                'type' => $data['type'],
                'color' => $data['color'] ?? null,
                'quantity_total' => $qty,
                'quantity_rented' => $data['quantity_rented'] ?? 0,
                'status' => $data['status'] ?? 'tersedia',
            ];

            Asset::create($create);
        }
    }

    /**
     * Form edit aset.
     */
    public function edit(Asset $asset): View
    {
        $meta = $this->meta();

        return view('inventory.assets.edit', compact('asset', 'meta'));
    }

    /**
     * Update aset.
     */
    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $data = $request->validated();

        // If status is provided, interpret quantity_total as the bucket for that status
        $status = $data['status'] ?? null;
        $qty = isset($data['quantity_total']) ? (int) $data['quantity_total'] : null;

        if ($status && $qty !== null) {
            if ($status === 'tersedia') {
                // available = total - rented - repair; set total to match available + rented + repair
                $currentRented = $asset->quantity_rented ?? 0;
                $currentRepair = $asset->quantity_repair ?? 0;
                $asset->quantity_total = $qty + $currentRented + $currentRepair;
            } elseif ($status === 'disewa') {
                $asset->quantity_rented = $qty;
            } elseif ($status === 'perbaikan') {
                $asset->quantity_repair = $qty;
            }
            // also update color/type if present
            if (isset($data['color'])) $asset->color = $data['color'];
            if (isset($data['type'])) $asset->type = $data['type'];
            $asset->save();
        } else {
            // fallback: update normally
            $asset->update($data);
        }

        return redirect()->route('assets.index')->with('status', 'Aset berhasil diperbarui.');
    }

    /**
     * Hapus aset.
     */
    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('assets.index')->with('status', 'Aset berhasil dihapus.');
    }

    /**
     * Metadata pilihan type/status untuk form.
     *
     * @return array<string, array<string,string>>
     */
    protected function meta(): array
    {
        return [
            'types' => [
                'papan' => 'Papan Dasar',
                'rak' => 'Rak Penyangga',
            ],
            'statuses' => [
                'tersedia' => 'Tersedia',
                'disewa' => 'Disewa',
                'perbaikan' => 'Perlu Perbaikan',
            ],
            'colors' => \App\Models\Color::where('active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * Return JSON counts for a given type+color grouped by status.
     * Query params: ?type=papan&color=Merah
     */
    public function counts()
    {
        $type = request('type');
        $color = request('color');

        if (!$type) {
            return response()->json(['error' => 'type required'], 400);
        }

        $query = Asset::where('type', $type);
        if ($color !== null && $color !== '') {
            $query->where('color', $color);
        } else {
            $query->whereNull('color');
        }

        $total = (int) $query->sum('quantity_total');
        $rented = (int) $query->sum('quantity_rented');
        $repair = (int) $query->sum('quantity_repair');
        $available = $total - $rented - $repair;

        return response()->json([
            'total' => $total,
            'tersedia' => max(0, $available),
            'disewa' => $rented,
            'perbaikan' => $repair,
        ]);
    }
}
