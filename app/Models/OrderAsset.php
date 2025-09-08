<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * OrderAsset (Pivot eksplisit untuk relasi orders - assets)
 *
 * Kolom: order_id, asset_id
 */
class OrderAsset extends Model
{
    use HasFactory;

    protected $table = 'order_assets';

    protected $fillable = [
        'order_id',
        'asset_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
