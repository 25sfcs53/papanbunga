@extends('layouts.app')

@section('title', 'Kelola Warna')

@section('content')

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="h4">Kelola Warna</h1>
         
        <div>
            <a href="{{ route('warna.index', ['type' => 'papan']) }}" class="btn btn-sm {{ $type === 'papan' ? 'btn-primary' : 'btn-outline-primary' }}">Warna Papan</a>
            <a href="{{ route('warna.index', ['type' => 'rak']) }}" class="btn btn-sm {{ $type === 'rak' ? 'btn-primary' : 'btn-outline-primary' }}">Warna Rak</a>
            <a href="{{ route('warna.create', ['type' => $type]) }}" class="btn btn-primary ms-2">+ Tambah Warna</a>
        </div>
    </div>
    <a href="{{ route('assets.index') }}" class="btn btn-sm btn-secondary mb-2">Kembali ke Aset</a>
    <div class="card">
        
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th class="text-center" style="width:120px">Status</th>
                            <th class="text-center" style="width:220px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($colors as $color)
                            <tr>
                                <td class="text-start">{{ $color->name }}</td>
                                <td class="text-center">{{ $color->active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('warna.edit', $color) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('warna.toggle', $color) }}" method="POST" class="m-0">
                                            @csrf
                                            <button class="btn btn-sm btn-secondary">{{ $color->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        <form action="{{ route('warna.destroy', $color) }}" method="POST" class="m-0" onsubmit="return confirm('Hapus warna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada warna untuk tipe ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
        
@endsection
