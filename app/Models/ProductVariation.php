<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model {

  protected $fillable = [
    'product_id',
    'price',
    'discount_price',
    'condition',
    'stock',
    'sku'
  ];

  protected $hidden = [
    'created_at',
    'updated_at'
  ];

}
