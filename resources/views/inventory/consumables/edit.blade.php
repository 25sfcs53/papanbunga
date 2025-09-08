@extends('layouts.app')

@section('title', 'Edit Consumable')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('consumables.update', $consumable) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input name="name" class="form-control" value="{{ old('name', $consumable->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Satuan</label>
                            <input name="unit" class="form-control" value="{{ old('unit', $consumable->unit) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input name="quantity" type="number" min="0" class="form-control" value="{{ old('quantity', $consumable->quantity) }}" required>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('consumables.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
