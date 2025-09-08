@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="h4 mb-4">Laporan Keuangan</h1>

            {{-- Filter Tanggal --}}
            <div class="card mb-4">
                <div class="card-header">
                    Filter Laporan
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-5">
                            <label for="end_date" class="form-label">Tanggal Selesai</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Ringkasan Laba/Rugi --}}
            <div class="card mb-4">
                <div class="card-header fw-bold">
                    Ringkasan Laba/Rugi ({{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }})
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5 class="text-success">Total Pemasukan</h5>
                            <p class="fs-5 fw-bold">{{ 'Rp ' . number_format($totalIncome, 0, ',', '.') }}</p>
                        </div>
                        <div class="col-md-4">
                            <h5 class="text-danger">Total Pengeluaran</h5>
                            <p class="fs-5 fw-bold">{{ 'Rp ' . number_format($totalExpense, 0, ',', '.') }}</p>
                        </div>
                        <div class="col-md-4">
                            <h5 class="{{ $netProfit >= 0 ? 'text-primary' : 'text-warning' }}">Laba/Rugi Bersih</h5>
                            <p class="fs-5 fw-bold">{{ 'Rp ' . number_format($netProfit, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Tabel Pemasukan --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Laporan Pemasukan</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal Selesai</th>
                                            <th>Pesanan</th>
                                            <th class="text-end">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($incomeReport as $order)
                                            <tr>
                                                <td>{{ $order->updated_at->format('d M Y') }}</td>
                                                <td>
                                                    #{{ $order->id }} - {{ $order->product?->name }}
                                                    <small class="d-block text-muted">{{ $order->customer?->name }}</small>
                                                </td>
                                                <td class="text-end">{{ 'Rp ' . number_format($order->final_price, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center">Tidak ada pemasukan pada periode ini.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabel Pengeluaran --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Laporan Pengeluaran</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kategori</th>
                                            <th>Deskripsi</th>
                                            <th class="text-end">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $categories = (new \App\Models\Expense)->getCategories();
                                        @endphp
                                        @forelse ($expenseReport as $expense)
                                            <tr>
                                                <td>{{ $expense->date->format('d M Y') }}</td>
                                                <td>{{ $categories[$expense->category] ?? $expense->category }}</td>
                                                <td>{{ $expense->description ?: '-' }}</td>
                                                <td class="text-end">{{ 'Rp ' . number_format($expense->amount, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center">Tidak ada pengeluaran pada periode ini.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
