<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Expense (Pengeluaran)
 *
 * Kolom: date (tanggal), category, description, amount
 */
class Expense extends Model
{
    use HasFactory;

    // Kategori standar pengeluaran
    public const CAT_PEMBELIAN_STOK = 'pembelian_stok';
    public const CAT_OPERASIONAL = 'operasional';
    public const CAT_GAJI_TIM = 'gaji_tim';
    public const CAT_PERBAIKAN_ASET = 'perbaikan_aset';
    public const CAT_LAIN_LAIN = 'lain_lain';

    protected $fillable = [
        'date',
        'category',
        'description',
        'amount',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Mendapatkan daftar kategori pengeluaran yang bisa dipilih.
     *
     * @return array<string, string>
     */
    public static function getCategories(): array
    {
        return [
            self::CAT_PEMBELIAN_STOK => 'Pembelian Stok',
            self::CAT_OPERASIONAL    => 'Operasional',
            self::CAT_GAJI_TIM       => 'Gaji Tim',
            self::CAT_PERBAIKAN_ASET => 'Perbaikan Aset',
            self::CAT_LAIN_LAIN      => 'Lain-lain',
        ];
    }
}
