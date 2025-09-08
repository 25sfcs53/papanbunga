<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer (Pelanggan)
 *
 * Kolom: name, phone_number
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
