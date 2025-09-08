@extends('layouts.app')

@section('title', 'Edit Aset')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4">Edit Aset</h1>
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

                    <form method="POST" action="{{ route('assets.update', $asset) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Tipe Aset</label>
                                @php
                                    $typeLabel = old('type', $asset->type) === 'papan' ? 'Papan Dasar' : (old('type', $asset->type) === 'rak' ? 'Rak Penyangga' : old('type', $asset->type));
                                @endphp
                                <p class="form-control-plaintext">{{ $typeLabel }}</p>
                                <input type="hidden" id="type" name="type" value="{{ old('type', $asset->type) }}">
                                @error('type')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Warna</label>
                                <p class="form-control-plaintext">{{ old('color', $asset->color) ?: '-' }}</p>
                                <input type="hidden" id="color" name="color" value="{{ old('color', $asset->color) }}">
                                @error('color')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <p class="text-muted mt-1" style="font-size: 0.9em;">Pilih status aset untuk mengubah jumlah aset sesuai status.</p>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="tersedia" @selected(old('status', $asset->status)==='tersedia')>Tersedia & Total</option>
                                    <option value="perbaikan" @selected(old('status', $asset->status)==='perbaikan')>Perlu Perbaikan</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="quantity_total" class="form-label" >Jumlah</label>
                                <p id="status-hint" class="form-text text-muted">
                                    @php $st = old('status', $asset->status); @endphp
                                    @if($st === 'tersedia')
                                        Tersedia
                                    @elseif($st === 'disewa')
                                        Disewa
                                    @elseif($st === 'perbaikan')
                                        Perbaikan
                                    @else
                                        &nbsp;
                                    @endif
                                </p>
                                <input id="quantity_total" type="number" min="0" class="form-control @error('quantity_total') is-invalid @enderror" name="quantity_total" value="{{ old('quantity_total', $asset->quantity_total) }}">
                                @error('quantity_total')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('assets.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const typeEl = document.getElementById('type');
    const colorEl = document.getElementById('color');
    const statusEl = document.getElementById('status');
    const qtyEl = document.getElementById('quantity_total');

    async function fetchCounts(){
        const type = typeEl.value;
        const color = colorEl.value || '';
        if(!type) return;

        const url = new URL("{{ route('assets.counts') }}", window.location.origin);
        url.searchParams.set('type', type);
        url.searchParams.set('color', color);

        try{
            const res = await fetch(url.toString());
            if(!res.ok) return;
            const data = await res.json();
            const s = statusEl.value || null;
            if (!s) {
                qtyEl.value = '';
                qtyEl.disabled = true;
                return;
            }
            const val = data[s] ?? 0;
            qtyEl.value = val;
            qtyEl.disabled = false;
            // update muted status hint with human-friendly label
            const hint = document.getElementById('status-hint');
            if (hint) {
                const map = { 'tersedia': 'Tersedia', 'disewa': 'Disewa', 'perbaikan': 'Perbaikan' };
                hint.textContent = map[s] ?? (s ? s : '\u00A0');
            }
        }catch(e){
            console.error(e);
        }
    }

    // disable qty until status selected
    if (qtyEl) qtyEl.disabled = true;

    // initialize hint text based on current status
    const initHint = document.getElementById('status-hint');
    if (initHint) {
        const s = statusEl.value || '';
        const map = { 'tersedia': 'Tersedia', 'disewa': 'Disewa', 'perbaikan': 'Perbaikan' };
        initHint.textContent = map[s] ?? (s ? s : '\u00A0');
    }

    if(typeEl) typeEl.addEventListener('change', function(){ qtyEl.value=''; qtyEl.disabled=true; fetchCounts(); });
    if(colorEl) colorEl.addEventListener('change', function(){ qtyEl.value=''; qtyEl.disabled=true; fetchCounts(); });
    if(statusEl) statusEl.addEventListener('change', fetchCounts);
});
</script>
@endpush
            </div>
        </div>
    </div>
</div>
@endsection
