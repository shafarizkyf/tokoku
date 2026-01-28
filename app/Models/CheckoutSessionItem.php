<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSessionItem extends Model
{
    protected $fillable = [
        'checkout_session_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'price_at_time',
        'price_discount_at_time',
    ];

    protected $casts = [
        'price_at_time' => 'integer',
        'price_discount_at_time' => 'integer',
        'quantity' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function checkoutSession()
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function getSubtotalAttribute(): int
    {
        $price = $this->price_discount_at_time ?? $this->price_at_time;
        return $price * $this->quantity;
    }
}
