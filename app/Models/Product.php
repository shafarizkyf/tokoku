<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model {

  protected $fillable = [
    'store_id',
    'name',
    'slug',
    'review_avg',
    'sold_count',
    'created_by',
  ];

  public function images() {
    return $this->hasMany(ProductImage::class);
  }

  public function variation() {
    return $this->hasMany(ProductVariation::class);
  }

}
