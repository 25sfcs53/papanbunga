@extends('layouts.app')

@section('title', 'Manajemen Pesanan')

@section('content')
@php
    use Illuminate\Support\Str;
@endphp
<div class="container">
    <div class="row">
        <div class="col-md-12">

            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4">Manajemen Pesanan</h1>
                    <p class="text-muted">Daftar pesanan. Akses: Admin &amp; Owner.</p>
                </div>
                <a href="{{ route('orders.create') }}" class="btn btn-primary">
                    Tambah Pesanan
                </a>
            </div>

            <!-- Form Pencarian -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
                        <div class="col-md-10">
                            <input type="text"
                                   class="form-control"
                                   name="search"
                                   value="{{ $search ?? '' }}"
                                   placeholder="Cari berdasarkan pelanggan, varian, atau status...">
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
                                <a href="{{ route('orders.index') }}" class="text-decoration-none ms-2">
                                    <i class="fas fa-times"></i> Hapus filter
                                </a>
                            </small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Tgl Kirim</th>
                                    <th scope="col" style="width: 200px; min-width: 140px;">Pelanggan</th>
                                    <th scope="col">Varian</th>
                                    <th scope="col">Harga Akhir</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td>{{ $order->delivery_date?->format('d M Y') }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width:180px;" title="{{ $order->customer?->name ?? '-' }}">
                                                {{ $order->customer?->name ?? '-' }}
                                            </div>
                                            @if($order->shipping_address)
                                                <div class="small text-muted">{{ Str::limit($order->shipping_address, 80) }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $order->product?->name ?? '-' }}</td>
                                        <td class="fw-bold">
                                            Rp {{ number_format((float) $order->final_price, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-secondary',
                                                    'disewa'  => 'bg-warning text-dark',
                                                    'selesai' => 'bg-success',
                                                ];
                                                $badge = $statusColors[strtolower($order->status)] ?? 'bg-light text-dark';
                                            @endphp
                                            <span class="badge {{ $badge }}">
                                                {{ $order->status }}
                                            </span>
                                        </td>
                                        <td class="text-end align-middle">
                                            <div class="btn-toolbar justify-content-end" role="toolbar" aria-label="Aksi pesanan">
                                                <div class="btn-group btn-group-sm me-2" role="group" aria-label="Status actions">
                                                    {{-- Quick actions: change status via small POST forms (preserve js hook) --}}
                                                    @if(strtolower($order->status) !== 'disewa')
                                                        <form action="{{ route('orders.changeStatus', $order) }}" method="POST" class="d-inline js-status-change" aria-label="Tandai disewa">
                                                            @csrf
                                                            <input type="hidden" name="status" value="disewa">
                                                            <button type="submit" class="btn btn-sm btn-warning" title="Tandai Disewa" aria-label="Tandai Disewa">
                                                                <i class="fas fa-handshake" aria-hidden="true"></i>
                                                                <span class="d-none d-md-inline ms-1">Disewa</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if(strtolower($order->status) === 'disewa')
                                                        <form action="{{ route('orders.changeStatus', $order) }}" method="POST" class="d-inline js-status-change" aria-label="Selesaikan pesanan">
                                                            @csrf
                                                            <input type="hidden" name="status" value="selesai">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Selesaikan" aria-label="Selesaikan">
                                                                <i class="fas fa-check" aria-hidden="true"></i>
                                                                <span class="d-none d-md-inline ms-1">Selesaikan</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>

                                                <div class="btn-group btn-group-sm" role="group" aria-label="Primary actions">
                                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-info" title="Lihat Detail" aria-label="Lihat detail pesanan">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                        <span class="d-none d-md-inline ms-1">Lihat</span>
                                                    </a>

                                                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-outline-secondary" title="Edit" aria-label="Edit pesanan">
                                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                                        <span class="d-none d-md-inline ms-1">Edit</span>
                                                    </a>

                                                    <form action="{{ route('orders.destroy', $order) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Yakin ingin menghapus pesanan ini?')" class="d-inline" aria-label="Hapus pesanan">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Hapus" aria-label="Hapus">
                                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                                            <span class="d-none d-md-inline ms-1">Hapus</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            @if(!empty($search))
                                                Tidak ada pesanan yang ditemukan untuk pencarian "{{ $search }}".
                                            @else
                                                Belum ada pesanan.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if ($orders->hasPages())
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }}
                                dari {{ $orders->total() }} pesanan
                                @if(!empty($search))
                                    (hasil pencarian)
                                @endif
                            </small>
                        </div>
                        <div>
                            {{ $orders->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @elseif($orders->count() > 0)
                <div class="card-footer">
                    <small class="text-muted">
                        Total: {{ $orders->count() }} pesanan
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

                @push('scripts')
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    function handleStatusForm(e) {
                        e.preventDefault();
                        const form = e.target;
                        if (!form.classList.contains('js-status-change')) return;
                        if (!confirm('Yakin ingin merubah status pesanan?')) return;

                        const url = form.action;
                        const formData = new FormData(form);

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData,
                            credentials: 'same-origin'
                        }).then(r => r.json()).then(json => {
                            if (json.status === 'success') {
                                // reload to reflect new status & buttons
                                location.reload();
                            } else {
                                alert(json.message || 'Gagal mengubah status');
                            }
                        }).catch(err => {
                            console.error(err);
                            alert('Terjadi kesalahan saat mencoba mengubah status.');
                        });
                    }

                    document.querySelectorAll('form.js-status-change').forEach(f => f.addEventListener('submit', handleStatusForm));
                });
                </script>
                @endpush
