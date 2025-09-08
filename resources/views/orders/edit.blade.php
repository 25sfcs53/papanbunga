@extends('layouts.app')

@section('title', 'Edit Pesanan')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">Edit Pesanan #{{ $order->id }}</h1>
                    <p class="text-muted mb-0">Perbarui informasi pesanan pelanggan</p>
                </div>
                <div>
                    <span class="badge bg-{{ $order->status === 'Selesai' ? 'success' : ($order->status === 'Dibatalkan' ? 'danger' : 'warning') }} fs-6">
                        {{ $order->status }}
                    </span>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Form Edit Pesanan
                    </h5>
                </div>
                <div class="card-body">
                    <form id="order-edit-form" method="POST" action="{{ route('orders.update', $order) }}" x-data="orderForm()" data-current-status="{{ strtolower($order->status) }}">
                        @csrf
                        @method('PUT')

                        {{-- Validation summary (like create page) --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Informasi Pelanggan & Tanggal -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label fw-semibold">
                                    <i class="fas fa-user me-1"></i>Pelanggan
                                </label>
                                <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" @selected(old('customer_id', $order->customer_id) == $c->id)>
                                            {{ $c->name }}{{ $c->phone_number ? ' - ' . $c->phone_number : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="delivery_date" class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1"></i>Tanggal Pengiriman
                                </label>
                                <input id="delivery_date" type="date" class="form-control @error('delivery_date') is-invalid @enderror" name="delivery_date" value="{{ old('delivery_date', $order->delivery_date?->format('Y-m-d')) }}" required>
                                @error('delivery_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="shipping_address" class="form-label fw-semibold">Alamat Pengiriman</label>
                                <textarea id="shipping_address" name="shipping_address" rows="3" class="form-control @error('shipping_address') is-invalid @enderror" placeholder="Masukkan alamat pengiriman lengkap (opsional)">{{ old('shipping_address', $order->shipping_address) }}</textarea>
                                @error('shipping_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Informasi Produk -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-box me-2"></i>Informasi Produk</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="product_id" class="form-label fw-semibold">Varian Produk</label>
                                        <select id="product_id" name="product_id" x-model="productId" @change="onProductChange" class="form-select @error('product_id') is-invalid @enderror" required>
                                            <option value="">-- Pilih Varian --</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" @selected(old('product_id', $order->product_id) == $p->id) data-price="{{ (float) $p->base_price }}" data-rack-color="{{ $p->default_rack_color }}">
                                                    {{ $p->name }}
                                                </option>
                                            @endforeach

                                        </select>
                                        @error('product_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="base_price" class="form-label fw-semibold">Harga Dasar</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input id="base_price" type="text" class="form-control bg-light" :value="basePriceDisplay" readonly>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>Terisi otomatis dari varian produk
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Diskon & Harga -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-percentage me-2"></i>Diskon & Harga Final</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="discount_type" class="form-label fw-semibold">Tipe Diskon</label>
                                        <select id="discount_type" name="discount_type" x-model="discountType" @change="recalc" class="form-select @error('discount_type') is-invalid @enderror">
                                            <option value="">Tidak ada diskon</option>
                                            @php $dt = old('discount_type', $order->discount_type); @endphp
                                            <option value="percent" @selected($dt==='percent')>Persentase (%)</option>
                                            <option value="fixed" @selected($dt==='fixed')>Potongan Langsung (Rp)</option>
                                        </select>
                                        @error('discount_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="discount_value" class="form-label fw-semibold">Nilai Diskon</label>
                                        <div class="input-group">
                                            <span class="input-group-text" x-text="discountType === 'percent' ? '%' : 'Rp'"></span>
                                            <input id="discount_value" type="number" step="any" min="0" class="form-control @error('discount_value') is-invalid @enderror" name="discount_value" x-model.number="discountValue" :disabled="!discountType" @input="recalc" placeholder="0">
                                        </div>
                                        @error('discount_value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="final_price" class="form-label fw-semibold">Harga Akhir</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input id="final_price" type="text" class="form-control bg-warning bg-opacity-25 fw-bold" :value="finalPriceDisplay" readonly>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-calculator me-1"></i>Perhitungan otomatis
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Aset dialokasikan otomatis berdasarkan varian produk. Edit papan/rak dinonaktifkan pada form ini. --}}


                        <!-- Informasi Tambahan -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="text_content" class="form-label fw-semibold">
                                    <i class="fas fa-comment me-1"></i>Ucapan / Konten Tulisan
                                </label>
                                <div class="d-flex gap-2">
                                    <textarea id="text_content" name="text_content" rows="4" class="form-control @error('text_content') is-invalid @enderror" placeholder="Masukkan ucapan atau pesan khusus">{{ old('text_content', $order->text_content) }}</textarea>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddModal">Quick Add</button>
                                    </div>
                                </div>
                                @error('text_content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Pesan yang akan ditampilkan pada papan bunga</div>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">
                                    <i class="fas fa-flag me-1"></i>Status Pesanan
                                </label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @php
                                        // Normalize current status to lowercase to match option values
                                        $currentStatus = strtolower((string) old('status', $order->status));
                                        $statusOptions = [
                                            'pending' => 'Pending',
                                            'disewa' => 'Disewa',
                                            'selesai' => 'Selesai'
                                        ];
                                    @endphp
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($currentStatus === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Perubahan status akan mempengaruhi inventaris secara otomatis
                                </div>
                            </div>

                        </div>

                        {{-- Hidden container for selected quick-add components (prepopulate from order) --}}
                        <div id="selected-components" class="mb-3">
                            {{-- server-side prepopulate --}}
                            @php
                                $sel = old('components');
                                if (empty($sel)) {
                                    $sel = [];
                                    foreach($order->components as $c) {
                                        $sel[] = ['id' => $c->id, 'quantity' => $c->pivot->quantity_used ?? 1];
                                    }
                                }
                            @endphp
                            @foreach($sel as $i => $c)
                                <input type="hidden" name="components[{{ $i }}][id]" value="{{ $c['id'] }}">
                                <input type="hidden" name="components[{{ $i }}][quantity]" value="{{ $c['quantity'] }}">
                            @endforeach
                        </div>

                        {{-- Quick Add Modal (same as create) --}}
                        <div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Quick Add Komponen</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted">Pilih komponen hiasan, simbol atau kata sambung untuk menambahkannya ke ucapan.</p>
                                        <div class="row">
                                            @foreach(($components ?? collect())->groupBy('type') as $type => $group)
                                                <div class="col-md-4 mb-3">
                                                    <h6 class="small text-capitalize">{{ str_replace('_',' ', $type) }}</h6>
                                                    <ul class="list-group">
                                                        @foreach($group as $comp)
                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                <div>{{ $comp->name }}</div>
                                                                <div>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary add-component-btn" data-name="{{ $comp->name }}" data-id="{{ $comp->id }}">Add</button>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card">
                            <div class="card-body bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            Terakhir diperbarui: 
                                            <time class="js-local-datetime" datetime="{{ $order->updated_at->toIso8601String() }}">{{ $order->updated_at->format('d M Y, H:i') }}</time>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-arrow-left me-1"></i>Kembali
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Update Pesanan
                                        </button>
                                    </div>
                                </div>
                            </div>
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
document.addEventListener('alpine:init', () => {
    Alpine.data('orderForm', () => ({
        // Data from Blade
        productId: '{{ old('product_id', $order->product_id) }}',
        rackId: '{{ old('rak_id', $order->rak_id) }}',
        basePrice: 0,
        discountType: '{{ old('discount_type', $order->discount_type) }}',
        discountValue: Number('{{ old('discount_value', (float)$order->discount_value) }}') || 0,

        // Initialization
        init() {
            this.$nextTick(() => {
                this.updateProductDetails();
                // On edit, we don't auto-recommend a rack, we just use the saved one.
                // The onProductChange will handle recommendations if the user changes the product.
            });
            this.$watch('productId', () => this.onProductChange());
        },

        // Computed Properties for Display
        basePriceDisplay() {
            return this.formatRupiah(this.basePrice);
        },
        finalPriceDisplay() {
            let final = this.basePrice || 0;
            if (this.discountType === 'percent') {
                final -= (final * (Number(this.discountValue) / 100));
            } else if (this.discountType === 'fixed') {
                final -= Number(this.discountValue);
            }
            return this.formatRupiah(Math.max(0, final));
        },

        // Methods
        onProductChange() {
            this.updateProductDetails();
            this.recommendRack();
        },

        updateProductDetails() {
            const select = document.getElementById('product_id');
            if (!select.value) {
                this.basePrice = 0;
                return;
            }
            const opt = select.options[select.selectedIndex];
            this.basePrice = opt ? Number(opt.getAttribute('data-price') || 0) : 0;
        },

        recommendRack() {
            const productSelect = document.getElementById('product_id');
            if (!productSelect.value) return;

            const selectedProductOption = productSelect.options[productSelect.selectedIndex];
            const defaultRackColor = selectedProductOption.getAttribute('data-rack-color');
            
            const rackSelect = document.getElementById('rak_id');
            let recommendedRackId = '';

            for (let option of rackSelect.options) {
                if (option.getAttribute('data-color') === defaultRackColor) {
                    recommendedRackId = option.value;
                    break;
                }
            }
            
            this.rackId = recommendedRackId;
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
                minimumFractionDigits: 0
            }).format(num || 0);
        }
    }));
});
</script>
<script>
// Quick Add components: insert name into textarea and track selected components
document.addEventListener('DOMContentLoaded', function () {
    const selectedContainer = document.getElementById('selected-components');
    const textArea = document.getElementById('text_content');

    function renderHiddenInputs(selected) {
        selectedContainer.innerHTML = '';
        let idx = 0;
        for (const id in selected) {
            const qty = selected[id];
            const wrap = document.createElement('div');
            wrap.innerHTML = `
                <input type="hidden" name="components[${idx}][id]" value="${id}">
                <input type="hidden" name="components[${idx}][quantity]" value="${qty}">
            `;
            selectedContainer.appendChild(wrap);
            idx++;
        }
    }

    // Initialize selected from any existing hidden inputs (server-side prepopulation)
    const selected = {};
    Array.from(selectedContainer.querySelectorAll('input[name$="[id]"]')).forEach((inp, i) => {
        const id = inp.value;
        const qtyInput = selectedContainer.querySelector(`input[name="components[${i}][quantity]"]`);
        const qty = qtyInput ? Number(qtyInput.value) : 1;
        if (id) selected[id] = (selected[id] || 0) + qty;
    });

    // Ensure inputs match initial map (in case of duplicates)
    renderHiddenInputs(selected);

    document.querySelectorAll('.add-component-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const name = this.getAttribute('data-name');
            const id = this.getAttribute('data-id');

            // append name to textarea (with space)
            if (textArea.value.trim() !== '') textArea.value = textArea.value.trim() + ' ' + name; else textArea.value = name;

            // increment selected quantity
            selected[id] = (selected[id] || 0) + 1;
            renderHiddenInputs(selected);

            // keep modal open for convenience
        });
    });
});
</script>
<script>
// Confirm status change from disewa -> pending on edit form
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('order-edit-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const current = (form.getAttribute('data-current-status') || '').toLowerCase();
        const select = form.querySelector('#status');
        const next = select ? select.value.toLowerCase() : '';
        if (current === 'disewa' && next === 'pending') {
            if (!confirm('Mengubah status dari Disewa ke Pending akan mengembalikan aset dan komponen yang digunakan/disewa ke stok. Lanjutkan?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
<script>
// Convert server timestamps to the client's local time display
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-local-datetime').forEach(function (el) {
        try {
            const iso = el.getAttribute('datetime');
            if (!iso) return;
            const dt = new Date(iso);
            if (isNaN(dt.getTime())) return;
            // Format using locale with date and time
            const formatted = dt.toLocaleString(undefined, { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
            el.textContent = formatted;
        } catch (e) {
            // ignore
        }
    });
});
</script>
@endpush
