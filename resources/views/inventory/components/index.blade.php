@extends('layouts.app')

@section('title', 'Manajemen Komponen')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4">Manajemen Komponen (Reusable Consumables)</h1>
                    <p class="text-muted">Daftar huruf/angka dan hiasan yang digunakan dan dikembalikan ke stok. Akses: Owner.</p>
                </div>
                <div>
                    <a href="{{ route('components.create') }}" class="btn btn-primary">Tambah Komponen</a>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="card">
                <div class="card-body p-0">
                    <div class="p-3">
                        <form method="GET" action="{{ route('components.index') }}" class="row g-2 align-items-center">
                            <div class="col-auto" style="flex:1">
                                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama komponen...">
                            </div>
                            <div class="col-auto">
                                <select name="type" class="form-select">
                                    <option value="">Semua Tipe</option>
                                    <option value="huruf_besar" {{ request('type')=='huruf_besar' ? 'selected' : '' }}>Huruf Besar</option>
                                    <option value="huruf_kecil" {{ request('type')=='huruf_kecil' ? 'selected' : '' }}>Huruf Kecil</option>
                                    <option value="angka" {{ request('type')=='angka' ? 'selected' : '' }}>Angka</option>
                                    <option value="simbol" {{ request('type')=='simbol' ? 'selected' : '' }}>Simbol</option>
                                    <option value="hiasan" {{ request('type')=='hiasan' ? 'selected' : '' }}>Hiasan</option>
                                    <option value="kata_sambung" {{ request('type')=='kata_sambung' ? 'selected' : '' }}>Kata Sambung</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="aman" {{ request('status')=='aman' ? 'selected' : '' }}>Aman</option>
                                    <option value="menipis" {{ request('status')=='menipis' ? 'selected' : '' }}>Menipis</option>
                                    <option value="habis" {{ request('status')=='habis' ? 'selected' : '' }}>Habis</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary">Cari</button>
                                <a href="{{ route('components.index') }}" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:200px">Nama Komponen</th>
                                    <th class="text-center" style="width:130px">Tipe</th>
                                    <th class="text-center" style="width:120px">Stok Total</th>
                                    <th class="text-center" style="width:120px">Stok Digunakan</th>
                                    <th class="text-center" style="width:120px">Stok Tersedia</th>
                                    <th class="text-center" style="width:130px">Status Stok</th>
                                    <th class="text-center" style="width:120px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($components as $component)
                                    <tr>
                                        <td><strong>{{ $component->name }}</strong></td>
                                        <td class="text-center"><span class="badge bg-secondary text-uppercase">{{ strtoupper($component->type) }}</span></td>
                                        <td class="text-center">{{ $component->stok_total ?? $component->quantity_available }}</td>
                                        <td class="text-center"><span class="badge bg-light text-dark">{{ $component->stok_used ?? 0 }}</span></td>
                                        <td class="text-center">{{ (($component->stok_total ?? $component->quantity_available) - ($component->stok_used ?? 0)) }}</td>
                                        <td class="text-center">
                                            @php
                                                $total = $component->stok_total ?? $component->quantity_available;
                                                $used = $component->stok_used ?? 0;
                                                $available = max(0, $total - $used);
                                                $ratio = $total > 0 ? ($available / $total) : 0;
                                            @endphp
                                            @if($available <= 0)
                                                <span class="badge bg-danger">Habis</span>
                                            @elseif($ratio <= 0.2)
                                                <span class="badge bg-warning text-dark">Menipis</span>
                                            @else
                                                <span class="badge bg-success">Aman</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('components.edit', $component) }}" class="btn btn-sm btn-outline-primary">✎</a>
                                                <form action="{{ route('components.destroy', $component) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Hapus komponen ini? Aksi tidak dapat dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada komponen.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(method_exists($components, 'links'))
                    <div class="card-footer">
                        {{ $components->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
