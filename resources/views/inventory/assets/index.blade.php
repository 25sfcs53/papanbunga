@extends('layouts.app')

@section('title', 'Manajemen Aset')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4">Manajemen Aset</h1>
                    <p class="text-muted">Daftar aset yang dapat digunakan kembali: Papan dan Rak. Akses: Owner.</p>
                </div>
                <div class="btn btn-group">
                    <a href="{{ route('assets.create') }}" class="btn btn-primary">
                        Tambah Aset
                    </a>
                    <a href="{{ route('warna.index') }}" class="btn btn-secondary">
                        Kelola Warna
                    </a>
                </div>
            </div>
            @php
                // Prefer controller-provided totals (covering filters); fall back to computing from current page collection.
                if (isset($papanTotals) && isset($rakTotals)) {
                    $totalPapan = (int) ($papanTotals->total ?? 0);
                    $rentedPapan = (int) ($papanTotals->rented ?? 0);
                    $repairPapan = (int) ($papanTotals->repair ?? 0);
                    $availablePapan = max(0, $totalPapan - $rentedPapan - $repairPapan);

                    $totalRak = (int) ($rakTotals->total ?? 0);
                    $rentedRak = (int) ($rakTotals->rented ?? 0);
                    $repairRak = (int) ($rakTotals->repair ?? 0);
                    $availableRak = max(0, $totalRak - $rentedRak - $repairRak);
                } else {
                    $collection = method_exists($assets, 'getCollection') ? $assets->getCollection() : $assets;
                    $papan = $collection->where('type', 'papan');
                    $rak = $collection->where('type', 'rak');

                    $totalPapan = $papan->sum('quantity_total');
                    $availablePapan = $papan->sum(function($a) { return $a->quantity_available ?? ($a->quantity_total - ($a->quantity_rented ?? 0) - ($a->quantity_repair ?? 0)); });
                    $rentedPapan = $papan->sum('quantity_rented');
                    $repairPapan = $papan->sum('quantity_repair');

                    $totalRak = $rak->sum('quantity_total');
                    $availableRak = $rak->sum(function($a) { return $a->quantity_available ?? ($a->quantity_total - ($a->quantity_rented ?? 0) - ($a->quantity_repair ?? 0)); });
                    $rentedRak = $rak->sum('quantity_rented');
                    $repairRak = $rak->sum('quantity_repair');
                }
            @endphp

            <div class="mb-3">
                <form method="GET" action="{{ route('assets.index') }}" class="row g-2 mb-3 align-items-center">
                    <div class="col-auto">
                        <input name="q" value="{{ request('q') }}" type="search" class="form-control form-control-sm" placeholder="Cari warna atau tipe...">
                    </div>
                    <div class="col-auto">
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua Tipe</option>
                            <option value="papan" {{ request('type') == 'papan' ? 'selected' : '' }}>Papan</option>
                            <option value="rak" {{ request('type') == 'rak' ? 'selected' : '' }}>Rak</option>
                            {{-- font type removed --}}
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                            <option value="disewa" {{ request('status') == 'disewa' ? 'selected' : '' }}>Disewa</option>
                            <option value="perbaikan" {{ request('status') == 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary">Bersihkan</a>
                    </div>
                </form>
                <div class="row justify-content-between">
                    <div class="col-4">
                        <strong>Papan</strong>
                        <span class="badge bg-primary">Total: {{ $totalPapan }}</span>
                        <span class="badge bg-success">Tersedia: {{ $availablePapan }}</span>
                        <span class="badge bg-warning text-dark">Disewa: {{ $rentedPapan }}</span>
                        <span class="badge bg-danger">Perbaikan: {{ $repairPapan }}</span>
                    </div>
                    <div class="col-4">
                        <strong>Rak</strong>
                        <span class="badge bg-primary">Total: {{ $totalRak }}</span>
                        <span class="badge bg-success">Tersedia: {{ $availableRak }}</span>
                        <span class="badge bg-warning text-dark">Disewa: {{ $rentedRak }}</span>
                        <span class="badge bg-danger">Perbaikan: {{ $repairRak }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th scope="col">Tipe</th>
                                <th scope="col">Warna</th>
                                <th scope="col">Jumlah Total</th>
                                <th scope="col">Tersedia</th>
                                <th scope="col">Disewa</th>
                                <th scope="col">Perbaikan</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $asset)
                                <tr class="text-center">
                                    <td>
                                        @php
                                            $typeMap = ['papan' => 'Papan', 'rak' => 'Rak'];
                                        @endphp
                                        {{ $typeMap[$asset->type] ?? $asset->type }}
                                    </td>
                                    <td>{{ $asset->color ?: '-' }}</td>
                                    <td>{{ $asset->quantity_total ?? 0 }}</td>
                                    <td>
                                        @php $avail = $asset->quantity_available ?? 0; @endphp
                                        <span class="badge {{ $avail > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $avail }}</span>
                                    </td>
                                    <td>
                                        @php $rented = $asset->quantity_rented ?? 0; @endphp
                                        <span class="badge {{ $rented > 0 ? 'bg-warning text-dark' : 'bg-secondary' }}">{{ $rented }}</span>
                                    </td>
                                    <td>
                                        @php $repair = $asset->quantity_repair ?? 0; @endphp
                                        <span class="badge {{ $repair > 0 ? 'bg-danger' : 'bg-secondary' }}">{{ $repair }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('assets.destroy', $asset) }}" method="POST" onsubmit="return confirm('Hapus aset ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada aset.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($assets->hasPages())
                <div class="card-footer">
                    {{ $assets->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
