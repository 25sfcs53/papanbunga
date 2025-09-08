<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama.
     * Hanya bisa diakses oleh Owner.
     */
    public function index(): View
    {
        // Pemasukan & Laba Rugi (Bulan Ini)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalIncome = Order::where('status', 'Selesai')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->sum('final_price');

        $totalExpense = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netProfit = $totalIncome - $totalExpense;

        // Pesanan Aktif
        $activeOrdersCount = Order::whereIn('status', ['Dirakit', 'Disewa'])->count();

        // Data untuk Chart (Contoh: 12 bulan terakhir)
        $incomeData = $this->getMonthlyFinancialData(Order::class, 'final_price', 'updated_at', ['status' => 'Selesai']);
        $expenseData = $this->getMonthlyFinancialData(Expense::class, 'amount', 'date');

        return view('dashboard', [
            'totalIncome'       => (float) $totalIncome,
            'totalExpense'      => (float) $totalExpense,
            'netProfit'         => (float) $netProfit,
            'activeOrdersCount' => $activeOrdersCount,
            'chartLabels'       => array_keys($incomeData),
            'incomeDataset'     => array_values($incomeData),
            'expenseDataset'    => array_values($expenseData),
        ]);
    }

    /**
     * Helper untuk mengambil data keuangan per bulan selama 12 bulan terakhir.
     */
    private function getMonthlyFinancialData(string $model, string $sumColumn, string $dateColumn, array $conditions = []): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            $start = $date->startOfMonth()->copy();
            $end = $date->endOfMonth()->copy();

            $query = $model::whereBetween($dateColumn, [$start, $end]);

            if (!empty($conditions)) {
                $query->where($conditions);
            }

            $data[$monthName] = (float) $query->sum($sumColumn);
        }
        return $data;
    }
}
