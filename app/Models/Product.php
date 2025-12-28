<?php

namespace App\Models;

use App\Models\Scopes\ProductActive;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

#[ScopedBy([ProductActive::class])]
class Product extends Model {

  use Searchable, SoftDeletes, HasFactory;

  protected $fillable = [
    'store_id',
    'name',
    'slug',
    'review_avg',
    'sold_count',
    'created_by',
    'source',
    'is_active'
  ];

  protected $hidden = [
    'deleted_at'
  ];

  protected static function booted(): void {
    static::updated(function(Product $product){
      if (!$product->is_active) {
        CartItem::whereProductId($product->id)->delete();
        Cache::tags(['cartItems'])->flush();
      }
    });
  }

  public function toSearchableArray() {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'slug' => $this->slug,
      'description' => strip_tags($this->description), // Optional, but useful for full text search
      'condition' => $this->condition,
      'review_avg' => $this->review_avg,
      'review_count' => $this->review_count,
      'sold_count' => $this->sold_count,
    ];
  }

  public function getDescriptionAttribute($value) {
    return strip_tags($value);
  }

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
