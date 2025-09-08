@extends('layouts.app')

@section('title', 'Manajemen Pelanggan')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4">Manajemen Pelanggan</h1>
                    <p class="text-muted">Daftar pelanggan. Akses: Admin & Owner.</p>
                </div>
                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                    Tambah Pelanggan
                </a>
            </div>

            <!-- Form Pencarian -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   value="{{ $search }}" 
                                   placeholder="Cari berdasarkan nama atau nomor HP...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </form>
                    @if($search)
                        <div class="mt-2">
                            <small class="text-muted">
                                Hasil pencarian untuk: "<strong>{{ $search }}</strong>"
                                <a href="{{ route('customers.index') }}" class="text-decoration-none ms-2">
                                    <i class="fas fa-times"></i> Hapus filter
                                </a>
                            </small>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">No. HP</th>
                                <th scope="col">Dibuat</th>
                                <th scope="col" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->phone_number ?: '-' }}</td>
                                    <td>{{ $customer->created_at?->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Hapus pelanggan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        @if($search)
                                            Tidak ada pelanggan yang ditemukan untuk pencarian "{{ $search }}".
                                        @else
                                            Belum ada pelanggan.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($customers->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">
                            Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} 
                            dari {{ $customers->total() }} pelanggan
                            @if($search)
                                (hasil pencarian)
                            @endif
                        </small>
                    </div>
                    <div>
                        {{ $customers->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @elseif($customers->count() > 0)
                <div class="card-footer">
                    <small class="text-muted">
                        Total: {{ $customers->count() }} pelanggan
                        @if($search)
                            (hasil pencarian)
                        @endif
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
