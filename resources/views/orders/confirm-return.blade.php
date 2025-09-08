@extends('layouts.app')

@section('title', 'Konfirmasi Penyelesaian Pesanan')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Konfirmasi Penyelesaian Pesanan #{{ $order->id }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Periksa kondisi aset yang dipinjam dan pilih apakah komponen akan dikembalikan ke stok. Warna rak yang diperlukan: <strong>{{ $order->required_rack_color }}</strong></p>


                    <form method="POST" action="{{ route('orders.complete', $order) }}">
                        @csrf

                        <h6 class="mt-3">Aset Teralokasi:</h6>
                        @if($order->assets->isEmpty())
                            <p class="text-muted">Tidak ada aset teralokasi pada pesanan ini.</p>
                        @else
                            @foreach($order->assets as $asset)
                                <div class="mb-3 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>#{{ $asset->id }} - {{ ucfirst($asset->type) }}</strong>
                                            <div class="text-muted">{{ $asset->color ?? '—' }} • {{ $asset->size ?? '—' }}</div>
                                        </div>
                                        <div class="ms-3">
                                            <select name="assets[{{ $loop->index }}][condition]" class="form-select form-select-sm">
                                                <option value="baik">Baik</option>
                                                <option value="perbaikan">Perbaikan</option>
                                            </select>
                                            <input type="hidden" name="assets[{{ $loop->index }}][id]" value="{{ $asset->id }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <div class="form-check form-switch my-3">
                            <input class="form-check-input" type="checkbox" id="return_components" name="return_components" value="1" checked>
                            <label class="form-check-label" for="return_components">Kembalikan komponen ke stok (jika ada)</label>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-success">Selesaikan Pesanan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
