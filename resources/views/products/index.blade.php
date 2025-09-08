@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4">Manajemen Produk (Varian)</h1>
                    <p class="text-muted">Daftar varian papan bunga. Hanya Owner yang dapat mengelola data ini.</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('products.create') }}" class="btn btn-primary">Tambah Produk</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form class="mb-3" method="GET" action="{{ route('products.index') }}">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input id="products-search" type="search" name="q" value="{{ old('q', $query ?? request('q')) }}" class="form-control" placeholder="Cari nama, warna, atau status (aktif/nonaktif)...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all">Semua Status</option>
                                    <option value="aktif" {{ (isset($status) && strtolower($status) === 'aktif') || request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="nonaktif" {{ (isset($status) && strtolower($status) === 'nonaktif') || request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="all">Semua Tipe</option>
                                    <option value="single" {{ (isset($type) && strtolower($type) === 'single') || request('type') === 'single' ? 'selected' : '' }}>Single</option>
                                    <option value="gandeng" {{ (isset($type) && strtolower($type) === 'gandeng') || request('type') === 'gandeng' ? 'selected' : '' }}>Gandeng</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="color" class="form-select" onchange="this.form.submit()">
                                    <option value="">Semua Warna Papan</option>
                                    @foreach($papanColors ?? [] as $pc)
                                        <option value="{{ $pc->name }}" {{ (isset($color) && $color === $pc->name) || request('color') === $pc->name ? 'selected' : '' }}>{{ $pc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th scope="col">Foto</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Tipe Papan</th>
                                <th scope="col">Warna Papan</th>
                                <th scope="col">Warna Rak</th>
                                <th scope="col">Harga</th>
                                <th scope="col">Status</th>
                                <th scope="col">Deskripsi</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td>
                                        @if($product->photo)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) }}" alt="foto" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                        @else
                                            <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">-</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->name)
                                                @php
                                                    // Word-wrap name: don't split words, max 20 chars per line
                                                    $name = trim($product->name);
                                                    $words = preg_split('/\s+/u', $name);
                                                    $max = 20;
                                                    $lines = [];
                                                    $current = '';
                                                    foreach ($words as $w) {
                                                        if ($current === '') {
                                                            $current = $w;
                                                            continue;
                                                        }
                                                        $lenCurrent = mb_strlen($current);
                                                        $lenW = mb_strlen($w);
                                                        if ($lenCurrent + 1 + $lenW <= $max) {
                                                            $current .= ' ' . $w;
                                                        } else {
                                                            $lines[] = $current;
                                                            $current = $w;
                                                        }
                                                    }
                                                    if ($current !== '') {
                                                        $lines[] = $current;
                                                    }
                                                    echo implode('<br>', array_map('e', $lines));
                                                @endphp
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $qty = $product->required_papan_quantity ?? 1;
                                        @endphp
                                        {{ ($qty == 2 || $qty === '2') ? 'Gandeng' : 'Single' }}
                                    </td>
                                    <td class="text-center">{{ $product->required_papan_color ?? '-' }}</td>
                                    <td class="text-center">{{ $product->default_rack_color ? ucfirst($product->default_rack_color) : '-' }}</td>
                                    <td>Rp {{ number_format((float) $product->base_price, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ ($product->active ?? true) ? 'Aktif' : 'Nonaktif' }}</td>
                                    <td>
                                        @if($product->description)
                                            @php
                                                // Preserve existing newlines, and wrap by words (max 20 chars per line)
                                                $desc = $product->description;
                                                $origLines = preg_split('/\r?\n/', $desc);
                                                $wrapped = [];
                                                $max = 20;
                                                foreach ($origLines as $origLine) {
                                                    $line = trim($origLine);
                                                    if ($line === '') {
                                                        // keep empty line
                                                        $wrapped[] = '';
                                                        continue;
                                                    }
                                                    $words = preg_split('/\s+/u', $line);
                                                    $current = '';
                                                    foreach ($words as $w) {
                                                        if ($current === '') {
                                                            $current = $w;
                                                            continue;
                                                        }
                                                        $lenCurrent = mb_strlen($current);
                                                        $lenW = mb_strlen($w);
                                                        if ($lenCurrent + 1 + $lenW <= $max) {
                                                            $current .= ' ' . $w;
                                                        } else {
                                                            $wrapped[] = $current;
                                                            $current = $w;
                                                        }
                                                    }
                                                    if ($current !== '') {
                                                        $wrapped[] = $current;
                                                    }
                                                }
                                                echo implode('<br>', array_map('e', $wrapped));
                                            @endphp
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning mb-1">Edit</a>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger mb-1">Hapus</button>
                                            </form>
                                            <form action="{{ route('products.toggle', $product) }}" method="POST" onsubmit="return confirm('Ubah status produk ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">{{ ($product->active ?? true) ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                            </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada produk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages())
                <div class="card-footer">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var input = document.getElementById('products-search');
        if (!input) return;
        var timer = null;
        var form = input.form;
        function submitNow() {
            if (!form) return;
            form.submit();
        }
        input.addEventListener('input', function(){
            if (timer) clearTimeout(timer);
            timer = setTimeout(submitNow, 500);
        });
        // submit immediately on Enter
        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                submitNow();
            }
        });
    })();
</script>
@endpush
