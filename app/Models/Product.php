<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Product (Varian Papan Bunga)
 *
 * Kolom: name, base_price, photo, description
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_price',
        'photo',
    'description',
    'required_papan_color',
    'required_papan_quantity',
    'default_rack_color',
    'active',
    ];


    protected $casts = [
        'base_price' => 'decimal:2',
    'active' => 'boolean',
    ];

    // Relasi: satu produk bisa dimiliki oleh banyak order
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
