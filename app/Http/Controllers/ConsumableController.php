<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsumableRequest;
use App\Models\Consumable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsumableController extends Controller
{
    public function index(): View
    {
        $items = Consumable::latest()->paginate(12);

        return view('inventory.consumables.index', compact('items'));
    }

    public function create(): View
    {
        return view('inventory.consumables.create');
    }

    public function store(ConsumableRequest $request): RedirectResponse
    {
        Consumable::create($request->validated());

        return redirect()->route('consumables.index')->with('status', 'Consumable berhasil dibuat.');
    }

    public function edit(Consumable $consumable): View
    {
        return view('inventory.consumables.edit', compact('consumable'));
    }

    public function update(ConsumableRequest $request, Consumable $consumable): RedirectResponse
    {
        $consumable->update($request->validated());

        return redirect()->route('consumables.index')->with('status', 'Consumable berhasil diperbarui.');
    }

    public function destroy(Consumable $consumable): RedirectResponse
    {
        $consumable->delete();

        return redirect()->route('consumables.index')->with('status', 'Consumable berhasil dihapus.');
    }
}
