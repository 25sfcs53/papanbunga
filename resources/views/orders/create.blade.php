@extends('layouts.app')

@section('title', 'Tambah Pesanan')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">Tambah Pesanan</h1>
                    <p class="text-muted mb-0">Buat pesanan baru untuk pelanggan</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Form Tambah Pesanan
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('orders.store') }}" x-data="orderForm()">
                        @csrf

                        {{-- Validation / error summary --}}
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Informasi Pelanggan & Tanggal -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label fw-semibold">Pelanggan</label>
                                <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}{{ $c->phone_number ? ' - ' . $c->phone_number : '' }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="delivery_date" class="form-label fw-semibold">Tanggal Pengiriman</label>
                                <input id="delivery_date" type="date" class="form-control @error('delivery_date') is-invalid @enderror" name="delivery_date" value="{{ old('delivery_date') }}" required>
                                @error('delivery_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="shipping_address" class="form-label fw-semibold">Alamat Pengiriman</label>
                                <textarea id="shipping_address" name="shipping_address" rows="3" class="form-control @error('shipping_address') is-invalid @enderror" placeholder="Masukkan alamat pengiriman lengkap" required>{{ old('shipping_address') }}</textarea>
                                @error('shipping_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="text_content" class="form-label fw-semibold">
                                    <i class="fas fa-comment me-1"></i>Ucapan / Konten Tulisan
                                </label>
                                <div class="d-flex gap-2">
                                    <textarea id="text_content" name="text_content" rows="3" class="form-control @error('text_content') is-invalid @enderror" placeholder="Masukkan ucapan sesuai permintaan pelanggan" required>{{ old('text_content') }}</textarea>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddModal">Quick Add</button>
                                    </div>
                                </div>
                                <div id="quick-add-pills-container" class="d-flex flex-wrap gap-1 mt-2">
                                    {{-- Pills for selected components will be rendered here by JS --}}
                                </div>
                                @error('text_content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Pesan yang akan ditampilkan pada papan bunga — sistem akan otomatis mengalokasikan komponen berdasarkan karakter.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-semibold">Status Pesanan</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="pending" @selected(old('status')==='pending')>Pending</option>
                                    <option value="disewa" @selected(old('status')==='disewa')>Disewa</option>
                                    <option value="selesai" @selected(old('status')==='selesai')>Selesai</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        {{-- Hidden container for selected quick-add components --}}
                        <div id="selected-components" class="mb-3">
                            {{-- JS will render hidden inputs here: components[index][id], components[index][quantity] --}}
                        </div>

                        {{-- Quick Add Modal --}}
                        <div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Quick Add Komponen</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted">Pilih komponen hiasan atau kata sambung untuk menambahkannya ke ucapan.</p>
                                        <div class="row">
                                            @foreach($components->groupBy('type') as $type => $group)
                                                @if($type === 'kata_sambung' || $type === 'hiasan')
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="small text-capitalize">{{ str_replace('_',' ', $type) }}</h6>
                                                    <ul class="list-group">
                                                        @foreach($group as $comp)
                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    {{ $comp->name }}
                                                                    <span class="badge bg-primary rounded-pill ms-2" data-badge-id="{{ $comp->id }}" style="display: none;"></span>
                                                                </div>
                                                                <div>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary add-component-btn" data-name="{{ $comp->name }}" data-id="{{ $comp->id }}">Add</button>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
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
                                                @if(! ($p->active ?? true))
                                                    @continue
                                                @endif
                                                <option value="{{ $p->id }}" @selected(old('product_id') == $p->id) data-price="{{ (float) $p->base_price }}">{{ $p->name }}</option>
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
                                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Terisi otomatis dari varian produk</div>
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
                                            <option value="percent" @selected(old('discount_type')==='percent')>Persentase (%)</option>
                                            <option value="fixed" @selected(old('discount_type')==='fixed')>Potongan Langsung (Rp)</option>
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
                                        <div class="form-text"><i class="fas fa-calculator me-1"></i>Perhitungan otomatis</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alokasi Aset -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-dolly-flatbed me-2"></i>Alokasi Aset & Komponen</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Aset (Papan & Rak) dan Komponen akan dialokasikan secara otomatis oleh sistem berdasarkan Varian Produk yang dipilih dan Ucapan yang dibuat.
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
                                            Isi form lalu klik "Buat Pesanan" untuk menyimpan.
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-arrow-left me-1"></i>Kembali
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Buat Pesanan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderForm', () => ({
        // Data
        productId: '{{ old('product_id') }}',
        basePrice: 0,
        discountType: '{{ old('discount_type') }}',
        discountValue: Number('{{ old('discount_value', 0) }}') || 0,

        // Initialization
        init() {
            if (this.productId) {
                this.$nextTick(() => this.updateProductDetails());
            }
            this.$watch('productId', () => this.updateProductDetails());
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
        updateProductDetails() {
            const select = document.getElementById('product_id');
            if (!select.value) {
                this.basePrice = 0;
                return;
            }
            const opt = select.options[select.selectedIndex];
            this.basePrice = opt ? Number(opt.getAttribute('data-price') || 0) : 0;
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
    const pillsContainer = document.getElementById('quick-add-pills-container');

    function renderHiddenInputs(selected) {
        selectedContainer.innerHTML = '';
        let idx = 0;
        for (const id in selected) {
            const qty = selected[id];
            if (qty > 0) {
                const wrap = document.createElement('div');
                wrap.innerHTML = `
                    <input type="hidden" name="components[${idx}][id]" value="${id}">
                    <input type="hidden" name="components[${idx}][quantity]" value="${qty}">
                `;
                selectedContainer.appendChild(wrap);
                idx++;
            }
        }
    }

    function renderPills(selected) {
        pillsContainer.innerHTML = '';
        for (const id in selected) {
            if (selected[id] > 0) {
                const comp = document.querySelector(`.add-component-btn[data-id="${id}"]`);
                if (comp) {
                    const name = comp.getAttribute('data-name');
                    const pill = document.createElement('span');
                    pill.className = 'badge rounded-pill bg-secondary d-flex align-items-center';
                    pill.innerHTML = `
                        ${name}
                        <button type="button" class="btn-close btn-close-white ms-1" aria-label="Close" data-remove-id="${id}"></button>
                    `;
                    pillsContainer.appendChild(pill);
                }
            }
        }
    }

    function updateBadge(id, count) {
        const badge = document.querySelector(`[data-badge-id="${id}"]`);
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    const selected = {};

    document.querySelectorAll('.add-component-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const name = this.getAttribute('data-name');
            const id = this.getAttribute('data-id');

            // append name to textarea (with space)
            if (textArea.value.trim() !== '') textArea.value = textArea.value.trim() + ' ' + name; else textArea.value = name;

            // increment selected quantity
            selected[id] = (selected[id] || 0) + 1;
            renderHiddenInputs(selected);
            renderPills(selected);
            updateBadge(id, selected[id]);
        });
    });

    pillsContainer.addEventListener('click', function(e) {
        if (e.target.matches('[data-remove-id]')) {
            const idToRemove = e.target.getAttribute('data-remove-id');
            const comp = document.querySelector(`.add-component-btn[data-id="${idToRemove}"]`);
            const nameToRemove = comp ? comp.getAttribute('data-name') : '';

            if (selected[idToRemove] > 0) {
                selected[idToRemove]--;

                // Remove one instance of the name from textarea
                const regex = new RegExp(`\\s?${nameToRemove.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}`);
                textArea.value = textArea.value.replace(regex, '');

                renderHiddenInputs(selected);
                renderPills(selected);
                updateBadge(idToRemove, selected[idToRemove]);
            }
        }
    });
});
</script>
@endpush
