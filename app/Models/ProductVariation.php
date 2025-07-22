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

  public function scopeOptions($query) {
    return $query->select(
        'variation_attributes.id as attribute_id',
        'variation_attributes.name as attribute_name',
        'variation_options.id as option_id',
        'variation_options.value as option_name'
      )
      ->join('product_variation_options', 'product_variation_options.product_variation_id', '=', 'product_variations.id')
      ->join('variation_options', 'variation_options.id', '=', 'product_variation_options.variation_option_id')
      ->join('variation_attributes', 'variation_attributes.id', '=', 'variation_options.variation_attribute_id')
      ->groupBy('attribute_id', 'attribute_name', 'option_id', 'option_name');
  }

}
