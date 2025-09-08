<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Order;
use App\Models\Expense;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman utama laporan.
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfMonth();

        // Laporan Pemasukan
        $incomeReport = Order::where('status', 'Selesai')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->orderBy('updated_at', 'desc')
            ->get();
        $totalIncome = $incomeReport->sum('final_price');

        // Laporan Pengeluaran
        $expenseReport = Expense::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();
        $totalExpense = $expenseReport->sum('amount');

        // Laporan Laba/Rugi
        $netProfit = $totalIncome - $totalExpense;

        return view('reports.index', compact(
            'incomeReport',
            'totalIncome',
            'expenseReport',
            'totalExpense',
            'netProfit',
            'startDate',
            'endDate'
        ));
    }
}
