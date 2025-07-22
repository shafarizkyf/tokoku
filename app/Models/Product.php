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
    'source',
  ];

  public function image() {
    return $this->hasOne(ProductImage::class);
  }

  public function images() {
    return $this->hasMany(ProductImage::class);
  }

  public function variations() {
    return $this->hasMany(ProductVariation::class);
  }

  public function variation() {
    return $this->hasOne(ProductVariation::class)->orderBy('price');
  }

  public function scopeSearch($query, $value) {
    return $query->where('name', 'like', "%{$value}%");
  }

}
