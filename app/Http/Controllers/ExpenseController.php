<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Tampilkan daftar pengeluaran.
     */
    public function index(): View
    {
        $search = request('search');
        
        $expenses = Expense::query()
            ->when($search, function ($query, $search) {
                return $query->where('description', 'like', "%{$search}%")
                             ->orWhere('category', 'like', "%{$search}%");
            })
            ->latest('date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => (float) Expense::sum('amount'),
            'month' => (float) Expense::whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount'),
        ];

        return view('expenses.index', compact('expenses', 'summary', 'search'));
    }


    /**
     * Form create pengeluaran.
     */
    public function create(): View
    {
        $categories = $this->categories();

        return view('expenses.create', compact('categories'));
    }

    /**
     * Simpan pengeluaran baru.
     */
    public function store(ExpenseRequest $request): RedirectResponse
    {
        Expense::create($request->validated());

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran berhasil dicatat.');
    }

    /**
     * Form edit pengeluaran.
     */
    public function edit(Expense $expense): View
    {
        $categories = $this->categories();

        return view('expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update pengeluaran.
     */
    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran berhasil diperbarui.');
    }

    /**
     * Hapus pengeluaran.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran berhasil dihapus.');
    }

    /**
     * Daftar kategori tersedia.
     *
     * @return array<string,string>
     */
    protected function categories(): array
    {
        return Expense::getCategories();
    }
}
