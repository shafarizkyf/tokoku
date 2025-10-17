<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model {
  protected $fillable = [
    'product_id',
    'path'
  ];

  protected $hidden = [
    'product_id',
    'path',
    'created_at',
    'updated_at'
  ];

  public $appends = [
    'url'
  ];

  protected static function booted() {
    static::deleted(function(ProductImage $productImage){
      Storage::disk('public')->delete($productImage->path);
    });
  }

  public function getUrlAttribute() {
    return str_contains($this->path, 'http') // path may still refer to origin host
      ? $this->path
      : url(Storage::url($this->path)); // already downloaded to local
  }

}
