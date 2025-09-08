<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Component (Stok Komponen Reusable)
 *
 * Kolom: name, type (huruf_besar|huruf_kecil|angka|simbol|hiasan|kata_sambung), color, quantity_available, stok_total, stok_used
 */
class Component extends Model
{
    use HasFactory;
     public const TYPE_HURUF_BESAR = 'huruf_besar';
     public const TYPE_HURUF_KECIL = 'huruf_kecil';
     public const TYPE_ANGKA = 'angka';
     public const TYPE_SIMBOL = 'simbol';
     public const TYPE_HIASAN = 'hiasan';
     public const TYPE_KATA_SAMBUNG = 'kata_sambung';

    protected $fillable = [
        'name',
        'type',
    'color',
    'quantity_available',
    'stok_total',
    'stok_used',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
    'stok_total' => 'integer',
    'stok_used' => 'integer',
    ];

    /**
     * Relasi: Komponen digunakan pada banyak Order (pivot order_components)
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_components')
            ->withPivot(['quantity_used'])
            ->withTimestamps();
    }
}
