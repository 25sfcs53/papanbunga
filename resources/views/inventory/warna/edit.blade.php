@extends('layouts.app')

@section('title', 'Edit Warna')

@section('content')
<div class="container">
    <h1 class="h4 mb-4">Edit Warna</h1>

            <div class="card">
                <div class="card-body">
            {{-- Top alert for validation errors (e.g., duplicate name) --}}
            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first('name') ?: 'Terdapat kesalahan pada input. Periksa kembali.' }}
                </div>
            @endif

            <form action="{{ route('warna.update', $color) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Kategori</label>
                            <select name="type" id="type" class="form-select" disabled>
                                <option value="papan" @selected(old('type', $type ?? $color->type) === 'papan')>Papan</option>
                                <option value="rak" @selected(old('type', $type ?? $color->type) === 'rak')>Rak</option>
                            </select>
                            <input type="hidden" name="type" value="{{ old('type', $type ?? $color->type) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Warna</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $color->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route('warna.index', ['type' => $type ?? $color->type]) }}" class="btn btn-secondary">Kembali</a>
            </form>
                </div>
            </div>
</div>
@endsection
