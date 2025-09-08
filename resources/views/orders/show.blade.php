@extends('layouts.app')

@section('title', 'Detail Pesanan #' . $order->id)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-1">Detail Pesanan #{{ $order->id }}</h1>
                    <p class="text-muted mb-0">Informasi lengkap pesanan pelanggan</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ $order->status === 'selesai' ? 'success' : ($order->status === 'pending' ? 'warning' : 'primary') }} fs-6">
                        {{ ucfirst($order->status) }}
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Aksi
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('orders.edit', $order) }}">
                                <i class="fas fa-edit me-2"></i>Edit Pesanan
                            </a></li>
                            @if($order->status !== 'selesai')
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Ubah Status</h6></li>
                            @if($order->status !== 'pending')
                            <li>
                                <form method="POST" action="{{ route('orders.changeStatus', $order) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-clock me-2 text-warning"></i>Pending
                                    </button>
                                </form>
                            </li>
                            @endif
                            @if($order->status !== 'disewa')
                            <li>
                                <form method="POST" action="{{ route('orders.changeStatus', $order) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="disewa">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-handshake me-2 text-primary"></i>Disewa
                                    </button>
                                </form>
                            </li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('orders.changeStatus', $order) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="selesai">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-check-circle me-2 text-success"></i>Selesai
                                    </button>
                                </form>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Informasi Pelanggan -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Informasi Pelanggan</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Nama Pelanggan</label>
                                    <p class="mb-2">{{ $order->customer->name }}</p>
                                </div>
                                @if($order->customer->phone_number)
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Nomor Telepon</label>
                                    <p class="mb-2">
                                        <a href="tel:{{ $order->customer->phone_number }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $order->customer->phone_number }}
                                        </a>
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Pesanan -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Informasi Pesanan</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                                    <label class="form-label fw-semibold text-muted">Tanggal Pesanan</label>
                                                    <p class="mb-2">{{ $order->created_at->timezone(config('app.timezone'))->format('d M Y') }}</p>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold text-muted">Tanggal Pengiriman</label>
                                                    <p class="mb-2">{{ $order->delivery_date->timezone(config('app.timezone'))->format('d M Y') }}</p>
                                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold text-muted">Status</label>
                                    <p class="mb-2">
                                        <span class="badge bg-{{ $order->status === 'selesai' ? 'success' : ($order->status === 'pending' ? 'warning' : 'primary') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold text-muted">Alamat Pengiriman</label>
                                    <p class="mb-0">{{ $order->shipping_address }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Terakhir Diperbarui</label>
                                    <p class="mb-0">{{ $order->updated_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Produk -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-box me-2"></i>Informasi Produk</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Varian Produk</label>
                                    <p class="mb-2">{{ $order->product->name }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Harga Dasar</label>
                                    <p class="mb-2">Rp {{ number_format($order->base_price, 0, ',', '.') }}</p>
                                </div>
                                @if($order->discount_type)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Diskon</label>
                                    <p class="mb-2">
                                        @if($order->discount_type === 'percent')
                                            {{ $order->discount_value }}% 
                                            (Rp {{ number_format($order->base_price * ($order->discount_value / 100), 0, ',', '.') }})
                                        @else
                                            Rp {{ number_format($order->discount_value, 0, ',', '.') }}
                                        @endif
                                    </p>
                                </div>
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Harga Final</label>
                                    <p class="mb-2 fw-bold text-success fs-5">Rp {{ number_format($order->final_price, 0, ',', '.') }}</p>
                                </div>
                                @if($order->text_content)
                                @php
                                    $rawText = (string) $order->text_content;
                                    // collect decorative component names (hiasan, kata_sambung) for highlighting
                                    $decorative = [];
                                    foreach ($order->components as $c) {
                                        $name = is_array($c) ? ($c['name'] ?? null) : ($c->name ?? null);
                                        $type = is_array($c) ? ($c['type'] ?? null) : ($c->type ?? null);
                                        if ($name && in_array($type, ['hiasan', 'kata_sambung'], true)) {
                                            $decorative[] = $name;
                                        }
                                    }
                                    $decorative = array_values(array_unique($decorative));

                                    // highlight decorative parts in the rendered text (longest-first)
                                    $renderedText = '';
                                    if (!empty($decorative)) {
                                        usort($decorative, function($a, $b){ return mb_strlen($b) <=> mb_strlen($a); });
                                        $escaped = array_map(function($n){ return preg_quote($n, '/'); }, $decorative);
                                        $pattern = implode('|', $escaped);
                                        $parts = preg_split('/(' . $pattern . ')/u', $rawText, -1, PREG_SPLIT_DELIM_CAPTURE);
                                        foreach ($parts as $part) {
                                            if (in_array($part, $decorative, true)) {
                                                // find component for this decorative name
                                                $compMatch = null;
                                                foreach ($order->components as $cc) {
                                                    $cname = is_array($cc) ? ($cc['name'] ?? null) : ($cc->name ?? null);
                                                    $ctype = is_array($cc) ? ($cc['type'] ?? null) : ($cc->type ?? null);
                                                    if ($cname === $part) { $compMatch = ['name' => $cname, 'type' => $ctype]; break; }
                                                }
                                                $cls = ($compMatch && $compMatch['type'] === 'kata_sambung') ? 'bg-info' : 'bg-secondary';
                                                $renderedText .= '<span class="badge ' . $cls . ' me-1">' . e($part) . '</span>';
                                            } else {
                                                $renderedText .= e($part);
                                            }
                                        }
                                    } else {
                                        $renderedText = e($rawText);
                                    }

                                    // build a per-character token map to better reflect allocation for single-letter components
                                    $chars = preg_split('//u', $rawText, -1, PREG_SPLIT_NO_EMPTY);
                                    $charMap = [];
                                    foreach ($chars as $ch) {
                                        if (trim($ch) === '') { continue; }
                                        $matched = null;
                                        foreach ($order->components as $cc) {
                                            $cname = is_array($cc) ? ($cc['name'] ?? null) : ($cc->name ?? null);
                                            if ($cname === $ch) { $matched = $cc; break; }
                                        }
                                        $charMap[] = ['char' => $ch, 'component' => $matched];
                                    }

                                    // group attached components safely for the components table
                                    $componentsCollection = $order->components;
                                    // if relation appears empty, try reloading from the relation (handles array vs collection issues)
                                    if ((is_array($componentsCollection) && empty($componentsCollection)) || ($componentsCollection instanceof \Illuminate\Support\Collection && $componentsCollection->isEmpty())) {
                                        try {
                                            $componentsCollection = $order->components()->get();
                                            $reloadedComponents = true;
                                        } catch (\Throwable $e) {
                                            $componentsCollection = collect();
                                            $reloadedComponents = false;
                                        }
                                    } else {
                                        $reloadedComponents = false;
                                    }

                                    $grouped = [];
                                    foreach ($componentsCollection as $cc) {
                                        $cname = is_array($cc) ? ($cc['name'] ?? null) : ($cc->name ?? null);
                                        $ctype = is_array($cc) ? ($cc['type'] ?? null) : ($cc->type ?? null);
                                        // pivot qty may be array/object as well
                                        $qty = 1;
                                        if (is_array($cc) && isset($cc['pivot'])) {
                                            $qty = $cc['pivot']['quantity_used'] ?? ($cc['pivot']['qty'] ?? 1);
                                        } elseif (!is_array($cc) && isset($cc->pivot)) {
                                            $qty = $cc->pivot->quantity_used ?? ($cc->pivot->qty ?? 1);
                                        }
                                        $grouped[$ctype][] = ['name' => $cname, 'type' => $ctype, 'qty' => $qty];
                                    }
                                @endphp
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-muted">Ucapan / Konten Tulisan</label>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0 font-monospace">{!! $renderedText !!}</p>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">Peta token: </small>
                                        @if(!empty($charMap))
                                            @foreach($charMap as $entry)
                                                @php
                                                    $comp = $entry['component'] ?? null;
                                                    if (is_array($comp)) {
                                                        $compName = $comp['name'] ?? null;
                                                        $compType = $comp['type'] ?? null;
                                                    } else {
                                                        $compName = $comp->name ?? null;
                                                        $compType = $comp->type ?? null;
                                                    }
                                                @endphp
                                                @if($compName)
                                                    <span class="badge bg-success ms-1">{{ e($entry['char']) }} &rarr; {{ e($compName) }} ({{ e($compType) }})</span>
                                                @else
                                                    <span class="badge bg-light text-muted border ms-1">{{ e($entry['char']) }} (tidak ada komponen)</span>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aset yang Dialokasikan -->
                @if($order->assets->count() > 0)
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-dolly-flatbed me-2"></i>Aset yang Dialokasikan</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tipe</th>
                                            <th>Warna</th>
                                            <th class="text-end">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->assets as $asset)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($asset->type) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $rawColor = trim((string) $asset->color);
                                                @endphp
                                                <span class="badge bg-dark text-white">{{ $rawColor ? ucfirst($rawColor) : '-' }}</span>
                                            </td>
                                            <td class="text-end">{{ $asset->pivot->quantity_used ?? 1 }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Komponen yang Digunakan -->
                @if($order->components->count() > 0)
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>Komponen yang Digunakan</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Komponen</th>
                                            <th>Tipe</th>
                                            <th class="text-end">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($grouped as $type => $components)
                                        <tr>
                                            <td colspan="3" class="bg-light fw-semibold">
                                                {{ str_replace('_', ' ', ucfirst($type)) }}
                                            </td>
                                        </tr>
                                        @foreach($components as $component)
                                        <tr>
                                            <td class="ps-4">{{ e($component['name']) }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ str_replace('_', ' ', e($component['type'])) }}</span>
                                            </td>
                                            <td class="text-end">{{ e($component['qty'] ?? 1) }}</td>
                                        </tr>
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Debug: raw pivot and components (temporary) -->
                <div class="col-12 mt-3">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">Debug: order_components & components</div>
                        <div class="card-body small">
                            <pre>rawPivot: {{ isset($rawPivot) ? e(json_encode($rawPivot, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) : 'n/a' }}</pre>
                            <pre>components: {{ e(json_encode($order->components->toArray() ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Timeline / Riwayat -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Pesanan</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Pesanan Dibuat</h6>
                                        <p class="text-muted mb-0">{{ $order->created_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</p>
                                    </div>
                                </div>
                                
                                @if($order->status !== 'pending')
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Status: {{ ucfirst($order->status) }}</h6>
                                        <p class="text-muted mb-0">{{ $order->updated_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($order->status === 'selesai')
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Pesanan Selesai</h6>
                                        <p class="text-muted mb-0">Aset dikembalikan ke inventaris</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        ID Pesanan: {{ $order->id }} | Dibuat: {{ $order->created_at->format('d M Y, H:i') }}
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Daftar Pesanan
                                    </a>
                                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-primary">
                                        <i class="fas fa-edit me-1"></i>Edit Pesanan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.timeline-item:last-child .timeline-marker {
    box-shadow: 0 0 0 2px #28a745;
}
</style>
@endpush
