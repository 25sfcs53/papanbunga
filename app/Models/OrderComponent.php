<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * OrderComponent (Pivot eksplisit untuk relasi orders - components)
 *
 * Kolom: order_id, component_id, quantity_used
 */
class OrderComponent extends Model
{
    use HasFactory;

    protected $table = 'order_components';

    protected $fillable = [
        'order_id',
        'component_id',
        'quantity_used',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }
}
