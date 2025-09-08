<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Tampilkan daftar pelanggan.
     */
    public function index(): View
    {
        $search = request('search');
        
        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('phone_number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    /**
     * Form create pelanggan.
     */
    public function create(): View
    {
        return view('customers.create');
    }

    /**
     * Simpan pelanggan baru.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil dibuat.');
    }

    /**
     * Form edit pelanggan.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update data pelanggan.
     */
    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Hapus pelanggan.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil dihapus.');
    }
}
