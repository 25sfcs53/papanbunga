@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">Edit Produk (Varian)</h1>
                    <p class="text-muted mb-0">Perbarui detail varian papan bunga.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Form Edit Produk
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Nama Varian --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nama Varian</label>
                            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Harga Dasar --}}
                        <div class="mb-3">
                            <label for="base_price" class="form-label fw-semibold">Harga Dasar</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input id="base_price" name="base_price" type="number" step="1000" min="0" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price', (float)$product->base_price) }}" required placeholder="Contoh: 500000">
                            </div>
                            @error('base_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Foto Varian --}}
                        <div class="mb-3">
                            <label for="photo" class="form-label fw-semibold">Foto Varian</label>
                            <div class="d-flex align-items-center">
                                @if($product->photo)
                                    <img src="{{ asset('storage/'.$product->photo) }}" alt="{{ $product->name }}" class="img-thumbnail me-3" style="width: 400px; height: 400px; object-fit: cover;">
                                @else
                                    <div class="me-3 border rounded d-flex justify-content-center align-items-center bg-light" style="width: 100px; height: 100px;">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <input id="photo" name="photo" type="file" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                                    <div class="form-text">Kosongkan jika tidak ingin mengubah foto.</div>
                                </div>
                            </div>
                             @error('photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Kebutuhan Aset --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="required_papan_color" class="form-label fw-semibold">Warna Papan</label>
                                <select id="required_papan_color" class="form-select @error('required_papan_color') is-invalid @enderror" name="required_papan_color" required>
                                    <option value="">-- Pilih Warna Papan --</option>
                                    @foreach($papanColors as $color)
                                        <option value="{{ $color->name }}" {{ old('required_papan_color', $product->required_papan_color) == $color->name ? 'selected' : '' }}>{{ $color->name }}</option>
                                    @endforeach
                                </select>
                                @error('required_papan_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="required_papan_type" class="form-label fw-semibold">Jenis/Tipe Papan</label>
                                @php
                                    $selectedType = old('required_papan_quantity_type', ($product->required_papan_quantity ?? 1) == 2 ? 'gandeng' : 'single');
                                @endphp
                                <select id="required_papan_type" class="form-select" name="required_papan_quantity_type" required>
                                    <option value="single" {{ $selectedType == 'single' ? 'selected' : '' }}>Single</option>
                                    <option value="gandeng" {{ $selectedType == 'gandeng' ? 'selected' : '' }}>Gandeng</option>
                                </select>

                                {{-- Hidden numeric quantity synced from the type select --}}
                                <input id="required_papan_quantity" type="hidden" name="required_papan_quantity" value="{{ old('required_papan_quantity', $product->required_papan_quantity ?? 1) }}">

                                @error('required_papan_quantity')
                                     <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Deskripsi</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="default_rack_color" class="form-label fw-semibold">Warna Rak Penyangga</label>
                            <select id="default_rack_color" class="form-select @error('default_rack_color') is-invalid @enderror" name="default_rack_color" required>
                                <option value="hitam" {{ strtolower(old('default_rack_color', $product->default_rack_color ?? '')) == 'hitam' ? 'selected' : '' }}>Hitam</option>
                                <option value="putih" {{ strtolower(old('default_rack_color', $product->default_rack_color ?? '')) == 'putih' ? 'selected' : '' }}>Putih</option>
                            </select>
                            @error('default_rack_color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="card-footer bg-light text-end">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        const typeSelect = document.getElementById('required_papan_type');
        const qtyInput = document.getElementById('required_papan_quantity');

        if (!typeSelect || !qtyInput) return;

        function sync() {
            const v = typeSelect.value === 'gandeng' ? 2 : 1;
            qtyInput.value = v;
        }

        // initialize
        sync();

        typeSelect.addEventListener('change', sync);
    })();
</script>
@endpush
