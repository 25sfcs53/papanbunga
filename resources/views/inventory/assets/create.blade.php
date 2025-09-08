@extends('layouts.app')

@section('title', 'Tambah Aset')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4">Tambah Aset</h1>
                </div>

                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('assets.store') }}" id="asset-form">
                        @csrf

                        <div class="mb-3">
                            <label for="type" class="form-label">Tipe Aset</label>
                            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="papan" @selected(old('type')==='papan')>Papan Dasar</option>
                                <option value="rak" @selected(old('type')==='rak')>Rak Penyangga</option>
                            </select>
                            @error('type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Warna</label>
                                <select id="color" name="color" class="form-select @error('color') is-invalid @enderror">
                                    <option value="">-- Pilih Warna --</option>
                                    @foreach($meta['colors'] as $c)
                                        {{-- mark each option with its type so JS can filter: data-type="papan" or "rak" --}}
                                        <option value="{{ $c->name }}" data-type="{{ $c->type }}" @selected(old('color')===$c->name)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('color')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="quantity_total" class="form-label">Jumlah (Quantity)</label>
                                <input id="quantity_total" type="number" min="1" max="99" inputmode="numeric" class="form-control @error('quantity_total') is-invalid @enderror" name="quantity_total" value="{{ old('quantity_total', 1) }}">
                                @error('quantity_total')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Status hidden on create; default to 'tersedia' for new assets --}}

                        </div>

                        <div class="mb-3 d-flex gap-2">
                            <button type="button" id="add-item" class="btn btn-outline-primary">Tambah ke Daftar</button>
                            <a href="{{ route('assets.index') }}" class="btn btn-secondary">Batal</a>
                        </div>

                        <div class="mb-3">
                            <h5>Daftar Item (akan ditambahkan sebelum submit)</h5>
                            <table class="table table-sm" id="items-table">
                                <thead>
                                    <tr>
                                        <th>Tipe</th>
                                        <th>Warna</th>
                                        <th>Jumlah</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Simpan Semua</button>
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
document.addEventListener('DOMContentLoaded', function(){
    const addBtn = document.getElementById('add-item');
    const itemsTableBody = document.querySelector('#items-table tbody');

    function createRow(type, color, qty, status){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${type}</td>
            <td>${color || '-'}</td>
            <td>${qty}</td>
            <td><button type="button" class="btn btn-sm btn-danger remove-item">Hapus</button></td>
        `;
        tr.dataset.type = type;
        tr.dataset.color = color || '';
        tr.dataset.qty = qty;
        tr.dataset.status = status || '';
        return tr;
    }

    addBtn.addEventListener('click', function(){
        const typeEl = document.getElementById('type');
        const colorEl = document.getElementById('color');
    // status is hidden on create, default to 'tersedia'
    const statusEl = null;
        const qtyEl = document.getElementById('quantity_total');

        const type = typeEl ? typeEl.value : '';
        const color = colorEl ? colorEl.value : '';
    const status = 'tersedia';
        const qty = qtyEl ? (qtyEl.value || 1) : 1;

        if(!type){
            alert('Pilih tipe terlebih dahulu');
            return;
        }

        if(!color){
            alert('Pilih warna terlebih dahulu');
            return;
        }

        const row = createRow(type, color, qty, status);
        itemsTableBody.appendChild(row);
    });

    // Filter color options based on selected asset type
    const typeSelect = document.getElementById('type');
    const colorSelect = document.getElementById('color');
    function filterColorsByType() {
        if (!typeSelect || !colorSelect) return;
        const selectedType = typeSelect.value;
        // keep the placeholder option (value="") visible
        const placeholder = colorSelect.querySelector('option[value=""]');
        // track whether current selected color is valid for the type
        let currentValid = false;
        Array.from(colorSelect.options).forEach(opt => {
            const optType = opt.dataset.type || '';
            if (opt.value === '') {
                opt.hidden = false;
                opt.disabled = false;
                return;
            }
            if (!selectedType) {
                // no type selected: hide all specific color options
                opt.hidden = true;
                opt.disabled = true;
            } else if (optType === selectedType) {
                opt.hidden = false;
                opt.disabled = false;
                if (opt.selected) currentValid = true;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        });
        // if current selection is not valid, reset to placeholder
        if (!currentValid) {
            if (placeholder) placeholder.selected = true;
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', filterColorsByType);
        // initialize on load (useful when old() has values)
        filterColorsByType();
    }

    // Limit quantity input to 2 digits (1-99)
    const qtyInput = document.getElementById('quantity_total');
    if (qtyInput) {
        qtyInput.addEventListener('input', function () {
            // allow user to clear the field while editing (so backspace works)
            // remove non-digit characters
            let digits = (this.value || '').toString().replace(/\D/g, '');
            // limit to 2 digits
            digits = digits.slice(0, 2);
            if (digits === '') {
                // leave empty while the user is typing
                this.value = '';
                return;
            }
            // parse and clamp max
            let n = parseInt(digits, 10);
            if (n > 99) n = 99;
            this.value = n;
        });

        // enforce minimum when the field loses focus
        qtyInput.addEventListener('blur', function () {
            let v = parseInt(this.value || '0', 10) || 0;
            if (v < 1) v = 1;
            if (v > 99) v = 99;
            this.value = v;
        });

        // ensure initial value within range on load
        (function(){
            let v = parseInt(qtyInput.value || '0', 10) || 1;
            if (v < 1) v = 1;
            if (v > 99) v = 99;
            qtyInput.value = v;
        })();
    }

    itemsTableBody.addEventListener('click', function(e){
        if(e.target.classList.contains('remove-item')){
            e.target.closest('tr').remove();
        }
    });

    // Before submit, inject hidden inputs for each item
    document.getElementById('asset-form').addEventListener('submit', function(e){
        // remove previous items inputs
        document.querySelectorAll('input[name^="items"]').forEach(n => n.remove());

        const rows = Array.from(itemsTableBody.querySelectorAll('tr'));
        if(rows.length === 0){
            // if no rows, build single item from current fields
            const type = document.getElementById('type').value;
            const color = document.getElementById('color').value || '';
            const status = (document.getElementById('status') && document.getElementById('status').value) || 'tersedia';
            const qty = document.getElementById('quantity_total').value || 1;

            // require type+color for single-item submit as well
            if (!type || !color) {
                e.preventDefault();
                alert('Pilih tipe dan warna/model terlebih dahulu, lalu tambahkan ke daftar.');
                return false;
            }

            const inputs = {
                'items[0][type]': type,
                'items[0][color]': color,
                'items[0][status]': status,
                'items[0][quantity_total]': qty,
            };
            for(const name in inputs){
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = name;
                i.value = inputs[name];
                this.appendChild(i);
            }
            return; // allow submit
        }

        rows.forEach((r, idx) => {
            const type = r.dataset.type;
            const color = r.dataset.color;
            const qty = r.dataset.qty;
            const status = r.dataset.status;

            const fields = {
                [`items[${idx}][type]`]: type,
                [`items[${idx}][color]`]: color,
                [`items[${idx}][status]`]: status,
                [`items[${idx}][quantity_total]`]: qty,
            };

            for(const name in fields){
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = name;
                i.value = fields[name];
                this.appendChild(i);
            }
        });
    });
});
</script>
@endpush
