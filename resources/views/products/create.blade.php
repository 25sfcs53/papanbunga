@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4">Tambah Produk (Varian)</h1>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Varian</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="base_price_display" class="form-label">Harga Dasar (Rp)</label>
                            <!-- Visible formatted input -->
                            <input id="base_price_display" type="text" inputmode="numeric" autocomplete="off" class="form-control @error('base_price') is-invalid @enderror" value="" required>
                            <!-- Hidden numeric value submitted to server -->
                            <input id="base_price" type="hidden" name="base_price" value="{{ old('base_price') }}">
                            @error('base_price')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="photo" class="form-label">Foto Varian</label>
                            <div class="d-flex align-items-center">
                            <img id="photoPreview" src="" alt="Preview Foto" class="img-thumbnail" style="display:none; width:400px; height:400px; object-fit:cover;">
                            @error('photo')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <input id="photo" type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept="image/*">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="required_papan_color" class="form-label">Warna Papan Hias</label>
                                <select id="required_papan_color" class="form-select @error('required_papan_color') is-invalid @enderror" name="required_papan_color" required>
                                    <option value="">-- Pilih Warna Papan --</option>
                    @foreach($papanColors ?? [] as $color)
                        <option value="{{ $color->name }}" @selected(old('required_papan_color') == $color->name)>{{ $color->name }}</option>
                    @endforeach
                                </select>
                                @error('required_papan_color')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="required_papan_type" class="form-label">Tipe Papan</label>
                                @php
                                    // Default to empty so user must choose type explicitly
                                    $selectedType = old('required_papan_quantity_type', '');
                                @endphp
                                <select id="required_papan_type" class="form-select" name="required_papan_quantity_type" required>
                                    <option value="" {{ $selectedType === '' ? 'selected' : '' }}>-- Pilih Tipe Papan --</option>
                                    <option value="single" {{ $selectedType == 'single' ? 'selected' : '' }}>Single</option>
                                    <option value="gandeng" {{ $selectedType == 'gandeng' ? 'selected' : '' }}>Gandeng</option>
                                </select>
                                <input id="required_papan_quantity" type="hidden" name="required_papan_quantity" value="{{ old('required_papan_quantity', '') }}">
                                @error('required_papan_quantity')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="default_rack_color" class="form-label">Warna Rak Penyangga</label>
                            <select id="default_rack_color" class="form-select @error('default_rack_color') is-invalid @enderror" name="default_rack_color" required>
                                <option value="" {{ old('default_rack_color', '') === '' ? 'selected' : '' }}>-- Pilih Warna Rak --</option>
                                <option value="hitam" {{ old('default_rack_color') == 'hitam' ? 'selected' : '' }}>Hitam</option>
                                <option value="putih" {{ old('default_rack_color') == 'putih' ? 'selected' : '' }}>Putih</option>
                            </select>
                            @error('default_rack_color')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi(Opsional)</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>



                        <div class="d-flex justify-content-end">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
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
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('required_papan_type');
    const qtyInput = document.getElementById('required_papan_quantity');
    function sync() {
        if (!typeSelect || !qtyInput) return;
        if (!typeSelect.value) {
            qtyInput.value = '';
            return;
        }
        qtyInput.value = (typeSelect.value === 'gandeng') ? 2 : 1;
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', sync);
        // Only sync on init if a value was already selected (old input)
        if (typeSelect.value) {
            sync();
        }
    }
    // Photo preview
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                photoPreview.style.display = 'none';
                photoPreview.src = '';
                return;
            }
            const url = URL.createObjectURL(file);
            photoPreview.src = url;
            photoPreview.style.display = '';
        });
    }
    // Harga formatting (Rupiah) and digit limit
    const priceDisplay = document.getElementById('base_price_display');
    const priceHidden = document.getElementById('base_price');
    function formatRupiah(value) {
        if (!value) return '';
        const num = parseInt(value.replace(/\D/g, '') || '0', 10);
        return new Intl.NumberFormat('id-ID').format(num);
    }
    function setPriceFromDisplay() {
        if (!priceDisplay || !priceHidden) return;
        // Extract digits and limit to 8 digits
        let digits = (priceDisplay.value || '').replace(/\D/g, '').slice(0, 8);
        priceHidden.value = digits ? parseInt(digits, 10) : '';
        priceDisplay.value = digits ? 'Rp ' + formatRupiah(digits) : '';
    }
    if (priceDisplay && priceHidden) {
        // Initialize from old value if present
        if (priceHidden.value) {
            priceDisplay.value = 'Rp ' + formatRupiah(priceHidden.value);
        }
        priceDisplay.addEventListener('input', setPriceFromDisplay);
        // Ensure proper value just before submit
        const form = priceDisplay.closest('form');
        if (form) {
            form.addEventListener('submit', setPriceFromDisplay);
        }
    }
});
</script>
@endpush
