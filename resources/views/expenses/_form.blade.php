@php
    $expenseCategories = \App\Models\Expense::getCategories();
@endphp

<div class="mb-3">
    <label for="date" class="form-label">Tanggal</label>
    <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', isset($expense) ? $expense->date?->format('Y-m-d') : date('Y-m-d')) }}" required>

    @error('date')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="category" class="form-label">Kategori</label>
    <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
        <option value="" disabled {{ old('category', $expense->category ?? null) ? '' : 'selected' }}>Pilih Kategori...</option>
        @foreach ($expenseCategories as $key => $label)
            <option value="{{ $key }}" {{ old('category', $expense->category ?? null) == $key ? 'selected' : '' }}>

                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('category')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="amount" class="form-label">Jumlah (Rp)</label>
    <input type="number" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $expense->amount ?? '') }}" required step="1">

    @error('amount')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Deskripsi</label>
    <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $expense->description ?? '') }}</textarea>

    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex justify-content-end">
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary me-2">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        Simpan
    </button>
</div>
