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
    'id',
    'product_id',
    'path',
    'created_at',
    'updated_at'
  ];

  public $appends = [
    'url'
  ];

  public function getUrlAttribute() {
    return url(Storage::url($this->path));
  }

}
