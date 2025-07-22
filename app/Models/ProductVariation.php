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
    'product_id',
    'created_at',
    'updated_at'
  ];

  public $appends = [
    'discount_percentage'
  ];

  protected function casts() {
    return [
      'price' => 'double',
      'discount_price' => 'double',
    ];
  }

  public function getDiscountPercentageAttribute() {
    return $this->discount_price ? number_format($this->discount_price / $this->price * 100, 0) : null;
  }

}
