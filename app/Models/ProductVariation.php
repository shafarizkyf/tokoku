<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model {

  use SoftDeletes, HasFactory;

  protected $fillable = [
    'product_id',
    'price',
    'discount_price',
    'condition',
    'stock',
    'sku',
    'weight',
  ];

  protected static function booted() {
    static::updated(function(ProductVariation $productVariation){
      CartItem::whereProductVariationId($productVariation->id)->update([
        'price_at_time' => $productVariation->price,
        'price_discount_at_time' => $productVariation->discount_price,
      ]);
    });

    static::saved(function(ProductVariation $productVariation){
      CartItem::whereProductVariationId($productVariation->id)->update([
        'price_at_time' => $productVariation->price,
        'price_discount_at_time' => $productVariation->discount_price,
      ]);
    });

    static::deleted(function(ProductVariation $productVariation){
      CartItem::whereProductVariationId($productVariation->id)->delete();
    });
  }

  protected $hidden = [
    'product_id',
    'created_at',
    'updated_at',
    'deleted_at'
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
    return $this->discount_price ? number_format(100 - $this->discount_price / $this->price * 100, 0) : null;
  }

  public function getWeightInKgAttribute() {
    return $this->weight ? number_format($this->weight / 1000, 2) : 0;
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

  public function product() {
    return $this->belongsTo(Product::class);
  }

  public function variationOptions() {
    return $this->hasMany(ProductVariationOption::class);
  }

}
