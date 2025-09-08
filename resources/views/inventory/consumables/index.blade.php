@extends('layouts.app')

@section('title', 'Consumables')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Consumables</h1>
        <a href="{{ route('consumables.create') }}" class="btn btn-primary">Tambah</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Satuan</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $it)
                        <tr>
                            <td>{{ $it->id }}</td>
                            <td>{{ $it->name }}</td>
                            <td>{{ $it->unit ?? '-' }}</td>
                            <td>{{ $it->quantity }}</td>
                            <td>
                                <a href="{{ route('consumables.edit', $it) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('consumables.destroy', $it) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Hapus?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
