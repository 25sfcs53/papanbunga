@extends('layouts.app')

@section('title', 'Manajemen Pengeluaran')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4">Manajemen Pengeluaran</h1>
                    <p class="text-muted">Daftar pengeluaran. Akses: Admin & Owner.</p>
                </div>
                <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                    Catat Pengeluaran
                </a>
            </div>

            <!-- Form Pencarian -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('expenses.index') }}" class="row g-3">
                        <div class="col-md-10">
                            <input type="text"
                                   class="form-control"
                                   name="search"
                                   value="{{ $search ?? '' }}"
                                   placeholder="Cari berdasarkan deskripsi atau kategori...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </form>
                    @if(!empty($search))
                        <div class="mt-2">
                            <small class="text-muted">
                                Hasil pencarian untuk: "<strong>{{ $search }}</strong>"
                                <a href="{{ route('expenses.index') }}" class="text-decoration-none ms-2">
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
                                <th scope="col">Tanggal</th>
                                <th scope="col">Kategori</th>
                                <th scope="col">Deskripsi</th>
                                <th scope="col" class="text-end">Jumlah (Rp)</th>
                                <th scope="col" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $categories = \App\Models\Expense::getCategories();
                            @endphp
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td>{{ $expense->date?->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $categories[$expense->category] ?? $expense->category }}</span>
                                    </td>

                                    <td>{{ $expense->description ?: '-' }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Hapus pengeluaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        @if(!empty($search))
                                            Tidak ada pengeluaran yang ditemukan untuk pencarian "{{ $search }}".
                                        @else
                                            Belum ada pengeluaran.
                                        @endif
                                    </td>
                                </tr>

                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($expenses->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">
                            Menampilkan {{ $expenses->firstItem() ?? 0 }} - {{ $expenses->lastItem() ?? 0 }}
                            dari {{ $expenses->total() }} pengeluaran
                            @if(!empty($search))
                                (hasil pencarian)
                            @endif
                        </small>
                    </div>
                    <div>
                        {{ $expenses->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @elseif($expenses->count() > 0)
                <div class="card-footer">
                    <small class="text-muted">
                        Total: {{ $expenses->count() }} pengeluaran
                        @if(!empty($search))
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
