<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Order (Pesanan)
 *
 * Kolom: customer_id, product_id, base_price, discount_type, discount_value, final_price,
 * text_content, status, delivery_date
 */
class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_DIRAKIT = 'Dirakit';
    public const STATUS_DISEWA = 'Disewa';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'customer_id',
        'product_id',
        'base_price',
        'discount_type',
        'discount_value',
        'final_price',
        'text_content',
        'status',
    'delivery_date',
    'shipping_address',
        'required_rack_color', // Menambahkan kolom untuk warna rak yang diperlukan
    ];


    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_price' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    // Relasi
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relasi many-to-many ke Asset melalui pivot order_assets
    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'order_assets')
            ->withPivot(['quantity_used'])
            ->withTimestamps();
    }

    // Relasi many-to-many ke Component melalui pivot order_components
    public function components()
    {
        return $this->belongsToMany(Component::class, 'order_components')
            ->withPivot(['quantity_used'])
            ->withTimestamps();
    }

    // Relasi ke model pivot eksplisit (opsional namun berguna)
    public function orderAssets()
    {
        return $this->hasMany(OrderAsset::class);
    }

    public function orderComponents()
    {
        return $this->hasMany(OrderComponent::class);
    }
}
