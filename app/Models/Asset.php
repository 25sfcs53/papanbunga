<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Asset (Tipe Aset dengan Stok Berbasis Kuantitas)
 *
 * Kolom: type (papan|rak), description, color, quantity_total, quantity_rented
 */
class Asset extends Model
{
    use HasFactory;


    /**
     * Scope: only assets that are available for allocation.
     *
     * This checks the computed available quantity (quantity_total - quantity_rented - quantity_repair)
     * and also allows using a status column 'tersedia' when present.
     */
    public function scopeAvailable($query)
    {
        // If table has quantity_total/quantity_rented columns, compute available > 0
        $query->whereRaw('(COALESCE(quantity_total,0) - COALESCE(quantity_rented,0) - COALESCE(quantity_repair,0)) > 0');

        // If there's a 'status' column using 'tersedia', include those as available too
        if (Schema::hasColumn((new static)->getTable(), 'status')) {
            $query->orWhere('status', 'tersedia');
        }

        return $query;
    }
    public const TYPE_PAPAN = 'papan';
    public const TYPE_RAK = 'rak';

    protected $fillable = [
        'type',
        'description',
        'color',
        'quantity_total',
        'quantity_rented',
    ];

    /**
     * Relasi: Aset digunakan pada banyak Order (pivot order_assets)
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_assets')
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    /**
     * Accessor: Kuantitas aset yang tersedia.
     *
     * @return int
     */
    public function getQuantityAvailableAttribute(): int
    {
    $total = $this->quantity_total ?? 0;
    $rented = $this->quantity_rented ?? 0;
    $repair = $this->quantity_repair ?? 0;

    $available = $total - $rented - $repair;
    return $available >= 0 ? $available : 0;
    }


    /**
     * Scope: filter berdasarkan tipe aset.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
