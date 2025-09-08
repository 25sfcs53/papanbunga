@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    {{-- Tampilan untuk Owner --}}
    @if (auth()->user()->hasRole('owner'))
        @php
            $formatRupiah = fn($number) => 'Rp ' . number_format($number, 0, ',', '.');
        @endphp

        {{-- Ringkasan Keuangan --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Pemasukan (Bulan Ini)</h5>
                        <p class="card-text fs-4 fw-bold">{{ $formatRupiah($totalIncome) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Pengeluaran (Bulan Ini)</h5>
                        <p class="card-text fs-4 fw-bold">{{ $formatRupiah($totalExpense) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card {{ $netProfit >= 0 ? 'text-bg-primary' : 'text-bg-warning' }}">
                    <div class="card-body">
                        <h5 class="card-title">Laba/Rugi Bersih</h5>
                        <p class="card-text fs-4 fw-bold">{{ $formatRupiah($netProfit) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Pesanan Aktif</h5>
                        <p class="card-text fs-4 fw-bold">{{ $activeOrdersCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grafik Keuangan --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Grafik Keuangan (12 Bulan Terakhir)
                    </div>
                    <div class="card-body">
                        <canvas id="financialChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    {{-- Tampilan untuk Admin --}}
    @else
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>
                    <div class="card-body">
                        <h1 class="h4">Selamat Datang, {{ auth()->user()->name }}!</h1>
                        <p>Anda login sebagai <strong>Admin</strong>.</p>
                        <p>Gunakan menu navigasi di atas untuk mengelola pesanan, pelanggan, dan pengeluaran.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if (auth()->user()->hasRole('owner'))
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('financialChart');
    if (!ctx) return;

    const formatRupiah = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);
    };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: @json($incomeDataset),
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pengeluaran',
                    data: @json($expenseDataset),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return formatRupiah(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += formatRupiah(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endif
