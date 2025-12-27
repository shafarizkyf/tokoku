<?php

namespace App\Models;

use App\Models\Scopes\ProductActive;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model {

  protected $fillable = [
    'order_id',
    'product_id',
    'product_variation_id',
    'name_snapshot',
    'variation_snapshot',
    'price',
    'quantity',
    'discount',
    'subtotal',
    'weight',
  ];

  public function product() {
    return $this->belongsTo(Product::class)
      ->withoutGlobalScope(ProductActive::class);
  }

  public function productVariation() {
    return $this->belongsTo(ProductVariation::class);
  }

}
