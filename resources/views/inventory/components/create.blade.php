@extends('layouts.app')

@section('title', 'Tambah Komponen')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4">Tambah Komponen</h1>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    <form method="POST" action="{{ route('components.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Komponen</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Tipe</label>
                                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="huruf_besar" @selected(old('type')==='huruf_besar')>Huruf Besar</option>
                                    <option value="huruf_kecil" @selected(old('type')==='huruf_kecil')>Huruf Kecil</option>
                                    <option value="angka" @selected(old('type')==='angka')>Angka</option>
                                    <option value="simbol" @selected(old('type')==='simbol')>Simbol</option>
                                    <option value="hiasan" @selected(old('type')==='hiasan')>Hiasan</option>
                                    <option value="kata_sambung" @selected(old('type')==='kata_sambung')>Kata Sambung</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- warna tidak digunakan untuk komponen anymore --}}
                        </div>

                        <div class="mb-3">
                            <label for="quantity_available" class="form-label">Jumlah Tersedia</label>
                            <input id="quantity_available" type="number" min="0" step="1" class="form-control @error('quantity_available') is-invalid @enderror" name="quantity_available" value="{{ old('quantity_available', 0) }}" required>
                            @error('quantity_available')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('components.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
